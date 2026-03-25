<?php

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sends meal crud notifications to admin and partner users', function (): void {
    $adminRecipient = User::factory()->create();
    $adminRecipient->assignRole('Admin');

    $partnerRecipient = User::factory()->create();
    $partnerRecipient->assignRole('Partner');

    $actor = User::factory()->create();
    $actor->assignRole('Partner');

    Sanctum::actingAs($actor);

    $createResponse = $this->postJson('/api/my-meals', [
        'title' => 'Notification Meal',
        'description' => 'Testing notifications',
        'thumbnail_image' => 'https://example.com/thumb.jpg',
        'images' => ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
        'status' => 'draft',
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true);

    $mealId = $createResponse->json('data.id');

    $updateResponse = $this->putJson("/api/my-meals/{$mealId}", [
        'status' => 'published',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    $deleteResponse = $this->deleteJson("/api/my-meals/{$mealId}");

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    foreach ([$adminRecipient, $partnerRecipient] as $recipient) {
        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'meal_created',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'meal_updated',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'meal_deleted',
        ]);
    }

    expect(Notification::query()->where('user_id', $actor->id)->where('type', 'meal_created')->count())->toBe(0);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    Sanctum::actingAs($superAdmin);

    $response = $this->postJson('/api/admin/cache/redis/clear');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('restricts redis cache clear endpoint to super admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/admin/cache/redis/clear');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});
