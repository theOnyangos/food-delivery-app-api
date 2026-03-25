<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealRecipe extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meal_recipes';

    protected $fillable = [
        'meal_id',
        'description',
        'status',
        'is_pro_only',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pro_only' => 'boolean',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(MealRecipeStep::class, 'recipe_id')->orderBy('position');
    }
}
