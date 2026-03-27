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

function actingAsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    return $admin;
}

it('lists meal options for admin', function (): void {
    actingAsAdmin();
    Meal::factory()->count(2)->create();

    $response = $this->getJson('/api/admin/meal-ingredients/meal-options');

    $response->assertOk()
        ->assertJsonPath('success', true);
    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
});

it('creates shows updates and deletes a meal ingredient', function (): void {
    actingAsAdmin();
    $meal = Meal::factory()->create();

    $create = $this->postJson('/api/admin/meal-ingredients', [
        'meal_id' => $meal->id,
        'meal_type' => 'prep',
        'metadata' => [
            ['name' => 'Oil', 'value' => '2 tbsp'],
        ],
    ]);

    $create->assertCreated()
        ->assertJsonPath('success', true);
    $id = $create->json('data.id');
    expect($id)->not->toBeEmpty();

    $show = $this->getJson("/api/admin/meal-ingredients/{$id}");
    $show->assertOk()
        ->assertJsonPath('data.metadata.0.name', 'Oil');

    $update = $this->patchJson("/api/admin/meal-ingredients/{$id}", [
        'meal_type' => 'main',
        'metadata' => [
            ['name' => 'Oil', 'value' => '3 tbsp'],
        ],
    ]);
    $update->assertOk()
        ->assertJsonPath('data.meal_type', 'main')
        ->assertJsonPath('data.metadata.0.value', '3 tbsp');

    $delete = $this->deleteJson("/api/admin/meal-ingredients/{$id}");
    $delete->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson("/api/admin/meal-ingredients/{$id}")->assertStatus(404);
});

it('downloads pdf export for admin', function (): void {
    actingAsAdmin();
    $meal = Meal::factory()->create();
    MealIngredient::query()->create([
        'meal_id' => $meal->id,
        'meal_type' => 'main',
        'metadata' => [['name' => 'Salt', 'value' => '1 tsp']],
    ]);

    $response = $this->get('/api/admin/meal-ingredients/export/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

it('returns 403 for partner on crud routes', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');
    Sanctum::actingAs($partner);

    $meal = Meal::factory()->create();
    $ing = MealIngredient::query()->create([
        'meal_id' => $meal->id,
        'meal_type' => null,
        'metadata' => [['name' => 'X', 'value' => 'Y']],
    ]);

    $this->getJson('/api/admin/meal-ingredients/meal-options')->assertStatus(403);
    $this->postJson('/api/admin/meal-ingredients', [
        'meal_id' => $meal->id,
        'metadata' => [['name' => 'A', 'value' => 'B']],
    ])->assertStatus(403);
    $this->getJson("/api/admin/meal-ingredients/{$ing->id}")->assertStatus(403);
    $this->deleteJson("/api/admin/meal-ingredients/{$ing->id}")->assertStatus(403);
    $this->get('/api/admin/meal-ingredients/export/pdf')->assertStatus(403);
});
