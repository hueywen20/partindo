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
         // ── Master Data ───────────────────────────────────────────────────────
        'Customers' => [
            'view_customer', 'create_customer', 'edit_customer', 'delete_customer',
        ],
        'Suppliers' => [
            'view_supplier', 'create_supplier', 'edit_supplier', 'delete_supplier',
        ],
        'Brands' => [
            'view_brand', 'create_brand', 'edit_brand', 'delete_brand',
        ],
        'UOMs' => [
            'view_uom', 'create_uom', 'edit_uom', 'delete_uom',
        ],
        'Product Locations' => [
            'view_product_location', 'create_product_location', 'edit_product_location', 'delete_product_location',
        ],

        // -- Inventory ─────────────────────────────────────────────────────────
        'Products' => [
            'view_product', 'create_product', 'edit_product', 'delete_product',
        ],

        // ── Transactions ──────────────────────────────────────────────────────
   
        // -- Sales & Purchases ─────────────────────────────────────────────────────
        'Quotations' => [
            'view_quotation', 'create_quotation', 'edit_quotation', 'delete_quotation',
        ],
        'Purchase Orders' => [
            'view_purchase_orders', 'create_purchase_orders', 'edit_purchase_orders', 'delete_purchase_orders',
        ],
        'Sales' => [
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
        ],
        'Purchases' => [
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
        ],

        // ── Finance & Reports ──────────────────────────────────────────────────
       'Reports' => [
           'view_reports', 'create_reports', 'edit_reports', 'delete_reports',
       ],

        // ── Settings ──────────────────────────────────────────────────────────
        'User' => [
            'view_users', 'create_users', 'edit_users', 'delete_users',
        ],
        'Role & Permission' => [
            'view_roles_permissions', 'manage_roles_permissions',
        ],
        'Audit Log' => [
            'view_audit',
        ],

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