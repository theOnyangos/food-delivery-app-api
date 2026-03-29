<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSale extends Model
{
    use HasUuids;

    protected $table = 'asl_pos_sales';

    protected $fillable = [
        'receipt_number',
        'daily_menu_id',
        'sold_by',
        'order_type',
        'customer_email',
        'receipt_email_sent_at',
        'totals',
        'lines',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'totals' => 'array',
            'lines' => 'array',
            'receipt_email_sent_at' => 'datetime',
        ];
    }

    public function dailyMenu(): BelongsTo
    {
        return $this->belongsTo(DailyMenu::class, 'daily_menu_id');
    }

    public function soldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }
}
