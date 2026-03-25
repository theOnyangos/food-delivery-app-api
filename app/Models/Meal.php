<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meals';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'excerpt',
        'description',
        'thumbnail_image',
        'images',
        'cooking_time',
        'servings',
        'calories',
        'status',
        'tags',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cooking_time' => 'integer',
            'servings' => 'integer',
            'calories' => 'integer',
            'images' => 'array',
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MealCategory::class, 'category_id');
    }

    public function nutrition(): HasOne
    {
        return $this->hasOne(MealNutrition::class, 'meal_id');
    }

    public function allergens(): HasMany
    {
        return $this->hasMany(MealAllergen::class, 'meal_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(MealIngredient::class, 'meal_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(MealRecipe::class, 'meal_id');
    }

    public function tutorials(): HasMany
    {
        return $this->hasMany(MealTutorial::class, 'meal_id');
    }
}
