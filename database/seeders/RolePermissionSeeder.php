<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ───────────────────────────────────────
        $permissions = [
            ['name' => 'Full Access',          'slug' => 'full_access',       'description' => 'Unrestricted platform access'],
            ['name' => 'Manage Company',       'slug' => 'manage_company',    'description' => 'Full access to own company data'],
            ['name' => 'Manage Inventory',     'slug' => 'manage_inventory',  'description' => 'Products, materials, BOM, production, inventory'],
            ['name' => 'Manage Sales',         'slug' => 'manage_sales',      'description' => 'Sales orders, customers, payments, product costs'],
            ['name' => 'View Reports',         'slug' => 'view_reports',      'description' => 'Access reports and analytics'],
            ['name' => 'View Dashboard',       'slug' => 'view_dashboard',    'description' => 'Access the main dashboard'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // ── Roles ─────────────────────────────────────────────
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'superadmin',
                'description' => 'Platform-wide administrator',
                'permissions' => ['full_access'],
            ],
            [
                'name'        => 'Company Admin',
                'slug'        => 'admin',
                'description' => 'Full access to own company',
                'permissions' => ['manage_company', 'manage_inventory', 'manage_sales', 'view_reports', 'view_dashboard'],
            ],
            [
                'name'        => 'Sales Admin',
                'slug'        => 'sales_admin',
                'description' => 'Access to sales, orders, customers and payments',
                'permissions' => ['manage_sales', 'view_reports', 'view_dashboard'],
            ],
            [
                'name'        => 'Inventory Admin',
                'slug'        => 'inventory_admin',
                'description' => 'Access to products, materials, BOM and production',
                'permissions' => ['manage_inventory', 'view_reports', 'view_dashboard'],
            ],
        ];

        foreach ($roles as $roleData) {
            $permSlugs = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(['slug' => $roleData['slug']], $roleData);

            $permIds = Permission::whereIn('slug', $permSlugs)->pluck('id');
            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }
}
