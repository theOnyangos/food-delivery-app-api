<?php

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Support\InventoryConstants;
use App\Support\InventoryMovementType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('records usage and reduces stock with a usage movement', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $item = InventoryItem::query()->create([
        'sku' => 'SKU-USE-1',
        'name' => 'Flour',
        'image_url' => null,
        'quantity' => 100,
        'unit' => 'kg',
        'storage_location' => 'A1',
        'storage_temperature_celsius' => null,
        'expiration_date' => null,
        'low_stock_threshold' => 10,
    ]);

    $response = $this->postJson('/api/admin/inventory/usage', [
        'occurred_at' => '2026-03-28T18:00:00Z',
        'notes' => 'Evening shift',
        'lines' => [
            ['inventory_item_id' => $item->id, 'quantity_used' => 12.5],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.correlation_id', fn ($v) => is_string($v) && $v !== '');

    $item->refresh();
    expect((float) (string) $item->quantity)->toBe(87.5);

    $movement = InventoryMovement::query()
        ->where('inventory_item_id', $item->id)
        ->where('type', InventoryMovementType::USAGE)
        ->first();

    expect($movement)->not->toBeNull();
    expect((float) (string) $movement->quantity_delta)->toBe(-12.5);
    expect((float) (string) $movement->quantity_after)->toBe(87.5);
    expect($movement->notes)->toBe('Evening shift');
});

it('imports valid CSV and creates items', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $header = implode(',', InventoryConstants::CSV_HEADERS);
    $row = 'IMP-1,Imported rice,25,kg,Pantry,5,2027-01-01,3';
    $csv = $header."\n".$row."\n";

    $file = UploadedFile::fake()->createWithContent('stock.csv', $csv);

    $response = $this->post('/api/admin/inventory/import', [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.imported', 1)
        ->assertJsonPath('data.updated', 0);

    $item = InventoryItem::query()->where('sku', 'IMP-1')->first();
    expect($item)->not->toBeNull();
    expect((float) (string) $item->quantity)->toBe(25.0);
});

it('rejects CSV with wrong headers', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $csv = "wrong,headers\na,b\n";
    $file = UploadedFile::fake()->createWithContent('bad.csv', $csv);

    $response = $this->post('/api/admin/inventory/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('returns DataTables JSON for inventory items', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    InventoryItem::query()->create([
        'sku' => 'DT-1',
        'name' => 'Beans',
        'image_url' => null,
        'quantity' => 4,
        'unit' => 'kg',
        'storage_location' => 'Cold room',
        'storage_temperature_celsius' => 2,
        'expiration_date' => '2030-01-01',
        'low_stock_threshold' => 1,
    ]);

    $dt = $this->getJson('/api/admin/inventory/items/datatables?draw=1&start=0&length=10');

    $dt->assertOk()
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

    $rows = $dt->json('data');
    expect($rows)->toHaveCount(1);
    expect($rows[0])->toHaveKeys(['id', 'name', 'sku', 'status_label', 'quantity_display', 'updated_at_formatted']);
    expect($rows[0]['name'])->toBe('Beans');
});

it('returns item analytics aggregated for date range', function (): void {
    $admin = User::factory()->create(['first_name' => 'Alex', 'last_name' => 'Admin']);
    $admin->assignRole('Admin');
    $peer = User::factory()->create(['first_name' => 'Pat', 'last_name' => 'Peer']);
    Sanctum::actingAs($admin);

    $item = InventoryItem::query()->create([
        'sku' => 'AN-1',
        'name' => 'Sugar',
        'image_url' => null,
        'quantity' => 100,
        'unit' => 'kg',
        'storage_location' => 'S1',
        'storage_temperature_celsius' => null,
        'expiration_date' => null,
        'low_stock_threshold' => 5,
    ]);

    $day1 = '2026-03-10 12:00:00';
    $day2 = '2026-03-11 12:00:00';

    InventoryMovement::query()->create([
        'inventory_item_id' => $item->id,
        'type' => InventoryMovementType::USAGE,
        'quantity_delta' => -2,
        'quantity_after' => 98,
        'occurred_at' => $day1,
        'notes' => null,
        'created_by' => $admin->id,
        'inventory_import_batch_id' => null,
        'correlation_id' => null,
    ]);
    InventoryMovement::query()->create([
        'inventory_item_id' => $item->id,
        'type' => InventoryMovementType::USAGE,
        'quantity_delta' => -3,
        'quantity_after' => 95,
        'occurred_at' => $day1,
        'notes' => null,
        'created_by' => $admin->id,
        'inventory_import_batch_id' => null,
        'correlation_id' => null,
    ]);
    InventoryMovement::query()->create([
        'inventory_item_id' => $item->id,
        'type' => InventoryMovementType::USAGE,
        'quantity_delta' => -1,
        'quantity_after' => 94,
        'occurred_at' => $day2,
        'notes' => null,
        'created_by' => $peer->id,
        'inventory_import_batch_id' => null,
        'correlation_id' => null,
    ]);
    InventoryMovement::query()->create([
        'inventory_item_id' => $item->id,
        'type' => InventoryMovementType::ADJUSTMENT,
        'quantity_delta' => 5,
        'quantity_after' => 99,
        'occurred_at' => $day2,
        'notes' => 'Stock count',
        'created_by' => $admin->id,
        'inventory_import_batch_id' => null,
        'correlation_id' => null,
    ]);

    $response = $this->getJson('/api/admin/inventory/items/'.$item->id.'/analytics?from=2026-03-01&to=2026-03-31');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.item.name', 'Sugar')
        ->assertJsonPath('data.summary.total_usage_quantity', 6)
        ->assertJsonPath('data.summary.usage_event_count', 3);

    $daily = $response->json('data.daily_usage');
    expect($daily)->toBeArray();
    $m10 = collect($daily)->firstWhere('date', '2026-03-10');
    $m11 = collect($daily)->firstWhere('date', '2026-03-11');
    expect((float) $m10['usage_quantity'])->toBe(5.0);
    expect((float) $m11['usage_quantity'])->toBe(1.0);

    $byUser = $response->json('data.usage_by_user');
    expect($byUser)->toBeArray();
    $alex = collect($byUser)->firstWhere('user_name', 'Alex Admin');
    $pat = collect($byUser)->firstWhere('user_name', 'Pat Peer');
    expect((float) $alex['usage_quantity'])->toBe(5.0);
    expect((float) $pat['usage_quantity'])->toBe(1.0);

    $byType = collect($response->json('data.movement_volume_by_type'));
    expect((float) $byType->firstWhere('type', 'usage')['volume'])->toBe(6.0);
    expect((float) $byType->firstWhere('type', 'adjustment')['volume'])->toBe(5.0);

    $cum = $response->json('data.cumulative_usage');
    expect((float) end($cum)['cumulative_quantity'])->toBe(6.0);
});

it('rejects analytics range over 366 days', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $item = InventoryItem::query()->create([
        'sku' => 'AN-2',
        'name' => 'Salt',
        'image_url' => null,
        'quantity' => 1,
        'unit' => 'kg',
        'storage_location' => null,
        'storage_temperature_celsius' => null,
        'expiration_date' => null,
        'low_stock_threshold' => null,
    ]);

    $response = $this->getJson('/api/admin/inventory/items/'.$item->id.'/analytics?from=2025-01-01&to=2026-12-31');

    $response->assertStatus(422);
});
