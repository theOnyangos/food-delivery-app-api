<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    protected $table = 'asl_personal_access_tokens';

    public $incrementing = false;

    protected $keyType = 'string';
}
