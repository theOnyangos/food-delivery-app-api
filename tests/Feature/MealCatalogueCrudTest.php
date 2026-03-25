<?php

use App\Models\Meal;
use App\Models\MealCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows partner to create, update and delete their own meal', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    $category = MealCategory::query()->create([
        'title' => 'Lunch',
        'description' => 'Lunch meals',
    ]);

    Sanctum::actingAs($partner);

    $createResponse = $this->postJson('/api/my-meals', [
        'category_id' => $category->id,
        'title' => 'Protein Bowl',
        'excerpt' => 'Balanced lunch bowl',
        'description' => 'Chicken, rice and vegetables.',
        'cooking_time' => 30,
        'servings' => 2,
        'calories' => 600,
        'status' => 'published',
        'tags' => ['healthy', 'high-protein'],
        'nutrition' => [
            'fats' => 12.5,
            'protein' => 45,
            'carbs' => 50,
        ],
        'allergens' => [
            ['title' => 'Gluten', 'description' => 'Contains gluten traces'],
        ],
        'ingredients' => [
            [
                'meal_type' => 'main',
                'metadata' => [
                    ['name' => 'Chicken', 'value' => '200g'],
                    ['name' => 'Rice', 'value' => '150g'],
                ],
            ],
        ],
        'recipes' => [
            [
                'description' => 'Cooking instructions',
                'status' => 'active',
                'is_pro_only' => false,
                'steps' => [
                    ['title' => 'Prep', 'description' => 'Prepare ingredients', 'position' => 1],
                    ['title' => 'Cook', 'description' => 'Cook meal', 'position' => 2],
                ],
            ],
        ],
        'tutorials' => [
            ['title' => 'Plating', 'description' => 'How to plate', 'video_url' => 'https://example.com/video'],
        ],
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Protein Bowl')
        ->assertJsonPath('data.status', 'published');

    $mealId = $createResponse->json('data.id');

    $this->assertDatabaseHas('asl_meals', [
        'id' => $mealId,
        'user_id' => $partner->id,
        'status' => 'published',
    ]);

    $this->assertDatabaseHas('asl_meal_nutritions', [
        'meal_id' => $mealId,
    ]);

    $updateResponse = $this->putJson("/api/my-meals/{$mealId}", [
        'status' => 'archived',
        'title' => 'Protein Bowl Archived',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'archived')
        ->assertJsonPath('data.published_at', null);

    $deleteResponse = $this->deleteJson("/api/my-meals/{$mealId}");

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('asl_meals', [
        'id' => $mealId,
    ]);
});

it('prevents partner from managing another partners meal', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('Partner');

    $otherPartner = User::factory()->create();
    $otherPartner->assignRole('Partner');

    $meal = Meal::factory()->create([
        'user_id' => $owner->id,
        'status' => 'draft',
    ]);

    Sanctum::actingAs($otherPartner);

    $response = $this->putJson("/api/my-meals/{$meal->id}", [
        'title' => 'Hacked title',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'You are not allowed to manage this meal.');
});

it('requires authentication for meal management endpoints', function (): void {
    $response = $this->getJson('/api/my-meals');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('returns 403 for customer without manage meals on my-meals', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Customer');
    Sanctum::actingAs($user);

    $this->getJson('/api/my-meals')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});
