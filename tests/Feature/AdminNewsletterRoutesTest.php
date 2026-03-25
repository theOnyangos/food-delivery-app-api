<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('returns 403 for admin on newsletter subscribers list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Admin');
    Sanctum::actingAs($user);

    $this->getJson('/api/admin/newsletter/subscribers?draw=1&start=0&length=10')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns datatable json for super admin on subscribers list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    Sanctum::actingAs($user);

    $this->getJson('/api/admin/newsletter/subscribers?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
});

it('returns 403 for admin on newsletter send', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Admin');
    Sanctum::actingAs($user);

    $this->postJson('/api/admin/newsletter/send', [
        'subject' => 'Hello',
        'body' => '<p>Test</p>',
    ])
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('accepts send from super admin', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    Sanctum::actingAs($user);

    $this->postJson('/api/admin/newsletter/send', [
        'subject' => 'Hello',
        'body' => '<p>Test</p>',
    ])
        ->assertStatus(202)
        ->assertJsonPath('success', true);
});
