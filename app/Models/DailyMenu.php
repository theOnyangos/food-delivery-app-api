<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DailyMenuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMenu extends Model
{
    /** @use HasFactory<DailyMenuFactory> */
    use HasFactory, HasUuids;

    protected $table = 'asl_daily_menus';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'menu_date',
        'status',
        'created_by',
        'published_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'menu_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyMenuItem::class, 'daily_menu_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<DailyMenu>  $query
     * @return Builder<DailyMenu>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
