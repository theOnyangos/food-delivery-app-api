<?php

use App\Models\Meal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows meal owner to add nutrition allergens ingredients recipes and tutorials via component endpoints', function (): void {
    $partnerRole = Role::query()->create([
        'name' => 'Partner',
        'guard_name' => 'web',
    ]);

    $partner = User::factory()->create();
    $partner->assignRole($partnerRole);

    $meal = Meal::factory()->create([
        'user_id' => $partner->id,
        'status' => 'draft',
    ]);

    Sanctum::actingAs($partner);

    $nutritionResponse = $this->putJson("/api/my-meals/{$meal->id}/nutrition", [
        'fats' => 10.5,
        'protein' => 32,
        'carbs' => 41,
        'metadata' => ['fiber' => '9g'],
    ]);

    $nutritionResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nutrition.protein', '32.00');

    $allergensResponse = $this->putJson("/api/my-meals/{$meal->id}/allergens", [
        'allergens' => [
            ['title' => 'Gluten', 'description' => 'Contains wheat'],
            ['title' => 'Dairy', 'description' => 'Contains milk'],
        ],
    ]);

    $allergensResponse->assertOk()
        ->assertJsonPath('success', true);

    $ingredientsResponse = $this->putJson("/api/my-meals/{$meal->id}/ingredients", [
        'ingredients' => [
            [
                'meal_type' => 'main',
                'metadata' => [
                    ['name' => 'Chicken', 'value' => '200g'],
                    ['name' => 'Rice', 'value' => '120g'],
                ],
            ],
        ],
    ]);

    $ingredientsResponse->assertOk()
        ->assertJsonPath('success', true);

    $recipesResponse = $this->putJson("/api/my-meals/{$meal->id}/recipes", [
        'recipes' => [
            [
                'description' => 'Standard recipe',
                'status' => 'active',
                'is_pro_only' => false,
                'steps' => [
                    ['title' => 'Prep', 'description' => 'Prep ingredients', 'position' => 1],
                    ['title' => 'Cook', 'description' => 'Cook ingredients', 'position' => 2],
                ],
            ],
        ],
    ]);

    $recipesResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.recipes.0.steps.1.title', 'Cook');

    $tutorialsResponse = $this->putJson("/api/my-meals/{$meal->id}/tutorials", [
        'tutorials' => [
            ['title' => 'How to serve', 'description' => 'Serving guide', 'video_url' => 'https://example.com/tutorial'],
        ],
    ]);

    $tutorialsResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tutorials.0.title', 'How to serve');

    $this->assertDatabaseHas('asl_meal_nutritions', [
        'meal_id' => $meal->id,
    ]);

    $this->assertDatabaseHas('asl_meal_allergens', [
        'meal_id' => $meal->id,
        'title' => 'Gluten',
    ]);

    $this->assertDatabaseHas('asl_meal_ingredients', [
        'meal_id' => $meal->id,
        'meal_type' => 'main',
    ]);

    $this->assertDatabaseHas('asl_meal_recipes', [
        'meal_id' => $meal->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('asl_meal_tutorials', [
        'meal_id' => $meal->id,
        'title' => 'How to serve',
    ]);
});

it('blocks non-owner from updating meal components', function (): void {
    $partnerRole = Role::query()->create([
        'name' => 'Partner',
        'guard_name' => 'web',
    ]);

    $owner = User::factory()->create();
    $owner->assignRole($partnerRole);

    $intruder = User::factory()->create();
    $intruder->assignRole($partnerRole);

    $meal = Meal::factory()->create([
        'user_id' => $owner->id,
    ]);

    Sanctum::actingAs($intruder);

    $response = $this->putJson("/api/my-meals/{$meal->id}/nutrition", [
        'protein' => 25,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'You are not allowed to manage this meal.');
});
