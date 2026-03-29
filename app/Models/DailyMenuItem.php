<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DailyMenuItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMenuItem extends Model
{
    /** @use HasFactory<DailyMenuItemFactory> */
    use HasFactory, HasUuids;

    protected $table = 'asl_daily_menu_items';

    protected $fillable = [
        'daily_menu_id',
        'meal_id',
        'sort_order',
        'servings_available',
        'max_per_order',
        'price',
        'discount_percent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'servings_available' => 'integer',
            'max_per_order' => 'integer',
            'price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function dailyMenu(): BelongsTo
    {
        return $this->belongsTo(DailyMenu::class, 'daily_menu_id');
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id');
    }
}
