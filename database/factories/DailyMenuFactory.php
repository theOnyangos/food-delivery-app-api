<?php

namespace Database\Factories;

use App\Models\DailyMenu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyMenu>
 */
class DailyMenuFactory extends Factory
{
    protected $model = DailyMenu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_date' => fake()->unique()->date(),
            'status' => DailyMenu::STATUS_DRAFT,
            'created_by' => User::factory(),
            'published_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => DailyMenu::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => DailyMenu::STATUS_ARCHIVED,
            'published_at' => now()->subDay(),
        ]);
    }
}
