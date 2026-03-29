<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUnblockedByAdmin
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $target,
        public User $actor
    ) {}
}
