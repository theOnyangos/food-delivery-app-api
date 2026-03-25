<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meal_categories';

    protected $fillable = [
        'title',
        'description',
        'image',
        'icon',
    ];

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class, 'category_id');
    }
}
