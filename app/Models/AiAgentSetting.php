<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiAgentSetting extends Model
{
    use HasUuids;

    protected $table = 'asl_ai_agent_settings';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
        'updated_at',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('setting_key', $key)->first();
        if (! $setting || $setting->setting_value === null) {
            return $default;
        }
        $decoded = json_decode($setting->setting_value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $setting->setting_value;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): bool
    {
        $existing = static::query()->where('setting_key', $key)->first();
        $data = [
            'setting_key' => $key,
            'setting_value' => is_array($value) ? json_encode($value) : $value,
            'updated_at' => now(),
        ];
        if ($description !== null) {
            $data['description'] = $description;
        }
        if ($existing) {
            return $existing->update($data);
        }

        return (new static($data))->save();
    }
}
