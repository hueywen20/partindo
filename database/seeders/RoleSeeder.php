<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Customers 
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            // Supplier
            'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
            // Purchases
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
            // Products
            'view_products', 'create_products', 'edit_products', 'delete_products',
            // Quotations
            'view_quotations', 'create_quotations', 'edit_quotations', 'delete_quotations',
            // Purchase Oders
            'view_purchase_orders', 'create_purchase_orders', 'edit_purchase_orders', 'delete_purchase_orders',
            // Sales
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
            // Reports / Dashboard
            'view_dashboard', 'view_reports',
            // Users
            'manage_users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Define roles + their permissions
        $roles = [
            'Admin' => $permissions, // all
            'Finance' => [
               'view_sales', 'view_dashboard', 'view_reports',
               'view_purchases',
            ],
            'Store' => [
                'view_products',
            ],
            'Sales' => [
                'view_products', 'view_sales', 'create_sales',
                'view_quotations', 'create_quotations',
                'view_customers'
            ],
            
        ];

        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePerms);
        }
    }
}
