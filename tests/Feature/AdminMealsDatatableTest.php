<?php

use App\Models\Meal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin meals datatable', function (): void {
    $response = $this->getJson('/api/admin/meals?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('data', null);
});

it('returns 403 for partner on admin meals datatable', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    Sanctum::actingAs($partner);

    $response = $this->getJson('/api/admin/meals?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    Meal::factory()->create(['title' => 'Admin List Meal']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/meals?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    expect($response->json('data.0.meal_title'))->toBe('Admin List Meal');
});

it('returns yajra datatable payload for super admin', function (): void {
    Meal::factory()->create(['title' => 'Super Admin Meal']);

    $super = User::factory()->create();
    $super->assignRole('Super Admin');
    Sanctum::actingAs($super);

    $response = $this->getJson('/api/admin/meals?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

    expect($response->json('data.0.meal_title'))->toBe('Super Admin Meal');
});
