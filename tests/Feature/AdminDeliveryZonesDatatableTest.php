<?php

use App\Models\DeliveryZone;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin delivery zones datatable', function (): void {
    $response = $this->getJson('/api/admin/delivery-zones?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

it('returns 403 without manage delivery zones permission', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');

    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/admin/delivery-zones?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    DeliveryZone::query()->create([
        'name' => 'Westlands',
        'zip_code' => '00100',
        'delivery_fee' => 150.5,
        'status' => 'active',
        'minimum_order_amount' => 1000,
        'estimated_delivery_minutes' => 45,
        'is_serviceable' => true,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/delivery-zones?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    $rows = $response->json('data');
    expect($rows)->toBeArray()->not->toBeEmpty();
    expect($rows[0]['name'] ?? null)->toBe('Westlands');
    expect($rows[0]['zip_code'] ?? null)->toBe('00100');
});
