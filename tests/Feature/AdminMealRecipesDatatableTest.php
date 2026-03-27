<?php

use App\Models\Meal;
use App\Models\MealRecipe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication for admin meal recipes datatable', function (): void {
    $response = $this->getJson('/api/admin/meal-recipes?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('data', null);
});

it('returns 403 for partner on admin meal recipes datatable', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    Sanctum::actingAs($partner);

    $response = $this->getJson('/api/admin/meal-recipes?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for admin', function (): void {
    $meal = Meal::factory()->create(['title' => 'Recipe Parent Meal']);
    MealRecipe::query()->create([
        'meal_id' => $meal->id,
        'description' => 'Main recipe body',
        'status' => 'active',
        'is_pro_only' => false,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/meal-recipes?draw=1&start=0&length=10');

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
    expect($response->json('data.0.meal_title'))->toBe('Recipe Parent Meal');
    expect($response->json('data.0.description_excerpt'))->toBeString();
    expect($response->json('data.0.steps_count'))->toBe(0);
});

it('returns recipe show for admin', function (): void {
    $meal = Meal::factory()->create(['title' => 'Show Meal']);
    $recipe = MealRecipe::query()->create([
        'meal_id' => $meal->id,
        'description' => 'Desc',
        'status' => 'active',
        'is_pro_only' => false,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/admin/meal-recipes/{$recipe->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meal.id', $meal->id)
        ->assertJsonPath('data.meal.title', 'Show Meal')
        ->assertJsonPath('data.recipe.id', $recipe->id);
});
