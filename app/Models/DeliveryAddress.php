<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAddress extends Model
{
    use HasUuids;

    protected $table = 'asl_delivery_addresses';

    protected $fillable = [
        'user_id',
        'zone_id',
        'label',
        'address_line',
        'city',
        'zip_code',
        'longitude',
        'latitude',
        'delivery_notes',
        'is_default',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'longitude' => 'decimal:8',
            'latitude' => 'decimal:8',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }
}
