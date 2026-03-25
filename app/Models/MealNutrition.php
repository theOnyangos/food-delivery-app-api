<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealNutrition extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meal_nutritions';

    protected $fillable = [
        'meal_id',
        'fats',
        'protein',
        'carbs',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fats' => 'decimal:2',
            'protein' => 'decimal:2',
            'carbs' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }
}
