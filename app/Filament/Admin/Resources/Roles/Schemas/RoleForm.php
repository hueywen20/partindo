<?php

namespace App\Filament\Admin\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    private static array $modules = [
        // ── Transactions ──────────────────────────────────────────────────────
        'Penyesuaian Stok (+)' => [
            'view_stock_adjustment_plus', 'create_stock_adjustment_plus',
            'edit_stock_adjustment_plus', 'delete_stock_adjustment_plus',
        ],
        'Penyesuaian Stok (-)' => [
            'view_stock_adjustment_minus', 'create_stock_adjustment_minus',
            'edit_stock_adjustment_minus', 'delete_stock_adjustment_minus',
        ],
        'Assembly' => [
            'view_assembly', 'create_assembly', 'edit_assembly', 'delete_assembly',
        ],
        'De-Assembly' => [
            'view_de_assembly', 'create_de_assembly', 'edit_de_assembly', 'delete_de_assembly',
        ],
        'Pembelian' => [
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
        ],
        'Penjualan' => [
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
            'open_sales_to_customer',
        ],
        'Retur Pembelian' => [
            'view_purchase_returns', 'create_purchase_returns',
            'edit_purchase_returns', 'delete_purchase_returns',
        ],
        'Retur Penjualan' => [
            'view_sale_returns', 'create_sale_returns',
            'edit_sale_returns', 'delete_sale_returns',
        ],
        'Quotation' => [
            'view_quotations', 'create_quotations', 'edit_quotations', 'delete_quotations',
        ],

        // ── Master Data ───────────────────────────────────────────────────────
        'Customer' => [
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
        ],
        'Supplier' => [
            'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
        ],
        'Daftar Barang' => [
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_product_list', 'view_stock_card',
        ],

        // ── Finance ───────────────────────────────────────────────────────────
        'Penagihan Piutang' => [
            'view_receivables', 'create_receivables', 'edit_receivables', 'delete_receivables',
        ],
        'Pembayaran Hutang' => [
            'view_payables', 'create_payables', 'edit_payables', 'delete_payables',
        ],
        'Tutup Buku' => [
            'view_closing', 'close_per_year',
        ],
        'Kode Konversi' => [
            'view_conversion_codes', 'create_conversion_codes',
            'edit_conversion_codes', 'delete_conversion_codes',
        ],

        // ── Settings ──────────────────────────────────────────────────────────
        'User' => [
            'view_users', 'create_users', 'edit_users', 'delete_users',
        ],
        'Grup User' => [
            'view_user_groups', 'create_user_groups', 'edit_user_groups', 'delete_user_groups',
        ],
        'Pengaturan Lain-Lain' => [
            'view_other_settings', 'manage_other_settings',
        ],

        // ── Reports ───────────────────────────────────────────────────────────
        'Laporan Stok' => [
            'report_stock_minimum', 'report_stock_per_date', 'report_stock_expired',
            'report_stock_card_per_period', 'report_most_active_product', 'report_product_list',
        ],
        'Laporan Pembelian' => [
            'report_purchase_per_period_per_supplier', 'report_purchase_due_date',
            'report_purchase_per_area', 'report_purchase_per_month',
        ],
        'Laporan Penjualan' => [
            'report_sale_per_period_per_customer', 'report_sale_due_date',
            'report_sale_per_area', 'report_sale_per_month',
        ],
        'Laporan Retur Pembelian' => [
            'report_purchase_return_per_period_per_supplier',
        ],
        'Laporan Retur Penjualan' => [
            'report_sale_return_per_period_per_customer',
        ],
        'Laporan Penagihan Piutang' => [
            'report_receivables', 'report_unpaid_invoices', 'report_long_term_payable',
        ],
        'Laporan Pembayaran Hutang' => [
            'report_payables', 'report_unpaid_purchase_orders',
        ],
        'Laporan Laba Rugi' => [
            'report_profit_loss_per_period', 'report_profit_loss_per_invoice',
            'report_profit_loss_per_month',
        ],
        'Laporan Aktivitas' => [
            'report_activity_log',
        ],
    ];

    private static array $labels = [
        'view_stock_adjustment_plus'    => 'Lihat',
        'create_stock_adjustment_plus'  => 'Tambah',
        'edit_stock_adjustment_plus'    => 'Ubah',
        'delete_stock_adjustment_plus'  => 'Hapus',
        'view_stock_adjustment_minus'   => 'Lihat',
        'create_stock_adjustment_minus' => 'Tambah',
        'edit_stock_adjustment_minus'   => 'Ubah',
        'delete_stock_adjustment_minus' => 'Hapus',
        'view_assembly'    => 'Lihat', 'create_assembly'  => 'Tambah',
        'edit_assembly'    => 'Ubah',  'delete_assembly'  => 'Hapus',
        'view_de_assembly'    => 'Lihat', 'create_de_assembly'  => 'Tambah',
        'edit_de_assembly'    => 'Ubah',  'delete_de_assembly'  => 'Hapus',
        'view_purchases'    => 'Lihat', 'create_purchases'  => 'Tambah',
        'edit_purchases'    => 'Ubah',  'delete_purchases'  => 'Hapus',
        'view_sales'             => 'Lihat', 'create_sales'  => 'Tambah',
        'edit_sales'             => 'Ubah',  'delete_sales'  => 'Hapus',
        'open_sales_to_customer' => 'Buka Akses ke Customer',
        'view_purchase_returns'    => 'Lihat', 'create_purchase_returns'  => 'Tambah',
        'edit_purchase_returns'    => 'Ubah',  'delete_purchase_returns'  => 'Hapus',
        'view_sale_returns'    => 'Lihat', 'create_sale_returns'  => 'Tambah',
        'edit_sale_returns'    => 'Ubah',  'delete_sale_returns'  => 'Hapus',
        'view_quotations'    => 'Lihat', 'create_quotations'  => 'Tambah',
        'edit_quotations'    => 'Ubah',  'delete_quotations'  => 'Hapus',
        'view_customers'    => 'Lihat', 'create_customers'  => 'Tambah',
        'edit_customers'    => 'Ubah',  'delete_customers'  => 'Hapus',
        'view_suppliers'    => 'Lihat', 'create_suppliers'  => 'Tambah',
        'edit_suppliers'    => 'Ubah',  'delete_suppliers'  => 'Hapus',
        'view_products'     => 'Lihat', 'create_products'   => 'Tambah',
        'edit_products'     => 'Ubah',  'delete_products'   => 'Hapus',
        'view_product_list' => 'Lihat Daftar Barang',
        'view_stock_card'   => 'Lihat Kartu Stok',
        'view_receivables'    => 'Lihat', 'create_receivables'  => 'Tambah',
        'edit_receivables'    => 'Ubah',  'delete_receivables'  => 'Hapus',
        'view_payables'    => 'Lihat', 'create_payables'  => 'Tambah',
        'edit_payables'    => 'Ubah',  'delete_payables'  => 'Hapus',
        'view_closing'   => 'Lihat',
        'close_per_year' => 'Tutup Buku per Tahun',
        'view_conversion_codes'    => 'Lihat', 'create_conversion_codes'  => 'Tambah',
        'edit_conversion_codes'    => 'Ubah',  'delete_conversion_codes'  => 'Hapus',
        'view_users'    => 'Lihat', 'create_users'  => 'Tambah',
        'edit_users'    => 'Ubah',  'delete_users'  => 'Hapus',
        'view_user_groups'    => 'Lihat', 'create_user_groups'  => 'Tambah',
        'edit_user_groups'    => 'Ubah',  'delete_user_groups'  => 'Hapus',
        'view_other_settings'   => 'Lihat',
        'manage_other_settings' => 'Kelola Pengaturan',
        'report_stock_minimum'         => 'Stok Minimum',
        'report_stock_per_date'        => 'Stok per Tanggal',
        'report_stock_expired'         => 'Stok Expired',
        'report_stock_card_per_period' => 'Kartu Stok per Periode',
        'report_most_active_product'   => 'Barang Paling Aktif',
        'report_product_list'          => 'Daftar Barang',
        'report_purchase_per_period_per_supplier' => 'per Periode per Supplier',
        'report_purchase_due_date'                => 'per Tanggal Jatuh Tempo',
        'report_purchase_per_area'                => 'per Area / Kota',
        'report_purchase_per_month'               => 'per Bulan',
        'report_sale_per_period_per_customer' => 'per Periode per Customer',
        'report_sale_due_date'                => 'per Tanggal Jatuh Tempo',
        'report_sale_per_area'                => 'per Area / Kota',
        'report_sale_per_month'               => 'per Bulan',
        'report_purchase_return_per_period_per_supplier' => 'per Periode per Supplier',
        'report_sale_return_per_period_per_customer'     => 'per Periode per Customer',
        'report_receivables'            => 'Penagihan Piutang',
        'report_unpaid_invoices'        => 'Faktur Belum Lunas',
        'report_long_term_payable'      => 'Jangka Waktu Pelunasan Hutang',
        'report_payables'               => 'Pembayaran Hutang',
        'report_unpaid_purchase_orders' => 'Bon Pembelian Belum Lunas',
        'report_profit_loss_per_period'  => 'per Periode',
        'report_profit_loss_per_invoice' => 'per Faktur',
        'report_profit_loss_per_month'   => 'per Bulan',
        'report_activity_log'            => 'Laporan Aktivitas',
    ];

    public static function configure(Schema $schema): Schema
    {
        $existingPerms = Permission::pluck('name')->flip()->toArray();

        // Build per-module options (only perms that exist in DB)
        $moduleOptions = [];
        foreach (self::$modules as $heading => $permNames) {
            $options = [];
            foreach ($permNames as $perm) {
                if (array_key_exists($perm, $existingPerms)) {
                    $options[$perm] = self::$labels[$perm] ?? ucfirst(str_replace('_', ' ', $perm));
                }
            }
            if (! empty($options)) {
                $moduleOptions[$heading] = $options;
            }
        }

        // Build one Section per module
        $moduleSections = [];
        foreach ($moduleOptions as $heading => $options) {
            $fieldName = 'perm_' . md5($heading);

            $moduleSections[] = Section::make($heading)
                ->compact()
                ->schema([
                    CheckboxList::make($fieldName)
                        ->hiddenLabel()
                        ->options($options)
                        ->columns(1)        // top-to-bottom stacking
                        ->bulkToggleable()
                        ->afterStateHydrated(function ($component, $record) use ($options) {
                            if (! $record) {
                                $component->state([]);
                                return;
                            }
                            $rolePerms = $record->load('permissions')
                                ->permissions
                                ->pluck('name')
                                ->toArray();
                            $component->state(
                                array_values(array_intersect(array_keys($options), $rolePerms))
                            );
                        })
                        ->dehydrated(false),
                ]);
        }

        // Chunk modules into rows of 3
        $rows = array_chunk($moduleSections, 3);

        $components = [
            TextInput::make('name')
                ->label('Nama Role')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->columnSpanFull(),
        ];

        foreach ($rows as $row) {
            $components[] = Grid::make(3)
                ->columnSpanFull()
                ->schema($row);
        }

        return $schema->components($components);
    }

    /**
     * Called from EditRole and CreateRole pages to sync all checked permissions.
     * Collects every perm_* field from form data and syncs to the role.
     */
    public static function syncPermissions(\Spatie\Permission\Models\Role $role, array $data): void
    {
        $selected = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'perm_') && is_array($value)) {
                array_push($selected, ...$value);
            }
        }

        $role->syncPermissions($selected);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}