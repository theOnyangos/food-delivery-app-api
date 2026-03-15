<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

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
            'manage roles',
            'view permissions',
            'view users',
            'manage user roles',
            'view notifications',
            'manage notifications',
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
        $admin->syncPermissions(['view dashboard', 'manage users', 'manage uploads', 'view notifications', 'manage notifications']);
        $partner->syncPermissions(['view dashboard', 'manage uploads', 'view notifications', 'manage notifications']);
        $customer->syncPermissions([]);
    }
}
