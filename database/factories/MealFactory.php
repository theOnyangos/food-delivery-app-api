<?php

namespace Database\Factories;

use App\Models\Meal;
use App\Models\MealCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    protected $model = Meal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => MealCategory::factory(),
            'title' => fake()->sentence(3),
            'excerpt' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'thumbnail_image' => fake()->imageUrl(),
            'images' => [fake()->imageUrl(), fake()->imageUrl()],
            'cooking_time' => fake()->numberBetween(10, 120),
            'servings' => fake()->numberBetween(1, 8),
            'calories' => fake()->numberBetween(150, 1200),
            'status' => 'draft',
            'tags' => ['healthy', 'quick'],
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
