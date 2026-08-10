<?php

namespace App\Filament\Admin\Resources\Purchases\Schemas;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseInvoiceNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseForm
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
     * This should always be our first choice when restoring an existing purchase item.
     */
    public static function findProductById(
        mixed $productId
    ): ?Product {
        if (! $productId) {
            return null;
        }

        return Product::query()
            ->with([
                'brandModel',
                'categoryModel',
            ])
            ->find($productId);
    }

    /**
     * Find a product containing a specific part number.
     * Used only as a fallback for older records where product_id may be missing.
     */
    public static function findProductByPartNo(
        string $partNo
    ): ?Product {
        $partNo = trim($partNo);

        if ($partNo === '') {
            return null;
        }

        return Product::query()
            ->with([
                'brandModel',
                'categoryModel',
            ])
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->get([
                'id',
                'name',
                'code',
                'brand',
                'category',
            ])
            ->first(
                function ($product) use ($partNo): bool {
                    return collect(
                        preg_split(
                            '/\r\n|\r|\n/',
                            (string) $product->code
                        )
                    )
                        ->map(
                            fn ($line) => trim($line)
                        )
                        ->filter()
                        ->contains(
                            fn ($line) =>
                                strcasecmp(
                                    $line,
                                    $partNo
                                ) === 0
                        );
                }
            );
    }

    /**
     * Fill all product-related fields.
     *
     * product_id, product_name, brand, category
     */
    public static function fillProductFields(
        Product $product,
        callable $set
    ): void {
        $set(
            'product_id',
            $product->id
        );

        /*
         * IMPORTANT: Save description.
         */
        $set(
            'product_name',
            $product->name ?? '-'
        );

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
         * Save category.
         */
        $set(
            'category',
            $product->categoryModel?->name
                ?? $product->category
                ?? null
        );
    }

    // ============================================================
    // RECALCULATION
    // ============================================================

    /**
     * Recalculate all item totals and subtotal.
     */
    public static function recalculateItems(
        array $items,
        callable $set,
        callable $get
    ): void {
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $qty = self::parseNumber(
                $item['qty'] ?? 0
            );

            $price = self::parseNumber(
                $item['price'] ?? 0
            );

            $total = $qty * $price;

            $subtotal += $total;

            /*
             * Save numeric row total.
             */
            $set(
                "items.{$index}.grand_total",
                $total,
                isAbsolute: true
            );
        }

        /*
         * Save numeric subtotal.
         */
        $set(
            'grand_total',
            $subtotal,
            isAbsolute: true
        );

        self::recalculateFinal(
            $set,
            $get
        );
    }

    /**
     * Recalculate final total.
     * final = subtotal + tax - discount
     */
    public static function recalculateFinal(
        callable $set,
        callable $get
    ): void {
        $subtotal = self::parseNumber(
            $get(
                'grand_total',
                isAbsolute: true
            ) ?? 0
        );

        $taxRate = self::parseNumber(
            $get(
                'tax',
                isAbsolute: true
            ) ?? 0
        );

        $discount = self::parseNumber(
            $get(
                'discount',
                isAbsolute: true
            ) ?? 0
        );

        $tax = $subtotal * (
            $taxRate / 100
        );

        $final = max(
            0,
            $subtotal + $tax - $discount
        );

        $set(
            'final_total',
            $final,
            isAbsolute: true
        );
    }

    // ============================================================
    // PART NUMBER DROPDOWN
    // ============================================================

    /**
     * Search result label: bold monospace part number, muted gray for
     * description/brand. No fixed width anywhere — the earlier "clipped"
     * dropdown was actually a fixed 720px inner width overflowing the
     * browser window, not a container limit. Flexbox with no forced
     * width sizes naturally to content, same as plain text did, but
     * lets us style it for readability.
     */
    private static function partNoSearchLabel(
        Product $product,
        string $codeLine
    ): string {
        $code = e($codeLine);

        $name = e(
            $product->name ?? '-'
        );

        $brand = e(
            $product->brandModel?->name
                ?? '-'
        );

        return '
            <div style="display:flex; gap:12px; align-items:baseline; white-space:nowrap; font-size:13px;">
                <span style="font-family:monospace; font-weight:600; color:#111827;">' . $code . '</span>
                <span style="color:#374151;">' . $name . '</span>
                <span style="color:#9ca3af;">' . $brand . '</span>
            </div>
        ';
    }

    /**
     * Search products by part number.
     */
    private static function partNoSearch(
        string $search
    ): array {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        /*
         * Normalize:
         *
         * OR 2.4 -> or2.4 // OR2.4  -> or2.4 // OR-2.4 -> or2.4
         */
        $normalizedSearch = strtolower(
            str_replace(
                [' ', '-'],
                '',
                $search
            )
        );

        $products = Product::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->where(function ($query) use (
                $search,
                $normalizedSearch
            ) {
                $query
                    ->where(
                        'code',
                        'ILIKE',
                        "%{$search}%"
                    )
                    ->orWhereRaw(
                        "
                        LOWER(
                            REPLACE(
                                REPLACE(code, ' ', ''),
                                '-',
                                ''
                            )
                        ) LIKE ?
                        ",
                        [
                            "%{$normalizedSearch}%"
                        ]
                    );
            })
            ->with([
                'brandModel',
                'categoryModel',
            ])
            ->limit(50)
            ->get([
                'id',
                'name',
                'code',
                'brand',
                'category',
            ]);

        return $products
            ->flatMap(
                function ($product) use (
                    $search,
                    $normalizedSearch
                ) {
                    return collect(
                        preg_split(
                            '/\r\n|\r|\n/',
                            (string) $product->code
                        )
                    )
                        ->map(
                            fn ($line) =>
                                trim($line)
                        )
                        ->filter()
                        ->filter(
                            function ($line) use (
                                $search,
                                $normalizedSearch
                            ) {
                                $lowerLine = strtolower(
                                    $line
                                );

                                $lowerSearch = strtolower(
                                    $search
                                );

                                /*
                                 * Normal search.
                                 */
                                if (
                                    str_contains(
                                        $lowerLine,
                                        $lowerSearch
                                    )
                                ) {
                                    return true;
                                }

                                /*
                                 * Normalized search.
                                 */
                                $normalizedLine = strtolower(
                                    str_replace(
                                        [' ', '-'],
                                        '',
                                        $line
                                    )
                                );

                                return str_contains(
                                    $normalizedLine,
                                    $normalizedSearch
                                );
                            }
                        )
                        ->mapWithKeys(
                            function ($line) use (
                                $product
                            ) {
                                return [
                                    "{$product->id}::{$line}"
                                        => self::partNoSearchLabel(
                                            $product,
                                            $line
                                        ),
                                ];
                            }
                        );
                }
            )
            ->all();
    }

    // ============================================================
    // FORM
    // ============================================================

    public static function configure(
        Schema $schema
    ): Schema {
        return $schema->components([

            // ====================================================
            // HEADER
            // ====================================================

            DatePicker::make('date')
                ->default(now())
                ->required(),

            TextInput::make('purchase_inv_no')
                ->label('Purchase inv no')
                ->default(
                    fn () =>
                        PurchaseInvoiceNumberService::generate()
                )
                ->disabled()
                ->dehydrated(true),

            Select::make('supplier_id')
                ->label('Supplier')
                ->options(
                    fn () =>
                        Supplier::query()
                            ->where(
                                'status',
                                1
                            )
                            ->whereNotNull(
                                'company_name'
                            )
                            ->where(
                                'company_name',
                                '!=',
                                ''
                            )
                            ->orderBy(
                                'company_name'
                            )
                            ->pluck(
                                'company_name',
                                'id'
                            )
                )
                ->searchable()
                ->required(),

            TextInput::make('reference_no')
                ->label('Invoice No')
                ->required()
                ->unique(
                    ignoreRecord: true
                ),

            // ====================================================
            // ITEMS
            // ====================================================

            Repeater::make('items')
                ->relationship()

                ->live()

                ->afterStateUpdated(
                    function (
                        $state,
                        $set,
                        $get
                    ) {
                        self::recalculateItems(
                            $state ?? [],
                            $set,
                            $get
                        );
                    }
                )

                ->table([
                    TableColumn::make('Part No')
                        ->width('260px'),

                    TableColumn::make('Description')
                        ->width('220px'),

                    TableColumn::make('Brand')
                        ->width('120px'),

                    TableColumn::make('Qty')
                        ->width('90px'),

                    TableColumn::make('Unit Price')
                        ->width('150px'),

                    TableColumn::make('Total')
                        ->width('150px'),

                    TableColumn::make('Notes')
                        ->width('180px'),
                ])

                ->compact()

                ->schema([

                    // ============================================
                    // HIDDEN FIELDS
                    // ============================================

                    Hidden::make('product_id')
                        ->dehydrated(true),

                    Hidden::make('category')
                        ->dehydrated(true),

                    // ============================================
                    // PART NUMBER
                    // ============================================

                    Select::make('part_no')
                        ->label('Part No')
                        ->allowHtml()
                        ->extraAttributes([
                            'style' => 'font-family:monospace; font-weight:600; color:#000;',
                        ])

                        ->getSearchResultsUsing(
                            fn (
                                string $search
                            ): array =>
                                self::partNoSearch(
                                    $search
                                )
                        )

                        ->getOptionLabelUsing(
                            function (
                                $value
                            ): ?string {
                                if (! $value) {
                                    return null;
                                }

                                /*
                                 * Normal stored value.
                                 */
                                if (
                                    ! str_contains(
                                        (string) $value,
                                        '::'
                                    )
                                ) {
                                    return (string) $value;
                                }

                                /*
                                 * Composite value from search dropdown.
                                 */
                                [
                                    ,
                                    $partNo
                                ] = explode(
                                    '::',
                                    (string) $value,
                                    2
                                );

                                return $partNo;
                            }
                        )

                        ->searchable()

                        ->live()

                        ->dehydrated(true)

                        ->required()

                        ->placeholder(
                            'Paste or search part number...'
                        )

                        ->afterStateUpdated(
                            function (
                                $state,
                                $set,
                                $get
                            ): void {
                                if (! $state) {
                                    return;
                                }

                                /*
                                 * Search result:
                                 *
                                 * productId::partNumber
                                 */
                                if (
                                    ! str_contains(
                                        (string) $state,
                                        '::'
                                    )
                                ) {
                                    return;
                                }

                                [
                                    $productId,
                                    $codeLine
                                ] = explode(
                                    '::',
                                    (string) $state,
                                    2
                                );

                                $product = self::findProductById(
                                    $productId
                                );

                                if (! $product) {
                                    return;
                                }

                                /*
                                 * Fill product information.
                                 */
                                self::fillProductFields(
                                    $product,
                                    $set
                                );

                                /*
                                 * Store ONLY the actual part number in the database.
                                 */
                                $set(
                                    'part_no',
                                    trim($codeLine)
                                );
                            }
                        )

                        /*
                         * IMPORTANT FIX:
                         *
                         * When editing an existing purchase, use product_id FIRST.
                         *
                         * Do NOT try to find the product from part_no unless product_id is missing.
                         */
                        ->afterStateHydrated(
                            function (
                                $state,
                                $set,
                                $get
                            ): void {
                                $productId = $get(
                                    'product_id'
                                );

                                /*
                                 * --------------------------------
                                 * FIRST:
                                 * Restore directly from product_id.
                                 * --------------------------------
                                 */
                                $product = self::findProductById(
                                    $productId
                                );

                                /*
                                 * --------------------------------
                                 * FALLBACK:
                                 * Find using part number.
                                 * --------------------------------
                                 */
                                if (! $product) {
                                    $partNo = (string) $state;

                                    if (
                                        str_contains(
                                            $partNo,
                                            '::'
                                        )
                                    ) {
                                        $partNo = explode(
                                            '::',
                                            $partNo,
                                            2
                                        )[1];
                                    }

                                    $partNo = trim(
                                        $partNo
                                    );

                                    $product = self::findProductByPartNo(
                                        $partNo
                                    );
                                }

                                if (! $product) {
                                    return;
                                }

                                /*
                                 * Keep plain part number.
                                 */
                                $partNo = (string) $state;

                                if (
                                    str_contains(
                                        $partNo,
                                        '::'
                                    )
                                ) {
                                    $partNo = explode(
                                        '::',
                                        $partNo,
                                        2
                                    )[1];
                                }

                                $set(
                                    'part_no',
                                    trim($partNo)
                                );

                                /*
                                 * Restore ALL product data.
                                 */
                                self::fillProductFields(
                                    $product,
                                    $set
                                );
                            }
                        ),

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

                        /*
                         * IMPORTANT:
                         *
                         * Previously this was:
                         *
                         * ->dehydrated(false)
                         *
                         * which meant the description was NEVER saved.
                         */
                        ->dehydrated(true),

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
                        ->integer()
                        ->minValue(1)
                        ->default(1)

                        ->extraInputAttributes([
                            'onfocus' =>
                                'this.select();',
                        ])

                        ->live(
                            onBlur: true
                        ),

                    // ============================================
                    // UNIT PRICE
                    // ============================================

                    TextInput::make('price')
                        ->label('Unit Price')
                        ->inputMode('decimal')
                        ->prefix('Rp')

                        ->formatStateUsing(
                            fn ($state) =>
                                self::formatCurrency(
                                    $state
                                )
                        )

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

                        ->live(
                            onBlur: true
                        )

                        ->dehydrateStateUsing(
                            fn ($state) =>
                                self::parseNumber(
                                    $state
                                )
                        ),

                    // ============================================
                    // ROW TOTAL - HIDDEN
                    // ============================================

                    Hidden::make('grand_total')
                        ->dehydrateStateUsing(
                            function ($get) {
                                $qty =
                                    self::parseNumber(
                                        $get('qty') ?? 0
                                    );

                                $price =
                                    self::parseNumber(
                                        $get('price') ?? 0
                                    );

                                return $qty * $price;
                            }
                        )
                        ->dehydrated(true),

                    // ============================================
                    // ROW TOTAL - DISPLAY
                    // ============================================

                    Placeholder::make(
                        'total_display'
                    )
                        ->label('Total')
                        ->content(
                            function ($get) {
                                $qty =
                                    self::parseNumber(
                                        $get('qty') ?? 0
                                    );

                                $price =
                                    self::parseNumber(
                                        $get('price') ?? 0
                                    );

                                $total =
                                    $qty * $price;

                                return 'Rp ' .
                                    self::formatCurrency(
                                        $total
                                    );
                            }
                        ),

                    // ============================================
                    // NOTES
                    // ============================================

                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder(
                            'Remarks...'
                        )
                        ->rows(1),
                ])

                /*
                 * Recalculate when editing.
                 */
                ->afterStateHydrated(
                    function (
                        $state,
                        $set,
                        $get
                    ) {
                        self::recalculateItems(
                            $state ?? [],
                            $set,
                            $get
                        );
                    }
                )

                /*
                 * Add Product button.
                 */
                ->addAction(
                    fn ($action) => $action
                        ->label('Add Product')
                        ->icon(
                            'heroicon-m-plus'
                        )
                        ->color('warning')
                )

                ->columnSpanFull(),

            // ====================================================
            // TOTALS
            // ====================================================

            Hidden::make('grand_total')
                ->default(0)
                ->dehydrateStateUsing(
                    fn ($state) =>
                        self::parseNumber(
                            $state
                        )
                )
                ->dehydrated(true),

            Placeholder::make(
                'subtotal_display'
            )
                ->label('Subtotal')
                ->content(
                    fn ($get) =>
                        'Rp ' .
                        self::formatCurrency(
                            $get(
                                'grand_total',
                                isAbsolute: true
                            ) ?? 0
                        )
                )
                ->columnStart(2),

            // ====================================================
            // TAX
            // ====================================================

            TextInput::make('tax')
                ->label('Tax (%)')
                ->numeric()
                ->default(0)

                ->extraInputAttributes([
                    'onfocus' =>
                        'this.select();',
                ])

                ->live(
                    onBlur: true
                )

                ->columnStart(2)

                ->afterStateUpdated(
                    fn (
                        $state,
                        $set,
                        $get
                    ) =>
                        self::recalculateFinal(
                            $set,
                            $get
                        )
                ),

            // ====================================================
            // DISCOUNT
            // ====================================================

            TextInput::make('discount')
                ->label('Discount (Rp)')
                ->inputMode('decimal')
                ->prefix('Rp')
                ->default('0,00')

                ->formatStateUsing(
                    fn ($state) =>
                        self::formatCurrency(
                            $state
                        )
                )

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

                ->live(
                    onBlur: true
                )

                ->dehydrateStateUsing(
                    fn ($state) =>
                        self::parseNumber(
                            $state
                        )
                )

                ->columnStart(2)

                ->afterStateUpdated(
                    fn (
                        $state,
                        $set,
                        $get
                    ) =>
                        self::recalculateFinal(
                            $set,
                            $get
                        )
                ),

            // ====================================================
            // FINAL TOTAL
            // ====================================================

            Hidden::make('final_total')
                ->default(0)
                ->dehydrateStateUsing(
                    fn ($state) =>
                        self::parseNumber(
                            $state
                        )
                )
                ->dehydrated(true),

            Placeholder::make(
                'final_total_display'
            )
                ->label('Final Total')
                ->content(
                    fn ($get) =>
                        'Rp ' .
                        self::formatCurrency(
                            $get(
                                'final_total',
                                isAbsolute: true
                            ) ?? 0
                        )
                )
                ->columnStart(2),
        ]);
    }
}
