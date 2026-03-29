<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Route → permission map (middleware in routes/api.php):
 *
 * - uploads: manage uploads (+ Super Admin|Admin|Partner via role_or_permission)
 * - admin roles, permissions, user role sync: manage roles
 * - admin users (list, invite, role-options): manage users
 * - notifications (datatable, unread, etc.): view notifications | manage notifications
 * - chat (staff): manage chat
 * - chat (participants): use live chat
 * - admin delivery zones CRUD: manage delivery zones
 * - delivery addresses CRUD: manage delivery addresses or listed roles; check-coverage is auth only
 * - meals catalogue GET, meal-categories GET: auth only (no extra permission)
 * - my-meals: manage meals
 * - meal-categories POST/PUT/DELETE (admin): manage meal categories
 * - admin/cache/redis/clear: Super Admin role only
 * - ai routes: use ai chat
 * - admin ai-agent: manage ai agent
 * - POST /newsletter/subscribe: public (no auth)
 * - POST /admin/newsletter/send, GET/PATCH/DELETE /admin/newsletter/subscribers*: auth:sanctum + role_or_permission:Super Admin|manage newsletter + permission:manage newsletter (only Super Admin holds manage newsletter; Admin role excluded)
 * - admin/review-categories: manage review categories
 * - admin/review-topics: manage review topics
 * - admin/reviews: manage reviews
 * - GET /blog/categories, /blogs/recent, /blogs, /blogs/{slugOrId}: public (no extra permission)
 * - admin/blog/categories*, admin/blogs*: role_or_permission Super Admin|Admin|manage content (no duplicate permission middleware)
 * - GET /admin/meal-ingredients (DataTables): Super Admin or Admin role only (Partner excluded despite manage meals)
 * - GET /admin/meal-categories (DataTables): Super Admin or Admin role only (same as admin meals)
 * - admin/daily-menus*: role_or_permission Super Admin|Admin (same as admin meals/recipes DT; Partner excluded)
 * - GET /daily-menus/effective: auth:sanctum only
 * - permission manage daily menus: seeded for Super Admin + Admin for future granular gates / UI
 */
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
            'manage ai agent',
            'use ai chat',
            'manage chat',
            'use live chat',
            'manage meals',
            'manage meal categories',
            'manage delivery zones',
            'manage delivery addresses',
            'manage newsletter',
            'manage review categories',
            'manage review topics',
            'manage reviews',
            'manage content',
            'manage daily menus',
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

        $superAdmin->syncPermissions(Permission::query()->get());

        $admin->syncPermissions(Permission::query()->whereNotIn('name', ['manage roles', 'manage newsletter'])->get());

        $partner->syncPermissions(Permission::query()->whereIn('name', [
            'view dashboard',
            'manage uploads',
            'manage meals',
            'view notifications',
            'manage notifications',
            'use ai chat',
            'use live chat',
        ])->get());

        $customer->syncPermissions(Permission::query()->whereIn('name', [
            'manage delivery addresses',
            'use live chat',
        ])->get());
    }
}
