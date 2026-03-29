<?php

use App\Models\DailyMenu;
use App\Models\Meal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for effective daily menu', function (): void {
    $this->getJson('/api/daily-menus/effective')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('returns direct published menu for the requested date without recycle flag', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Soup']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2030-06-15',
        'notes' => 'Summer',
        'items' => [
            [
                'meal_id' => $meal->id,
                'sort_order' => 0,
                'servings_available' => 12,
                'max_per_order' => 2,
                'price' => 10,
                'discount_percent' => 20,
            ],
        ],
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/daily-menus/effective?date=2030-06-15');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_recycled', false)
        ->assertJsonPath('data.effective_date', '2030-06-15')
        ->assertJsonPath('data.source_menu_id', null)
        ->assertJsonPath('data.menu.menu_date', '2030-06-15')
        ->assertJsonPath('data.items.0.meal_title', 'Soup')
        ->assertJsonPath('data.items.0.servings_available', 12)
        ->assertJsonPath('data.items.0.max_per_order', 2);

    expect((float) $response->json('data.items.0.price'))->toBe(10.0);
    expect((float) $response->json('data.items.0.discount_percent'))->toBe(20.0);
    expect((float) $response->json('data.items.0.effective_price'))->toBe(8.0);
});

it('recycles latest prior published menu when none exists for the date', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Pasta']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2030-01-10',
        'items' => [
            ['meal_id' => $meal->id, 'servings_available' => 5],
        ],
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/daily-menus/effective?date=2030-01-20');

    $response->assertOk()
        ->assertJsonPath('data.is_recycled', true)
        ->assertJsonPath('data.effective_date', '2030-01-20')
        ->assertJsonPath('data.source_menu_date', '2030-01-10')
        ->assertJsonPath('data.menu.menu_date', '2030-01-10')
        ->assertJsonPath('data.items.0.meal_title', 'Pasta');
});

it('returns empty effective payload when no published menus exist', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $this->getJson('/api/daily-menus/effective?date=2040-01-01')
        ->assertOk()
        ->assertJsonPath('data.menu', null)
        ->assertJsonPath('data.items', [])
        ->assertJsonPath('data.is_recycled', false);
});

it('requires authentication for admin daily menus datatable', function (): void {
    $this->getJson('/api/admin/daily-menus?draw=1&start=0&length=10')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('returns 403 for partner on admin daily menus datatable', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');
    Sanctum::actingAs($partner);

    $this->getJson('/api/admin/daily-menus?draw=1&start=0&length=10')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin with manage daily menus', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    DailyMenu::factory()->create([
        'menu_date' => '2031-03-01',
        'status' => DailyMenu::STATUS_DRAFT,
        'created_by' => $admin->id,
    ]);

    $response = $this->getJson('/api/admin/daily-menus?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    expect($response->json('data.0'))->toHaveKey('menu_date_formatted');
    expect($response->json('data.0'))->toHaveKey('items_count');
});

it('invalidates effective cache after menu items update', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $mealA = Meal::factory()->create(['title' => 'A']);
    $mealB = Meal::factory()->create(['title' => 'B']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2032-05-01',
        'items' => [
            ['meal_id' => $mealA->id, 'servings_available' => 1],
        ],
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $first = $this->getJson('/api/daily-menus/effective?date=2032-05-10');
    $first->assertOk();
    expect($first->json('data.items'))->toHaveCount(1);

    Sanctum::actingAs($admin);
    $this->putJson("/api/admin/daily-menus/{$menuId}", [
        'items' => [
            ['meal_id' => $mealA->id, 'servings_available' => 3],
            ['meal_id' => $mealB->id, 'servings_available' => 4],
        ],
    ])->assertOk();

    Sanctum::actingAs($customer);
    $second = $this->getJson('/api/daily-menus/effective?date=2032-05-10');
    $second->assertOk();
    expect($second->json('data.items'))->toHaveCount(2);
    expect(collect($second->json('data.items'))->pluck('meal_title')->sort()->values()->all())->toBe(['A', 'B']);
});

it('returns stats summary for admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    DailyMenu::factory()->published()->create([
        'menu_date' => '2033-01-01',
        'created_by' => $admin->id,
    ]);

    $response = $this->getJson('/api/admin/daily-menus/stats/summary?from=2033-01-01&to=2033-01-31&missing_horizon_days=7');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'from',
                'to',
                'missing_horizon_days',
                'published_menus_count',
                'draft_menus_count',
                'missing_published_dates_next_horizon',
                'menus_in_range',
            ],
        ]);

    expect($response->json('data.published_menus_count'))->toBeGreaterThanOrEqual(1);
});

it('rejects deleting a published daily menu', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2034-02-01',
        'items' => [['meal_id' => $meal->id, 'servings_available' => 1]],
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->deleteJson("/api/admin/daily-menus/{$menuId}")
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('allows deleting a draft daily menu', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2034-03-01',
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');

    $this->deleteJson("/api/admin/daily-menus/{$menuId}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(DailyMenu::query()->find($menuId))->toBeNull();
});

it('duplicates a menu as a new draft with copied items', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2035-04-01',
        'notes' => 'Source notes',
        'items' => [[
            'meal_id' => $meal->id,
            'servings_available' => 7,
            'max_per_order' => 2,
            'price' => 15.5,
            'discount_percent' => 10,
        ]],
    ]);
    $created->assertCreated();
    $sourceId = $created->json('data.menu.id');

    $dup = $this->postJson("/api/admin/daily-menus/{$sourceId}/duplicate", [
        'menu_date' => '2035-04-15',
    ]);

    $dup->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.menu.menu_date', '2035-04-15')
        ->assertJsonPath('data.menu.status', 'draft')
        ->assertJsonPath('data.menu.notes', 'Source notes');

    expect($dup->json('data.items.0.servings_available'))->toBe(7);
    expect($dup->json('data.items.0.max_per_order'))->toBe(2);
    expect((float) $dup->json('data.items.0.price'))->toBe(15.5);
    expect((float) $dup->json('data.items.0.discount_percent'))->toBe(10.0);

    expect(DailyMenu::query()->count())->toBe(2);
});
