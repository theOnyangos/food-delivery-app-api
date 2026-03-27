<?php

namespace Database\Factories;

use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyMenuItem>
 */
class DailyMenuItemFactory extends Factory
{
    protected $model = DailyMenuItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_menu_id' => DailyMenu::factory(),
            'meal_id' => Meal::factory(),
            'sort_order' => 0,
            'servings_available' => fake()->numberBetween(5, 100),
            'max_per_order' => fake()->optional()->numberBetween(1, 5),
        ];
    }
}
