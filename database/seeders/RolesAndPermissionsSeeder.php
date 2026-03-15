<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'view dashboard',
            'manage users',
            'manage uploads',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $superAdmin = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $admin = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $partner = Role::query()->firstOrCreate(['name' => 'Partner', 'guard_name' => $guard]);
        $customer = Role::query()->firstOrCreate(['name' => 'Customer', 'guard_name' => $guard]);

        $allPermissions = Permission::query()->pluck('name')->all();

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions(['view dashboard', 'manage users', 'manage uploads']);
        $partner->syncPermissions(['view dashboard', 'manage uploads']);
        $customer->syncPermissions([]);
    }
}
