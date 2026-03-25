<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiDailyUsage extends Model
{
    use HasUuids;

    protected $table = 'asl_ai_daily_usage';

    protected $fillable = [
        'usage_date',
        'user_type',
        'identity_type',
        'identity',
        'message_count',
    ];
}
