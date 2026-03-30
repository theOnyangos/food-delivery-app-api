<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasUuids;

    protected $table = 'asl_inventory_items';

    protected $fillable = [
        'sku',
        'name',
        'image_url',
        'quantity',
        'unit',
        'storage_location',
        'storage_temperature_celsius',
        'expiration_date',
        'low_stock_threshold',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'storage_temperature_celsius' => 'decimal:2',
            'expiration_date' => 'date',
            'low_stock_threshold' => 'decimal:4',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_item_id');
    }
}
