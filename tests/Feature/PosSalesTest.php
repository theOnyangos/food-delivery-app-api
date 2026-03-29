<?php

use App\Jobs\SendPosReceiptEmailJob;
use App\Mail\PosReceiptMail;
use App\Models\DailyMenuItem;
use App\Models\Meal;
use App\Models\PosSale;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists published daily menus for POS', function (): void {
    Carbon::setTestNow('2032-01-15 10:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2032-01-15',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 9,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->getJson('/api/admin/pos/daily-menus/published')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.menus.0.id', $menuId)
        ->assertJsonPath('data.menus.0.menu_date', '2032-01-15');

    Carbon::setTestNow();
});

it('returns 404 for today POS menu when no published menu exists for today', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/pos/daily-menu/today')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'No published menu for today.');
});

it('returns today published menu for POS', function (): void {
    Carbon::setTestNow('2031-07-01 10:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Curry']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-07-01',
        'items' => [
            [
                'meal_id' => $meal->id,
                'sort_order' => 0,
                'servings_available' => 20,
                'max_per_order' => 5,
                'price' => 12,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $created->assertCreated();
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $response = $this->getJson('/api/admin/pos/daily-menu/today');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.menu.menu_date', '2031-07-01')
        ->assertJsonPath('data.items.0.meal_title', 'Curry');

    Carbon::setTestNow();
});

it('creates a POS sale and dispatches receipt job when email is set', function (): void {
    Queue::fake();

    Carbon::setTestNow('2031-08-10 12:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Salad']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-08-10',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 10,
                'max_per_order' => 3,
                'price' => 8,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $response = $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'customer_email' => 'buyer@example.com',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 2],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sale.receipt_number', fn ($v) => is_string($v) && str_starts_with($v, 'ASL-Order-'));

    expect(PosSale::query()->count())->toBe(1);

    $dmi = DailyMenuItem::query()->where('meal_id', $meal->id)->first();
    expect($dmi)->not->toBeNull();
    expect($dmi->servings_available)->toBe(8);

    Queue::assertPushed(SendPosReceiptEmailJob::class);

    Carbon::setTestNow();
});

it('rejects pos sale when quantity exceeds servings available', function (): void {
    Carbon::setTestNow('2031-08-11 12:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-08-11',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 2,
                'price' => 8,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 3],
        ],
    ])->assertStatus(422);

    expect(PosSale::query()->count())->toBe(0);
    expect(DailyMenuItem::query()->where('meal_id', $meal->id)->value('servings_available'))->toBe(2);

    Carbon::setTestNow();
});

it('rejects pos sale when item is sold out', function (): void {
    Carbon::setTestNow('2031-08-12 12:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-08-12',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 1,
                'price' => 8,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    DailyMenuItem::query()->where('daily_menu_id', $menuId)->where('meal_id', $meal->id)->update(['servings_available' => 0]);

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertStatus(422);

    expect(PosSale::query()->count())->toBe(0);

    Carbon::setTestNow();
});

it('does not dispatch receipt job when customer email is omitted', function (): void {
    Queue::fake();

    Carbon::setTestNow('2031-09-01 09:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-09-01',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 5,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'takeaway',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    Queue::assertNotPushed(SendPosReceiptEmailJob::class);

    Carbon::setTestNow();
});

it('sends receipt mail only once when the job handle runs twice', function (): void {
    Mail::fake();

    Carbon::setTestNow('2031-10-01 10:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create(['title' => 'Soup']);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-10-01',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 10,
                'discount_percent' => 0,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $response = $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'customer_email' => 'buyer@example.com',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    $saleId = $response->json('data.sale.id');

    $job = new SendPosReceiptEmailJob($saleId);
    $job->handle();
    $job->handle();

    Mail::assertSent(PosReceiptMail::class, 1);

    Carbon::setTestNow();
});

it('returns Yajra DataTables JSON for POS sales', function (): void {
    Carbon::setTestNow('2031-11-05 14:00:00');

    $admin = User::factory()->create(['first_name' => 'Alex', 'last_name' => 'River']);
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-11-05',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 6,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'dine-in',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    $dt = $this->getJson(
        '/api/admin/pos/sales/datatables?draw=1&start=0&length=10&filter_date=2031-11-05'
    );

    $dt->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

    $rows = $dt->json('data');
    expect($rows)->toHaveCount(1);
    expect($rows[0])->toHaveKeys(['id', 'receipt_number', 'sold_by_name', 'total_display', 'order_type_label']);
    expect($rows[0]['sold_by_name'])->toContain('Alex');

    $filtered = $this->getJson(
        '/api/admin/pos/sales/datatables?draw=1&start=0&length=10&filter_date=2031-11-05&sold_by_name=NobodyHere'
    );
    $filtered->assertOk();
    expect($filtered->json('data'))->toHaveCount(0);

    Carbon::setTestNow();
});

it('deletes a POS sale', function (): void {
    Carbon::setTestNow('2031-12-01 11:00:00');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $meal = Meal::factory()->create();

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/admin/daily-menus', [
        'menu_date' => '2031-12-01',
        'items' => [
            [
                'meal_id' => $meal->id,
                'servings_available' => 5,
                'price' => 7,
            ],
        ],
    ]);
    $menuId = $created->json('data.menu.id');
    $this->postJson("/api/admin/daily-menus/{$menuId}/publish")->assertOk();

    $saleRes = $this->postJson('/api/admin/pos/sales', [
        'order_type' => 'takeaway',
        'lines' => [
            ['meal_id' => $meal->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    $saleId = $saleRes->json('data.sale.id');

    $this->deleteJson("/api/admin/pos/sales/{$saleId}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(PosSale::query()->whereKey($saleId)->exists())->toBeFalse();

    $dmiAfterDelete = DailyMenuItem::query()->where('daily_menu_id', $menuId)->where('meal_id', $meal->id)->first();
    expect($dmiAfterDelete->servings_available)->toBe(5);

    Carbon::setTestNow();
});
