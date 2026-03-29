<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin users datatable', function (): void {
    $response = $this->getJson('/api/admin/users?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

it('returns 403 for customer on admin users datatable', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');

    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/admin/users?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    User::factory()->create(['email' => 'listed-user@example.com', 'first_name' => 'Listed', 'last_name' => 'User']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/users?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    $emails = collect($response->json('data'))->pluck('email')->all();
    expect($emails)->toContain('listed-user@example.com');
});

it('allows admin to fetch their own user detail', function (): void {
    $admin = User::factory()->create(['email' => 'self-admin@example.com']);
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/admin/users/{$admin->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'self-admin@example.com');
});

it('filters datatable by role query parameter', function (): void {
    $customer = User::factory()->create(['email' => 'only-customer@example.com']);
    $customer->assignRole('Customer');

    $partner = User::factory()->create(['email' => 'only-partner@example.com']);
    $partner->assignRole('Partner');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/users?draw=1&start=0&length=50&role=Customer');

    $response->assertOk();
    $emails = collect($response->json('data'))->pluck('email')->all();
    expect($emails)->toContain('only-customer@example.com')->not->toContain('only-partner@example.com');
});
