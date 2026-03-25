<?php

namespace Database\Seeders;

use App\Models\Meal;
use App\Models\MealCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class MealCatalogueSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::query()->firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $partnerRole = Role::query()->firstOrCreate([
            'name' => 'Partner',
            'guard_name' => 'web',
        ]);

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'meals-admin@example.com'],
            [
                'first_name' => 'Meals',
                'last_name' => 'Admin',
                'password' => bcrypt('password'),
                'account_number' => 'MEAL-ADMIN-001',
            ]
        );

        $partnerUser = User::query()->firstOrCreate(
            ['email' => 'meals-partner@example.com'],
            [
                'first_name' => 'Meals',
                'last_name' => 'Partner',
                'password' => bcrypt('password'),
                'account_number' => 'MEAL-PARTNER-001',
            ]
        );

        if (! $adminUser->hasRole($adminRole)) {
            $adminUser->assignRole($adminRole);
        }

        if (! $partnerUser->hasRole($partnerRole)) {
            $partnerUser->assignRole($partnerRole);
        }

        $categories = collect([
            ['title' => 'Breakfast', 'description' => 'Morning meals'],
            ['title' => 'Lunch', 'description' => 'Midday meals'],
            ['title' => 'Dinner', 'description' => 'Evening meals'],
        ])->map(function (array $attributes): MealCategory {
            return MealCategory::query()->firstOrCreate(
                ['title' => $attributes['title']],
                [
                    'description' => $attributes['description'],
                    'image' => null,
                    'icon' => null,
                ]
            );
        });

        foreach ([$adminUser, $partnerUser] as $owner) {
            foreach ($categories as $category) {
                $meal = Meal::factory()->published()->create([
                    'user_id' => $owner->id,
                    'category_id' => $category->id,
                    'title' => $owner->first_name.' '.$category->title.' Meal',
                    'tags' => ['popular', strtolower($category->title)],
                    'thumbnail_image' => 'https://example.com/images/meals/thumbnail.jpg',
                    'images' => [
                        'https://example.com/images/meals/gallery-1.jpg',
                        'https://example.com/images/meals/gallery-2.jpg',
                    ],
                ]);

                $meal->nutrition()->create([
                    'fats' => 12.5,
                    'protein' => 24.5,
                    'carbs' => 40.0,
                    'metadata' => ['fiber' => '8g'],
                ]);

                $meal->allergens()->createMany([
                    ['title' => 'Gluten', 'description' => 'Contains wheat'],
                    ['title' => 'Nuts', 'description' => 'Processed in facility with nuts'],
                ]);

                $meal->ingredients()->createMany([
                    [
                        'meal_type' => 'main',
                        'metadata' => [
                            ['name' => 'Chicken Breast', 'value' => '200g'],
                            ['name' => 'Rice', 'value' => '150g'],
                        ],
                    ],
                ]);

                $recipe = $meal->recipes()->create([
                    'description' => 'Cook and assemble meal components.',
                    'status' => 'active',
                    'is_pro_only' => false,
                ]);

                $recipe->steps()->createMany([
                    [
                        'title' => 'Prep ingredients',
                        'description' => 'Wash and slice all ingredients.',
                        'images' => [],
                        'position' => 1,
                    ],
                    [
                        'title' => 'Cook protein',
                        'description' => 'Cook chicken thoroughly.',
                        'images' => [],
                        'position' => 2,
                    ],
                ]);

                $meal->tutorials()->create([
                    'title' => 'How to plate this meal',
                    'description' => 'Simple serving guide.',
                    'video_url' => 'https://example.com/tutorials/meal-plating',
                ]);
            }
        }
    }
}
