<?php

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication for notifications datatable', function (): void {
    $response = $this->getJson('/api/notifications/datatable?draw=1&start=0&length=10');

    $response->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('data', null);
});

it('returns 403 for authenticated user without required role or permission', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/notifications/datatable?draw=1&start=0&length=10');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns yajra datatable payload for authorized user', function (): void {
    $user = User::factory()->create();
    $adminRole = Role::query()->create([
        'name' => 'Admin',
        'guard_name' => 'web',
    ]);
    $user->assignRole($adminRole);

    Notification::query()->create([
        'user_id' => $user->id,
        'type' => 'order_status',
        'data' => [
            'title' => 'Order Update',
            'message' => 'Your order is being prepared.',
        ],
        'is_read' => false,
        'read_at' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/notifications/datatable?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ])
        ->assertJsonPath('draw', 1);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
    expect($response->json('data.0.title'))->toBe('Order Update');
    expect($response->json('data.0.message'))->toBe('Your order is being prepared.');
});
