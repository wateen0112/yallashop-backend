<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';
        $roles = ['admin', 'vendor', 'courier', 'customer'];
        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, $guard);
        }

        $permissions = [
            'manage-vendors',
            'manage-couriers',
            'manage-orders',
            'manage-products',
            'manage-catalog',
            'view-analytics',
            'manage-payouts',
            'manage-disputes',
        ];
        foreach ($permissions as $permName) {
            Permission::findOrCreate($permName, $guard);
        }

        $admin = Role::findByName('admin');
        $admin->syncPermissions($permissions);

        $vendor = Role::findByName('vendor');
        $vendor->syncPermissions(['manage-products', 'manage-orders']);

        $courier = Role::findByName('courier');
        $courier->syncPermissions(['manage-orders']);
    }
}
