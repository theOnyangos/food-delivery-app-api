<?php

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a subscriber on first subscribe', function (): void {
    $response = $this->postJson('/api/newsletter/subscribe', [
        'email' => 'new@example.com',
        'name' => 'Test User',
        'source' => 'test',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Thank you for subscribing to our newsletter.')
        ->assertJsonPath('data.subscriber.email', 'new@example.com');

    $this->assertDatabaseHas('asl_newsletter_subscribers', [
        'email' => 'new@example.com',
    ]);
});

it('returns 200 when already subscribed', function (): void {
    NewsletterSubscriber::query()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing',
        'subscribed_at' => now(),
        'unsubscribed_at' => null,
    ]);

    $response = $this->postJson('/api/newsletter/subscribe', [
        'email' => 'existing@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'You are already subscribed to our newsletter.');
});

it('validates email on subscribe', function (): void {
    $this->postJson('/api/newsletter/subscribe', [
        'email' => 'not-an-email',
    ])->assertStatus(422)
        ->assertJsonPath('success', false);
});
