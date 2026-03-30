<?php

declare(strict_types=1);

namespace App\Support;

final class InventoryConstants
{
    /** @var list<string> */
    public const UNITS = ['g', 'kg', 'L', 'ml', 'pcs'];

    public const CSV_HEADERS = [
        'sku',
        'name',
        'quantity',
        'unit',
        'storage_location',
        'temperature_celsius',
        'expiration_date',
        'low_stock_threshold',
    ];
}
