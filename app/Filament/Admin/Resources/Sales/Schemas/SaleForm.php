<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use App\Models\Product;
use App\Models\Customer;
use App\Models\ProductRecipeSlot;
use App\Models\SlotSubstitute;
use App\Services\SaleInvoiceNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaleForm
{
    // ============================================================
    // NUMBER HELPERS
    // ============================================================

    /**
     * Convert Indonesian formatted number into float.
     *
     * Examples:
     * 30.000,00      -> 30000 // 1.250.000,50   -> 1250000.50 // 30000          -> 30000 // 30000.50       -> 30000.50
     */
    public static function parseNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        /*
         * Indonesian format:
         * 30.000,00 // 1.250.000,50
         */
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);

            return (float) $value;
        }

        /*
         * Plain numeric:
         * 30000 // 30000.50
         */
        return (float) $value;
    }

    /**
     * Format number as Indonesian currency.
     * 30000 -> 30.000,00
     */
    public static function formatCurrency(mixed $value): string
    {
        return number_format(
            self::parseNumber($value),
            2,
            ',',
            '.'
        );
    }

    // ============================================================
    // PRODUCT HELPERS
    // ============================================================

    /**
     * Find a product using product_id.
     * This should always be our first choice when restoring an existing sale item.
     */
    public static function findProductById(mixed $productId): ?Product
    {
        if (! $productId) {
            return null;
        }

        return Product::query()
            ->with(['brandModel', 'categoryModel'])
            ->find($productId);
    }

    /**
     * Find a product containing a specific part number.
     * Used only as a fallback for older records where product_id may be missing.
     */
    public static function findProductByPartNo(string $partNo): ?Product
    {
        $partNo = trim($partNo);

        if ($partNo === '') {
            return null;
        }

        return Product::query()
            ->with(['brandModel', 'categoryModel'])
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->get(['id', 'name', 'code', 'brand', 'category'])
            ->first(function ($product) use ($partNo): bool {
                return collect(preg_split('/\r\n|\r|\n/', (string) $product->code))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->contains(fn ($line) => strcasecmp($line, $partNo) === 0);
            });
    }

    /**
     * Fill all product-related fields: product_id, product_name, brand,
     * stock status, and composite component substitutions.
     *
     * NOTE: unlike Purchases, SaleItem has no "category" column, so it's
     * not set here.
     */
    public static function fillProductFields(Product $product, callable $set, float $qty = 1): void
    {
        $set('product_id', $product->id);

        /*
         * Save description.
         */
        $set('product_name', $product->name ?? '-');

        /*
         * Save brand.
         */
        $set(
            'brand',
            $product->brandModel?->name
                ?? $product->brand
                ?? '-'
        );

        /*
         * Save stock status (Sale-specific — customers need to see
         * this before they sell something that isn't in stock).
         */
        $status = self::stockStatus($product);
        $set('stock_label', $status['label']);
        $set('stock_color', $status['color']);

        /*
         * Populate composite component substitutions, if applicable.
         */
        self::populateComponents($set, $product, $qty);
    }

    // ============================================================
    // STOCK STATUS
    // ============================================================

    public static function stockStatus(Product $product): array
    {
        if ($product->is_composite) {
            $builds = $product->available_builds;

            if ($builds <= 0) {
                return ['label' => 'No Stock (0 builds)', 'color' => 'danger'];
            }

            if ($product->track_low_stock && $builds <= ($product->min_stock_threshold ?? 0)) {
                return ['label' => "Low Stock ({$builds} builds)", 'color' => 'warning'];
            }

            return ['label' => "In Stock ({$builds} builds)", 'color' => 'success'];
        }

        $stock = $product->stock ?? 0;

        if ($stock <= 0) {
            return ['label' => 'No Stock', 'color' => 'danger'];
        }

        if ($product->track_low_stock && $stock <= ($product->min_stock_threshold ?? 0)) {
            return ['label' => "Low Stock ({$stock})", 'color' => 'warning'];
        }

        return ['label' => "In Stock ({$stock})", 'color' => 'success'];
    }

    // ============================================================
    // COMPOSITE PRODUCTS
    // ============================================================

    private static function populateComponents(callable $set, Product $product, float $qty): void
    {
        if (! $product->is_composite) {
            $set('is_composite_item', false);
            $set('components', []);

            return;
        }

        $slots = $product->recipeSlots()->with('defaultSubstitute.product')->get();

        $set('is_composite_item', true);
        $set('components', $slots->map(fn ($slot) => [
            'slot_id' => $slot->id,
            'slot_name' => $slot->slot_name,
            'chosen_product_id' => $slot->defaultSubstitute?->product_id,
            'qty_used' => $slot->quantity * $qty,
        ])->toArray());
    }

    // ============================================================
    // RECALCULATION
    // ============================================================

    /**
     * Recalculate all item totals and subtotal.
     *
     * @param array<int|string, array<string, mixed>> $items The Repeater's
     * current items array, passed in directly from the $state parameter of
     * the Repeater's own afterStateUpdated hook. Do NOT fetch this via
     * $get('items', isAbsolute: true) — on ->relationship() repeaters that
     * lookup reliably returns an empty array.
     */
    public static function recalculateItems(array $items, callable $set, callable $get): void
    {
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $qty = self::parseNumber($item['qty'] ?? 0);
            $price = self::parseNumber($item['price'] ?? 0);

            $total = $qty * $price;

            $subtotal += $total;

            /*
             * Save numeric row total.
             *
             * NOTE: SaleItem's item-level column is "total", not
             * "grand_total" like PurchaseItem.
             */
            $set("items.{$index}.total", $total, isAbsolute: true);
        }

        /*
         * Save numeric subtotal.
         */
        $set('grand_total', $subtotal, isAbsolute: true);

        self::recalculateFinal($set, $get);
    }

    /**
     * Recalculate final total.
     * final = subtotal + tax - discount
     */
    public static function recalculateFinal(callable $set, callable $get): void
    {
        $subtotal = self::parseNumber($get('grand_total', isAbsolute: true) ?? 0);
        $taxRate = self::parseNumber($get('tax', isAbsolute: true) ?? 0);
        $discount = self::parseNumber($get('discount', isAbsolute: true) ?? 0);

        $tax = $subtotal * ($taxRate / 100);
        $final = max(0, $subtotal + $tax - $discount);

        $set('final_total', $final, isAbsolute: true);
    }

    // ============================================================
    // PART NUMBER DROPDOWN
    // ============================================================

    /**
     * PLAIN TEXT ONLY — no HTML/allowHtml here.
     *
     * allowHtml() + async search reliably breaks the chip/list
     * decoupling in this Filament version: once you pick an item
     * from a rich-HTML dropdown, the selected chip shows the raw
     * HTML label instead of calling getOptionLabelUsing() (that
     * only fires correctly when hydrating an EXISTING saved
     * record, not a fresh in-session pick). Plain text is the
     * only configuration that has reliably kept the chip clean.
     */
    private static function partNoSearchLabel(Product $product, string $codeLine): string
    {
        $name = $product->name ?? '-';
        $brand = $product->brandModel?->name ?? '-';

        return trim($codeLine . '  -  ' . $name . '  -  ' . $brand);
    }

    /**
     * Search products by part number.
     */
    private static function partNoSearch(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        /*
         * Normalize:
         *
         * OR 2.4 -> or2.4 // OR2.4  -> or2.4 // OR-2.4 -> or2.4
         */
        $normalizedSearch = strtolower(str_replace([' ', '-'], '', $search));

        $products = Product::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->where(function ($query) use ($search, $normalizedSearch) {
                $query
                    ->where('code', 'ILIKE', "%{$search}%")
                    ->orWhereRaw(
                        "LOWER(REPLACE(REPLACE(code, ' ', ''), '-', '')) LIKE ?",
                        ["%{$normalizedSearch}%"]
                    );
            })
            ->with(['brandModel', 'categoryModel'])
            ->limit(50)
            ->get(['id', 'name', 'code', 'brand', 'category']);

        return $products
            ->flatMap(function ($product) use ($search, $normalizedSearch) {
                return collect(preg_split('/\r\n|\r|\n/', (string) $product->code))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->filter(function ($line) use ($search, $normalizedSearch) {
                        $lowerLine = strtolower($line);
                        $lowerSearch = strtolower($search);

                        /*
                         * Normal search.
                         */
                        if (str_contains($lowerLine, $lowerSearch)) {
                            return true;
                        }

                        /*
                         * Normalized search.
                         */
                        $normalizedLine = strtolower(str_replace([' ', '-'], '', $line));

                        return str_contains($normalizedLine, $normalizedSearch);
                    })
                    ->mapWithKeys(function ($line) use ($product) {
                        return [
                            "{$product->id}::{$line}" => self::partNoSearchLabel($product, $line),
                        ];
                    });
            })
            ->all();
    }

    // ============================================================
    // FORM
    // ============================================================

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ====================================================
            // HEADER
            // ====================================================

            TextInput::make('sale_inv_no')
                ->label('Invoice No')
                ->default(fn () => SaleInvoiceNumberService::generate())
                ->disabled()
                ->dehydrated(true),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('customer_id')
                ->label('Customer')
                ->options(
                    fn () => Customer::query()
                        ->where('status', 1)
                        ->get()
                        ->mapWithKeys(fn ($c) => [
                            $c->id => $c->company_name ?: $c->customer_name,
                        ])
                )
                ->searchable()
                ->required(),

            Select::make('payment_type')
                ->label('Payment Type')
                ->options([
                    'cash' => 'Cash',
                    'credit' => 'Credit',
                ])
                ->default('cash')
                ->live()
                ->required(),

            TextInput::make('payment_terms_days')
                ->label('Payment Terms (days)')
                ->numeric()
                ->minValue(1)
                ->default(30)
                ->visible(fn ($get) => $get('payment_type') === 'credit')
                ->required(fn ($get) => $get('payment_type') === 'credit'),

            // ====================================================
            // ITEMS
            // ====================================================

            Repeater::make('items')
                ->relationship()

                ->live()

                ->afterStateUpdated(function ($state, $set, $get) {
                    self::recalculateItems($state ?? [], $set, $get);
                })

                ->table([
                    TableColumn::make('Part No')->width('260px'),
                    TableColumn::make('Description')->width('220px'),
                    TableColumn::make('Brand')->width('120px'),
                    TableColumn::make('Qty')->width('90px'),
                    TableColumn::make('Unit Price')->width('150px'),
                    TableColumn::make('Total')->width('150px'),
                    TableColumn::make('Notes')->width('180px'),
                ])

                ->compact()

                ->schema([

                    // ============================================
                    // HIDDEN FIELDS
                    // ============================================

                    Hidden::make('product_id')->dehydrated(true),
                    Hidden::make('is_composite_item')->default(false),
                    Hidden::make('stock_label')->default(''),
                    Hidden::make('stock_color')->default(''),

                    // ============================================
                    // PART NUMBER
                    // ============================================

                    Select::make('part_no')
                        ->label('Part No')
                        ->extraAttributes([
                            'style' => 'font-family:monospace; font-weight:600; color:#000;',
                        ])

                        ->getSearchResultsUsing(
                            fn (string $search): array => self::partNoSearch($search)
                        )

                        ->getOptionLabelUsing(function ($value): ?string {
                            if (! $value) {
                                return null;
                            }

                            /*
                             * Normal stored value.
                             */
                            if (! str_contains((string) $value, '::')) {
                                return (string) $value;
                            }

                            /*
                             * Composite value from search dropdown.
                             */
                            [, $partNo] = explode('::', (string) $value, 2);

                            return $partNo;
                        })

                        ->searchable()
                        ->live()
                        ->dehydrated(true)
                        ->required()
                        ->placeholder('Paste or search part number...')

                        ->afterStateUpdated(function ($state, $set, $get): void {
                            if (! $state) {
                                return;
                            }

                            /*
                             * Search result:
                             *
                             * productId::partNumber
                             */
                            if (! str_contains((string) $state, '::')) {
                                return;
                            }

                            [$productId, $codeLine] = explode('::', (string) $state, 2);

                            $product = self::findProductById($productId);

                            if (! $product) {
                                return;
                            }

                            $qty = self::parseNumber($get('qty') ?? 1);

                            /*
                             * Fill product information (including stock
                             * status and composite components).
                             */
                            self::fillProductFields($product, $set, $qty);

                            /*
                             * Store ONLY the actual part number in the database.
                             */
                            $set('part_no', trim($codeLine));
                        })

                        /*
                         * IMPORTANT FIX:
                         *
                         * When editing an existing sale, use product_id FIRST.
                         *
                         * Do NOT try to find the product from part_no unless
                         * product_id is missing.
                         */
                        ->afterStateHydrated(function ($state, $set, $get): void {
                            $productId = $get('product_id');

                            /*
                             * --------------------------------
                             * FIRST:
                             * Restore directly from product_id.
                             * --------------------------------
                             */
                            $product = self::findProductById($productId);

                            /*
                             * --------------------------------
                             * FALLBACK:
                             * Find using part number.
                             * --------------------------------
                             */
                            if (! $product) {
                                $partNo = (string) $state;

                                if (str_contains($partNo, '::')) {
                                    $partNo = explode('::', $partNo, 2)[1];
                                }

                                $partNo = trim($partNo);

                                $product = self::findProductByPartNo($partNo);
                            }

                            if (! $product) {
                                return;
                            }

                            /*
                             * Keep plain part number.
                             */
                            $partNo = (string) $state;

                            if (str_contains($partNo, '::')) {
                                $partNo = explode('::', $partNo, 2)[1];
                            }

                            $set('part_no', trim($partNo));

                            $qty = self::parseNumber($get('qty') ?? 1);

                            /*
                             * Restore ALL product data.
                             */
                            self::fillProductFields($product, $set, $qty);
                        }),

                    // ============================================
                    // DESCRIPTION
                    // ============================================

                    TextInput::make('product_name')
                        ->label('Description')
                        ->readOnly()

                        /*
                         * Match the search dropdown's typography for
                         * visual consistency across the row.
                         */
                        ->extraInputAttributes([
                            'style' => 'color:#000; font-size:13px;',
                        ])

                        ->dehydrated(true)

                        ->hint(fn ($get) => $get('stock_label') ?: '')
                        ->hintColor(fn ($get) => $get('stock_color') ?: 'gray')
                        ->hintIcon(fn ($get) => match ($get('stock_color')) {
                            'success' => 'heroicon-m-check-circle',
                            'warning' => 'heroicon-m-exclamation-triangle',
                            'danger' => 'heroicon-m-x-circle',
                            default => null,
                        }),

                    // ============================================
                    // BRAND
                    // ============================================

                    TextInput::make('brand')
                        ->label('Brand')
                        ->readOnly()
                        ->extraInputAttributes([
                            'style' => 'color:#000; font-size:13px;',
                        ])
                        ->dehydrated(true),

                    // ============================================
                    // QTY
                    // ============================================

                    TextInput::make('qty')
                        ->label('Qty')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)

                        ->extraInputAttributes([
                            'onfocus' => 'this.select();',
                        ])

                        ->live(onBlur: true)

                        ->afterStateUpdated(function ($state, $set, $get) {
                            /*
                             * Component qty_used still needs to update
                             * immediately when qty changes — this is
                             * sibling-scoped so plain $get()/$set() (no
                             * isAbsolute) is fine here.
                             */
                            $components = $get('components') ?? [];
                            $qty = self::parseNumber($state ?? 1);

                            foreach ($components as $i => $comp) {
                                $slot = ProductRecipeSlot::find($comp['slot_id'] ?? null);
                                $qtyUsed = ($slot->quantity ?? 1) * $qty;
                                $set("components.{$i}.qty_used", $qtyUsed);
                            }

                            /*
                             * Total/subtotal recalculation is handled by
                             * the Repeater's own ->live() cascade.
                             */
                        }),

                    // ============================================
                    // UNIT PRICE
                    // ============================================

                    TextInput::make('price')
                        ->label('Unit Price')
                        ->inputMode('decimal')
                        ->prefix('Rp')

                        ->formatStateUsing(fn ($state) => self::formatCurrency($state))

                        ->extraInputAttributes([

                            /*
                             * Focus:
                             *
                             * 30.000,00
                             *
                             * becomes:
                             *
                             * 30000
                             */
                            'x-on:focus' => "
                                let input = \$event.target;
                                let value = input.value.trim();

                                if (!value) {
                                    return;
                                }

                                let raw = value
                                    .replace(/\\./g, '')
                                    .replace(',', '.');

                                let number = parseFloat(raw);

                                if (!Number.isNaN(number)) {
                                    input.value = number;
                                }

                                input.select();
                            ",

                            /*
                             * Blur:
                             *
                             * 30000
                             *
                             * becomes:
                             *
                             * 30.000,00
                             */
                            'x-on:blur' => "
                                let input = \$event.target;
                                let value = input.value.trim();

                                if (!value) {
                                    input.value = '0,00';
                                    return;
                                }

                                let raw = value
                                    .replace(/\\./g, '')
                                    .replace(',', '.');

                                let number = parseFloat(raw);

                                if (Number.isNaN(number)) {
                                    return;
                                }

                                input.value =
                                    new Intl.NumberFormat(
                                        'id-ID',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    ).format(number);

                                input.dispatchEvent(
                                    new Event(
                                        'input',
                                        {
                                            bubbles: true
                                        }
                                    )
                                );
                            ",
                        ])

                        ->live(onBlur: true)

                        ->dehydrateStateUsing(fn ($state) => self::parseNumber($state)),

                    // ============================================
                    // ROW TOTAL - HIDDEN
                    // ============================================

                    Hidden::make('total')
                        ->dehydrateStateUsing(function ($get) {
                            $qty = self::parseNumber($get('qty') ?? 0);
                            $price = self::parseNumber($get('price') ?? 0);

                            return $qty * $price;
                        })
                        ->dehydrated(true),

                    // ============================================
                    // ROW TOTAL - DISPLAY
                    // ============================================

                    Placeholder::make('total_display')
                        ->label('Total')
                        ->content(function ($get) {
                            $qty = self::parseNumber($get('qty') ?? 0);
                            $price = self::parseNumber($get('price') ?? 0);
                            $total = $qty * $price;

                            return 'Rp ' . self::formatCurrency($total);
                        }),

                    // ============================================
                    // NOTES
                    // ============================================

                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Remarks...')
                        ->rows(1),

                    // ============================================
                    // COMPONENT SUBSTITUTIONS — composite products only
                    // ============================================

                    Repeater::make('components')
                        ->relationship('components')
                        ->label('Component substitutions')
                        ->visible(fn ($get) => (bool) $get('is_composite_item'))
                        ->deletable(false)
                        ->addable(false)
                        ->schema([
                            Hidden::make('slot_id'),

                            TextInput::make('slot_name')
                                ->label('Slot')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(2)
                                ->afterStateHydrated(function ($state, $set, $get) {
                                    if (! $state) {
                                        $slotId = $get('slot_id');

                                        if ($slotId) {
                                            $slot = ProductRecipeSlot::find($slotId);

                                            if ($slot) {
                                                $set('slot_name', $slot->slot_name);
                                            }
                                        }
                                    }
                                }),

                            Select::make('chosen_product_id')
                                ->label('Use product')
                                ->options(function ($get) {
                                    $slotId = $get('slot_id');

                                    if (! $slotId) {
                                        return [];
                                    }

                                    return SlotSubstitute::where('slot_id', $slotId)
                                        ->with('product')
                                        ->get()
                                        ->pluck('product.name', 'product_id');
                                })
                                ->searchable()
                                ->live()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('qty_used')
                                ->label('Qty needed')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true)
                                ->columnSpan(1),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                ])

                /*
                 * Recalculate when editing.
                 */
                ->afterStateHydrated(function ($state, $set, $get) {
                    self::recalculateItems($state ?? [], $set, $get);
                })

                /*
                 * Add Item button.
                 */
                ->addAction(
                    fn ($action) => $action
                        ->label('Add Item')
                        ->icon('heroicon-m-plus')
                        ->color('warning')
                )

                ->columnSpanFull(),

            // ====================================================
            // TOTALS
            // ====================================================

            Hidden::make('grand_total')
                ->default(0)
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->dehydrated(true),

            Placeholder::make('subtotal_display')
                ->label('Subtotal')
                ->content(fn ($get) => 'Rp ' . self::formatCurrency($get('grand_total', isAbsolute: true) ?? 0))
                ->columnStart(2),

            // ====================================================
            // TAX
            // ====================================================

            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)

                ->extraInputAttributes([
                    'onfocus' => 'this.select();',
                ])

                ->live(onBlur: true)
                ->columnStart(2)

                ->afterStateUpdated(
                    fn ($state, $set, $get) => self::recalculateFinal($set, $get)
                ),

            // ====================================================
            // DISCOUNT
            // ====================================================

            TextInput::make('discount')
                ->label('Discount (Rp)')
                ->inputMode('decimal')
                ->prefix('Rp')
                ->default('0,00')

                ->formatStateUsing(fn ($state) => self::formatCurrency($state))

                ->extraInputAttributes([

                    'x-on:focus' => "
                        let input = \$event.target;
                        let value = input.value.trim();

                        if (!value) {
                            input.value = '0';
                            return;
                        }

                        let raw = value
                            .replace(/\\./g, '')
                            .replace(',', '.');

                        let number = parseFloat(raw);

                        if (!Number.isNaN(number)) {
                            input.value = number;
                        }

                        input.select();
                    ",

                    'x-on:blur' => "
                        let input = \$event.target;
                        let value = input.value.trim();

                        if (!value) {
                            input.value = '0,00';
                            return;
                        }

                        let raw = value
                            .replace(/\\./g, '')
                            .replace(',', '.');

                        let number = parseFloat(raw);

                        if (Number.isNaN(number)) {
                            return;
                        }

                        input.value =
                            new Intl.NumberFormat(
                                'id-ID',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            ).format(number);

                        input.dispatchEvent(
                            new Event(
                                'input',
                                {
                                    bubbles: true
                                }
                            )
                        );
                    ",
                ])

                ->live(onBlur: true)

                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))

                ->columnStart(2)

                ->afterStateUpdated(
                    fn ($state, $set, $get) => self::recalculateFinal($set, $get)
                ),

            // ====================================================
            // FINAL TOTAL
            // ====================================================

            Hidden::make('final_total')
                ->default(0)
                ->dehydrateStateUsing(fn ($state) => self::parseNumber($state))
                ->dehydrated(true),

            Placeholder::make('final_total_display')
                ->label('Final Total')
                ->content(fn ($get) => 'Rp ' . self::formatCurrency($get('final_total', isAbsolute: true) ?? 0))
                ->columnStart(2),
        ]);
    }
}
