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

it('returns 401 for unauthenticated admin ai config', function (): void {
    $this->getJson('/api/admin/ai-agent/config')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('returns 403 for user without manage ai agent on admin config', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->where('name', 'Partner')->firstOrFail();
    $user->assignRole($role);

    Sanctum::actingAs($user);

    $this->getJson('/api/admin/ai-agent/config')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns 200 for super admin on admin ai config', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->where('name', 'Super Admin')->firstOrFail();
    $user->assignRole($role);

    Sanctum::actingAs($user);

    $this->getJson('/api/admin/ai-agent/config')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'config' => ['api_key_set', 'default_model', 'enabled']]);
});

it('returns 403 for customer role on ai chat', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->where('name', 'Customer')->firstOrFail();
    $user->assignRole($role);

    Sanctum::actingAs($user);

    $this->postJson('/api/ai/chat', ['message' => 'Hello'])
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns 400 when ai is not configured for partner chat', function (): void {
    config(['ai_agent.enabled' => true, 'ai_agent.api_key' => '']);

    $user = User::factory()->create();
    $role = Role::query()->where('name', 'Partner')->firstOrFail();
    $user->assignRole($role);

    Sanctum::actingAs($user);

    $this->postJson('/api/ai/chat', ['message' => 'Hello'])
        ->assertStatus(400)
        ->assertJsonPath('success', false);
});
