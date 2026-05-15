<?php

namespace App\Filament\Admin\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
// use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Filament\Schemas\Components\Section;


class RoleForm
{
    /**
     * Permission groups matching the UI in the screenshot.
     * Key   = section heading shown to the user
     * Value = permission name prefixes to include in that group
     */
    private static array $groups = [
        // ── Transactions ────────────────────────────────────────────────
        'Penyesuaian Stok (+)' => 'stock_adjustment_plus',
        'Penyesuaian Stok (-)' => 'stock_adjustment_minus',
        'Assembly'             => 'assembly',
        'De-Assembly'          => 'de_assembly',
        'Pembelian'            => 'purchases',
        'Penjualan'            => 'sales',
        'Retur Pembelian'      => 'purchase_returns',
        'Retur Penjualan'      => 'sale_returns',
        'Quotation'            => 'quotations',

        // ── Master Data ─────────────────────────────────────────────────
        'Customer'             => 'customers',
        'Supplier'             => 'suppliers',
        'Daftar Barang'        => 'products',

        // ── Finance ─────────────────────────────────────────────────────
        'Penagihan Piutang'    => 'receivables',
        'Pembayaran Hutang'    => 'payables',
        'Tutup Buku'           => 'closing',
        'Kode Konversi'        => 'conversion_codes',

        // ── Settings ────────────────────────────────────────────────────
        'User'                 => 'users',
        'Grup User'            => 'user_groups',
        'Pengaturan Lain-Lain' => 'other_settings',

        // ── Reports ─────────────────────────────────────────────────────
        'L. Stok'              => 'report_stock',
        'L. Pembelian'         => 'report_purchases',
        'L. Penjualan'         => 'report_sales',
        'L. Retur Pembelian'   => 'report_purchase_returns',
        'L. Retur Penjualan'   => 'report_sale_returns',
        'L. Penagihan Piutang' => 'report_receivables',
        'L. Pembayaran Hutang' => 'report_payables',
        'L. Laba Rugi'         => 'report_profit_loss',
        'L. Aktivitas'         => 'report_activity',
    ];

    /** Human-readable labels for each permission */
    private static array $labels = [
        // generic CRUD
        'view'   => 'Lihat Daftar dan Detail',
        'create' => 'Tambah',
        'edit'   => 'Ubah',
        'delete' => 'Hapus',

        // sales special
        'open_sales_to_customer' => 'Buka Akses Penjualan ke Customer',

        // product stock
        'view_product_list' => 'Lihat Daftar Barang',
        'view_stock_card'   => 'Lihat Kartu Stok',

        // closing
        'close_per_year'           => 'Tutup Buku per Tahun',
        'manage_other_settings'    => 'Pengaturan Lain-Lain',

        // report stock
        'report_stock_minimum'           => 'Stok Minimum',
        'report_stock_per_date'          => 'Stok per Tanggal',
        'report_stock_expired'           => 'Stok Expired',
        'report_stock_card_per_period'   => 'Kartu Stok per Periode',
        'report_most_active_product'     => 'Barang Paling Aktif per Periode',
        'report_product_list'            => 'Daftar Barang',

        // report purchases
        'report_purchase_per_period_per_supplier' => 'Pembelian per Periode per Supplier',
        'report_purchase_due_date'                => 'Pembelian per Tanggal Jatuh Tempo',
        'report_purchase_per_area'                => 'Pembelian per Area / Kota',
        'report_purchase_per_month'               => 'Pembelian per Bulan',

        // report sales
        'report_sale_per_period_per_customer' => 'Penjualan per Periode per Customer',
        'report_sale_due_date'                => 'Penjualan per Tanggal Jatuh Tempo',
        'report_sale_per_area'                => 'Penjualan per Area / Kota',
        'report_sale_per_month'               => 'Penjualan per Bulan',

        // report returns
        'report_purchase_return_per_period_per_supplier' => 'Retur Pembelian per Periode per Supplier',
        'report_sale_return_per_period_per_customer'     => 'Retur Penjualan per Periode per Customer',

        // report receivables / payables
        'report_receivables'          => 'Penagihan Piutang',
        'report_unpaid_invoices'      => 'Faktur Penjualan yang Belum Lunas',
        'report_long_term_payable'    => 'L.Jangka Waktu Pelunasan Hutang',
        'report_payables'             => 'Pembayaran Hutang',
        'report_unpaid_purchase_orders' => 'Bon Pembelian yang Belum Lunas',

        // report profit/loss
        'report_profit_loss_per_period'  => 'Laba/Rugi per Periode',
        'report_profit_loss_per_invoice' => 'Laba/Rugi per Faktur',
        'report_profit_loss_per_month'   => 'Laba/Rugi per Bulan',

        // activity
        'report_activity_log' => 'Laporan Aktivitas',
    ];

    public static function configure(Schema $schema): Schema
    {
        // Load all permissions that actually exist in DB
        $existingPerms = Permission::orderBy('name')->pluck('name')->toArray();

        $sections = [];

        foreach (self::$groups as $heading => $prefix) {
            // Find permissions for this group
            $groupPerms = array_filter($existingPerms, fn($p) => str_starts_with($p, $prefix));

            if (empty($groupPerms)) {
                continue;
            }

            // Build labelled options
            $options = [];
            foreach ($groupPerms as $perm) {
                $options[$perm] = self::labelFor($perm, $prefix);
            }

            $groupPermKeys = array_keys($options);

            $sections[] = Section::make($heading)
                ->collapsible()
                ->compact()
                ->columns(1)
                ->schema([
                    CheckboxList::make("perms_{$prefix}")
                        ->label('Pilih Semua')
                        ->relationship('permissions', 'name')
                        ->options($options)
                        ->columns(2)
                        ->bulkToggleable()   // adds the "Select All" toggle Filament provides
                        ->hiddenLabel(),
                ]);
        }

        return $schema->components(array_merge([
            TextInput::make('name')
                ->label('Nama Grup User')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->columnSpanFull(),
        ], $sections));
    }

    private static function labelFor(string $perm, string $prefix): string
    {
        // Direct match in labels map
        if (isset(self::$labels[$perm])) {
            return self::$labels[$perm];
        }

        // Strip prefix and try action-only match (view_, create_, edit_, delete_)
        $action = str_replace("{$prefix}_", '', $perm);
        $action = explode('_', $action)[0]; // e.g. "view"

        return self::$labels[$action] ?? ucfirst(str_replace('_', ' ', $perm));
    }
}
