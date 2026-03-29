<?php

use App\Models\Meal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('returns pos analytics summary with daily series', function (): void {
    Carbon::setTestNow('2033-05-01 14:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Soup']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2033-05-01',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 10,
                'price' => 10,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'daily_menu_id' => $menuId,
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 2],
        ],
    ])->assertCreated();

    $res = $this->getJson('/api/admin/pos/analytics/summary?from=2033-05-01&to=2033-05-01&include_daily_series=1');

    $res->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.order_count', 1)
        ->assertJsonPath('data.items_sold_total', 2);

    expect((float) $res->json('data.revenue_total'))->toBeGreaterThan(0);

    $daily = $res->json('data.daily');
    expect($daily)->toBeArray()->not->toBeEmpty();
    expect($daily[0]['date'])->toBe('2033-05-01');
    expect((float) $daily[0]['revenue'])->toBeGreaterThan(0);

    Carbon::setTestNow();
});

it('returns analytics by menu salesperson and meals', function (): void {
    Carbon::setTestNow('2033-06-10 11:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Pasta']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2033-06-10',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 15,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'takeaway',
        'daily_menu_id' => $menuId,
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    $from = '2033-06-10';
    $to = '2033-06-10';

    $menu = $this->getJson("/api/admin/pos/analytics/by-menu?from={$from}&to={$to}");
    $menu->assertOk()
        ->assertJsonPath('success', true);
    expect($menu->json('data.rows'))->toHaveCount(1);
    expect($menu->json('data.rows.0.daily_menu_id'))->toBe($menuId);

    $sp = $this->getJson("/api/admin/pos/analytics/by-salesperson?from={$from}&to={$to}");
    $sp->assertOk()
        ->assertJsonPath('data.rows.0.user_id', (string) $admin->id);

    $meals = $this->getJson("/api/admin/pos/analytics/meals?from={$from}&to={$to}&sort=revenue&limit=10");
    $meals->assertOk();
    expect($meals->json('data.rows.0.meal_id'))->toBe((string) $meal->id);
    expect($meals->json('data.rows.0.meal_title'))->toBe('Pasta');

    Carbon::setTestNow();
});

it('rejects analytics date range over max days', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/pos/analytics/summary?from=2020-01-01&to=2022-01-01')
        ->assertStatus(422);
});
