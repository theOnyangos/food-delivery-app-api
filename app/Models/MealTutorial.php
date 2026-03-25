<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealTutorial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asl_meal_tutorials';

    protected $fillable = [
        'meal_id',
        'title',
        'description',
        'video_url',
    ];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }
}
