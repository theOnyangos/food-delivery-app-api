<?php

use App\Models\MealCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin meal categories datatable', function (): void {
    $response = $this->getJson('/api/admin/meal-categories?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('data', null);
});

it('returns 403 for partner on admin meal categories datatable', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    Sanctum::actingAs($partner);

    $response = $this->getJson('/api/admin/meal-categories?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    MealCategory::factory()->create(['title' => 'Breakfast']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/meal-categories?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    expect($response->json('data.0.title'))->toBe('Breakfast');
    expect($response->json('data.0.description_excerpt'))->toBeString();
    expect($response->json('data.0.updated_at_formatted'))->toBeString();
});
