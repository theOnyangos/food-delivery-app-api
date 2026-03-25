<?php

use App\Models\Meal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('hides pro-only recipes for signed users', function (): void {
    $owner = User::factory()->create();

    $meal = Meal::factory()->published()->create([
        'user_id' => $owner->id,
    ]);

    $meal->recipes()->createMany([
        [
            'description' => 'Public recipe',
            'status' => 'active',
            'is_pro_only' => false,
        ],
        [
            'description' => 'Pro only recipe',
            'status' => 'active',
            'is_pro_only' => true,
        ],
    ]);

    $signedUser = User::factory()->create();
    Sanctum::actingAs($signedUser);

    $response = $this->getJson("/api/meals/{$meal->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.recipes'))->toBeArray()->toHaveCount(1);
    expect($response->json('data.recipes.0.is_pro_only'))->toBeFalse();
});

it('shows pro-only recipes for pro users', function (): void {
    $proRole = Role::query()->create([
        'name' => 'Pro User',
        'guard_name' => 'web',
    ]);

    $owner = User::factory()->create();

    $meal = Meal::factory()->published()->create([
        'user_id' => $owner->id,
    ]);

    $meal->recipes()->createMany([
        [
            'description' => 'Public recipe',
            'status' => 'active',
            'is_pro_only' => false,
        ],
        [
            'description' => 'Pro only recipe',
            'status' => 'active',
            'is_pro_only' => true,
        ],
    ]);

    $proUser = User::factory()->create();
    $proUser->assignRole($proRole);

    Sanctum::actingAs($proUser);

    $response = $this->getJson("/api/meals/{$meal->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.recipes'))->toBeArray()->toHaveCount(2);
});
