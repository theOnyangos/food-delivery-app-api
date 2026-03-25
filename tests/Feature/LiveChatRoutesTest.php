<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('returns 401 for unauthenticated chat settings', function (): void {
    $this->getJson('/api/chat/settings')
        ->assertStatus(401);
});

it('returns 403 for partner on chat settings', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::query()->where('name', 'Partner')->firstOrFail());
    Sanctum::actingAs($user);

    $this->getJson('/api/chat/settings')
        ->assertStatus(403);
});

it('returns 200 for admin on chat settings', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::query()->where('name', 'Admin')->firstOrFail());
    Sanctum::actingAs($user);

    $this->getJson('/api/chat/settings')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['timezone', 'working_hours_enabled']]);
});
