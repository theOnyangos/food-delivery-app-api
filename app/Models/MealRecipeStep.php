<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealRecipeStep extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meal_recipe_steps';

    protected $fillable = [
        'recipe_id',
        'title',
        'description',
        'images',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'position' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(MealRecipe::class, 'recipe_id');
    }
}
