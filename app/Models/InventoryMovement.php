<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasUuids;

    protected $table = 'asl_inventory_movements';

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity_delta',
        'quantity_after',
        'occurred_at',
        'notes',
        'created_by',
        'inventory_import_batch_id',
        'correlation_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryImportBatch::class, 'inventory_import_batch_id');
    }
}
