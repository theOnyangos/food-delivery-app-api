<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasUuids;

    protected $table = 'asl_delivery_zones';

    protected $fillable = [
        'name',
        'zip_code',
        'delivery_fee',
        'status',
        'minimum_order_amount',
        'estimated_delivery_minutes',
        'is_serviceable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'minimum_order_amount' => 'integer',
            'estimated_delivery_minutes' => 'integer',
            'is_serviceable' => 'boolean',
        ];
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class, 'zone_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_serviceable;
    }
}
