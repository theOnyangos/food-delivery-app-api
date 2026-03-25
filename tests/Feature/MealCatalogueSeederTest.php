<?php

use Database\Seeders\MealCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds meal catalogue relational data', function (): void {
    Artisan::call('db:seed', ['--class' => MealCatalogueSeeder::class]);

    expect(DB::table('asl_meal_categories')->count())->toBeGreaterThan(0);
    expect(DB::table('asl_meals')->count())->toBeGreaterThan(0);
    expect(DB::table('asl_meal_nutritions')->count())->toBeGreaterThan(0);
    expect(DB::table('asl_meal_recipes')->count())->toBeGreaterThan(0);
    expect(DB::table('asl_meal_recipe_steps')->count())->toBeGreaterThan(0);
});
