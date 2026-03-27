<?php

use App\Models\Meal;
use App\Models\MealRecipe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsAdminForPdf(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Sanctum::actingAs($admin);

    return $admin;
}

it('downloads meals pdf export for admin', function (): void {
    actingAsAdminForPdf();
    Meal::factory()->create(['title' => 'Export Test Meal']);

    $response = $this->get('/api/admin/meals/export/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

it('downloads meal recipes pdf export for admin', function (): void {
    actingAsAdminForPdf();
    $meal = Meal::factory()->create();
    MealRecipe::query()->create([
        'meal_id' => $meal->id,
        'description' => 'Recipe for PDF',
        'status' => 'active',
        'is_pro_only' => false,
    ]);

    $response = $this->get('/api/admin/meal-recipes/export/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

it('returns 403 for partner on meals pdf export', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');
    Sanctum::actingAs($partner);

    $this->get('/api/admin/meals/export/pdf')->assertStatus(403);
});

it('returns 403 for partner on meal recipes pdf export', function (): void {
    $partner = User::factory()->create();
    $partner->assignRole('Partner');
    Sanctum::actingAs($partner);

    $this->get('/api/admin/meal-recipes/export/pdf')->assertStatus(403);
});
