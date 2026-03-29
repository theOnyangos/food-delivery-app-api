<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PosSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosSaleCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PosSale $sale
    ) {}
}
