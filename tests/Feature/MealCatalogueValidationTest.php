<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('validates required meal fields and nested ingredient metadata shape', function (): void {
    $partnerRole = Role::query()->create([
        'name' => 'Partner',
        'guard_name' => 'web',
    ]);

    $partner = User::factory()->create();
    $partner->assignRole($partnerRole);

    Sanctum::actingAs($partner);

    $response = $this->postJson('/api/my-meals', [
        'description' => 'Missing title and status',
        'ingredients' => [
            [
                'meal_type' => 'main',
                'metadata' => [
                    ['name' => 'Chicken'],
                ],
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($response->json('data.errors.title'))->toBeArray();
    expect($response->json('data.errors.status'))->toBeArray();
    expect($response->json('data.errors'))->toHaveKey('ingredients.0.metadata.0.value');
});
