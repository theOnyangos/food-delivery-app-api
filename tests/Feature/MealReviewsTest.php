<?php

use App\Models\Meal;
use App\Models\Review;
use App\Models\ReviewCategory;
use App\Models\ReviewTopic;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists and creates meal reviews for a published meal', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    $meal = Meal::factory()->published()->create([
        'user_id' => $partner->id,
    ]);

    $category = ReviewCategory::query()->create([
        'name' => 'Taste',
        'slug' => 'taste',
        'sort_order' => 0,
    ]);
    $topic = ReviewTopic::query()->create([
        'name' => 'Fresh',
        'slug' => 'fresh',
        'sort_order' => 0,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $post = $this->postJson("/api/meals/{$meal->id}/reviews", [
        'rating' => 5,
        'message' => 'Excellent meal.',
        'category_ids' => [$category->id],
        'topic_ids' => [$topic->id],
    ]);

    $post->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rating', 5);

    $list = $this->getJson("/api/meals/{$meal->id}/reviews");
    $list->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.data.0.rating', 5);

    $this->getJson('/api/meals/reviews')->assertOk()->assertJsonPath('success', true);
});

it('allows admin to change review status and denies customers from admin review routes', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    $meal = Meal::factory()->published()->create([
        'user_id' => $partner->id,
    ]);

    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $this->postJson("/api/meals/{$meal->id}/reviews", [
        'rating' => 4,
        'message' => 'Good.',
    ])->assertCreated();

    $review = Review::query()->where('reviewable_id', $meal->id)->firstOrFail();
    $review->update(['status' => 'pending']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $patch = $this->patchJson("/api/admin/reviews/{$review->id}/status", [
        'status' => 'approved',
    ]);
    $patch->assertOk()->assertJsonPath('data.status', 'approved');

    Sanctum::actingAs($customer);
    $this->getJson('/api/admin/reviews')->assertForbidden();
});

it('lets a partner list reviews on their meal via my-meals', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');

    $meal = Meal::factory()->published()->create([
        'user_id' => $partner->id,
    ]);

    Sanctum::actingAs($partner);

    $this->getJson("/api/my-meals/{$meal->id}/reviews")
        ->assertOk()
        ->assertJsonPath('success', true);
});
