<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ─── All permissions grouped by module ───────────────────────────────
        $modules = [

            // ── Transactions ─────────────────────────────────────────────────
            'purchases' => [
                'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
            ],
            'sales' => [
                'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
                'open_sales_to_customer',
            ],
            'quotations' => [
                'view_quotations', 'create_quotations', 'edit_quotations', 'delete_quotations',
            ],
            'purchase_returns' => [
                'view_purchase_returns', 'create_purchase_returns', 'edit_purchase_returns', 'delete_purchase_returns',
            ],
            'sale_returns' => [
                'view_sale_returns', 'create_sale_returns', 'edit_sale_returns', 'delete_sale_returns',
            ],
            'stock_adjustment_plus' => [
                'view_stock_adjustment_plus', 'create_stock_adjustment_plus', 'edit_stock_adjustment_plus', 'delete_stock_adjustment_plus',
            ],
            'stock_adjustment_minus' => [
                'view_stock_adjustment_minus', 'create_stock_adjustment_minus', 'edit_stock_adjustment_minus', 'delete_stock_adjustment_minus',
            ],
            'assembly' => [
                'view_assembly', 'create_assembly', 'edit_assembly', 'delete_assembly',
            ],
            'de_assembly' => [
                'view_de_assembly', 'create_de_assembly', 'edit_de_assembly', 'delete_de_assembly',
            ],

            // ── Master Data ───────────────────────────────────────────────────
            'customers' => [
                'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            ],
            'suppliers' => [
                'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
            ],
            'products' => [
                'view_products', 'create_products', 'edit_products', 'delete_products',
            ],
            'product_stock' => [
                'view_product_list', 'view_stock_card',
            ],

            // ── Finance ───────────────────────────────────────────────────────
            'receivables' => [
                'view_receivables', 'create_receivables', 'edit_receivables', 'delete_receivables',
            ],
            'payables' => [
                'view_payables', 'create_payables', 'edit_payables', 'delete_payables',
            ],
            'closing' => [
                'view_closing', 'close_per_year',
            ],
            'conversion_codes' => [
                'view_conversion_codes', 'create_conversion_codes', 'edit_conversion_codes', 'delete_conversion_codes',
            ],

            // ── Settings ──────────────────────────────────────────────────────
            'users' => [
                'view_users', 'create_users', 'edit_users', 'delete_users',
            ],
            'user_groups' => [
                'view_user_groups', 'create_user_groups', 'edit_user_groups', 'delete_user_groups',
            ],
            'other_settings' => [
                'view_other_settings', 'manage_other_settings',
            ],
            'roles_permissions' => [
                'view_roles_permissions', 'manage_roles_permissions',
            ],

            // ── Reports ───────────────────────────────────────────────────────
            'report_stock' => [
                'report_stock_minimum', 'report_stock_per_date', 'report_stock_expired',
                'report_stock_card_per_period', 'report_most_active_product', 'report_product_list',
            ],
            'report_purchases' => [
                'report_purchase_per_period_per_supplier', 'report_purchase_due_date',
                'report_purchase_per_area', 'report_purchase_per_month',
            ],
            'report_sales' => [
                'report_sale_per_period_per_customer', 'report_sale_due_date',
                'report_sale_per_area', 'report_sale_per_month',
            ],
            'report_purchase_returns' => [
                'report_purchase_return_per_period_per_supplier',
            ],
            'report_sale_returns' => [
                'report_sale_return_per_period_per_customer',
            ],
            'report_receivables' => [
                'report_receivables', 'report_unpaid_invoices', 'report_long_term_payable',
            ],
            'report_payables' => [
                'report_payables', 'report_unpaid_purchase_orders',
            ],
            'report_profit_loss' => [
                'report_profit_loss_per_period', 'report_profit_loss_per_invoice', 'report_profit_loss_per_month',
            ],
            'report_activity' => [
                'report_activity_log',
            ],
        ];

        // Flatten and create all permissions
        $allPermissions = [];
        foreach ($modules as $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm]);
                $allPermissions[] = $perm;
            }
        }

        // ─── Roles ────────────────────────────────────────────────────────────
        $roles = [
            'Admin' => $allPermissions,

            'Finance' => [
                'view_sales', 'view_purchases',
                'view_receivables', 'view_payables',
                'report_profit_loss_per_period', 'report_profit_loss_per_invoice', 'report_profit_loss_per_month',
                'report_receivables', 'report_payables',
            ],

            'Store' => [
                'view_products', 'view_product_list', 'view_stock_card',
                'view_stock_adjustment_plus', 'create_stock_adjustment_plus',
                'view_stock_adjustment_minus', 'create_stock_adjustment_minus',
                'report_stock_minimum', 'report_stock_per_date', 'report_stock_expired',
            ],

            'Sales' => [
                'view_customers', 'create_customers', 'edit_customers',
                'view_products',
                'view_sales', 'create_sales', 'edit_sales',
                'view_quotations', 'create_quotations', 'edit_quotations',
                'view_sale_returns', 'create_sale_returns',
                'report_sale_per_period_per_customer', 'report_sale_per_month',
            ],
        ];

        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            // $role->syncPermissions($rolePerms);
            $role->syncPermissions(Permission::whereIn('name', $rolePerms)->get());
        }

        // IMPORTANT: clear Spatie cache
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();
    }
}
