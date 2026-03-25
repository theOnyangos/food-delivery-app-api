<?php

namespace Database\Factories;

use App\Models\MealCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealCategory>
 */
class MealCategoryFactory extends Factory
{
    protected $model = MealCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'image' => fake()->imageUrl(),
            'icon' => fake()->word(),
        ];
    }
}
