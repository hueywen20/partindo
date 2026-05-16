<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create super_admin role (guard must match your app — 'web' is Laravel default)
        $role = Role::firstOrCreate([
            'name'       => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Assign to the first admin user (admin@example.com from DefaultSeeder)
        $user = User::where('email', 'admin@example.com')->first();

        if ($user && ! $user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        // Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $this->command->info('super_admin role created and assigned to admin@example.com');
    }
}