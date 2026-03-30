<?php

declare(strict_types=1);

namespace App\Support;

final class InventoryMovementType
{
    public const PURCHASE = 'purchase';

    public const USAGE = 'usage';

    public const ADJUSTMENT = 'adjustment';

    public const WASTE = 'waste';

    public const IMPORT_CREATE = 'import_create';

    public const IMPORT_UPDATE = 'import_update';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PURCHASE,
            self::USAGE,
            self::ADJUSTMENT,
            self::WASTE,
            self::IMPORT_CREATE,
            self::IMPORT_UPDATE,
        ];
    }
}
