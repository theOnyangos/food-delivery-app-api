<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates default notification preferences when fetching for first time', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/notifications/preferences');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Notification preferences fetched successfully.')
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.notifications_enabled', true)
        ->assertJsonPath('data.email_notifications_enabled', true)
        ->assertJsonPath('data.sms_notifications_enabled', false)
        ->assertJsonPath('data.sms_phone_number', null);

    expect($response->json('data.notification_types'))->toBeArray();
});

it('requires a phone number when enabling sms notifications', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/notifications/preferences', [
        'sms_notifications_enabled' => true,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'A valid phone number is required when SMS notifications are enabled.');

    expect($response->json('data.errors.sms_phone_number'))->toBeArray();
});

it('updates notification preferences with a valid phone number', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/notifications/preferences', [
        'notifications_enabled' => true,
        'notification_types' => ['system', 'security'],
        'email_notifications_enabled' => false,
        'sms_notifications_enabled' => true,
        'sms_phone_number' => '+254712345678',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Notification preferences updated successfully.')
        ->assertJsonPath('data.notifications_enabled', true)
        ->assertJsonPath('data.email_notifications_enabled', false)
        ->assertJsonPath('data.sms_notifications_enabled', true)
        ->assertJsonPath('data.sms_phone_number', '+254712345678');

    expect($response->json('data.notification_types'))->toBe(['system', 'security']);
});
