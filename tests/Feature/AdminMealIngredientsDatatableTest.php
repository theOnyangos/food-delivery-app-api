<?php

use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin meal ingredients datatable', function (): void {
    $response = $this->getJson('/api/admin/meal-ingredients?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('data', null);
});

it('returns 403 for partner with manage meals', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    Sanctum::actingAs($partner);

    $response = $this->getJson('/api/admin/meal-ingredients?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    $owner = User::factory()->create();
    $meal = Meal::factory()->create(['user_id' => $owner->id]);
    MealIngredient::query()->create([
        'meal_id' => $meal->id,
        'meal_type' => 'main',
        'metadata' => [
            ['name' => 'Salt', 'value' => '1 tsp'],
        ],
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/meal-ingredients?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    expect($response->json('data.0'))->toHaveKey('meal_thumbnail_image');
    expect($response->json('data.0.meal_title'))->toBe($meal->title);
    expect($response->json('data.0.metadata_formatted'))->toContain('Salt');
});

it('returns yajra datatable payload for super admin', function (): void {
    $meal = Meal::factory()->create();
    MealIngredient::query()->create([
        'meal_id' => $meal->id,
        'meal_type' => null,
        'metadata' => [
            ['name' => 'Pepper', 'value' => 'pinch'],
        ],
    ]);

    $super = User::factory()->create();
    $super->assignRole('Super Admin');
    Sanctum::actingAs($super);

    $response = $this->getJson('/api/admin/meal-ingredients?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

    expect($response->json('data.0.metadata_formatted'))->toContain('Pepper');
});
