<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists public blog endpoints without authentication', function (): void {
    $category = BlogCategory::query()->create([
        'name' => 'News',
        'slug' => 'news',
    ]);

    Blog::query()->create([
        'blog_category_id' => $category->id,
        'title' => 'Hello',
        'slug' => 'hello',
        'excerpt' => 'Short',
        'body' => str_repeat('<p>Word </p>', 50),
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->getJson('/api/blog/categories')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/api/blogs/recent')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/api/blogs')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/api/blogs/hello')->assertOk()->assertJsonPath('data.title', 'Hello');
});

it('forbids customers from admin blog routes', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');
    Sanctum::actingAs($customer);

    $this->getJson('/api/admin/blogs?draw=1&start=0&length=10')->assertForbidden();
});

it('allows admin to access admin blog listing', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/blogs?draw=1&start=0&length=10')
        ->assertOk();
});
