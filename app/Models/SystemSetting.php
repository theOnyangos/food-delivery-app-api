<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'asl_system_settings';

    protected $fillable = [
        'key_name',
        'key_value',
        'description',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key_name', $key)->first();

        return $setting ? $setting->key_value : $default;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): SystemSetting
    {
        return self::query()->updateOrCreate(
            ['key_name' => $key],
            [
                'key_value' => is_array($value) ? json_encode($value) : $value,
                'description' => $description,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getJsonValue(string $key, array $default = []): array
    {
        $setting = self::query()->where('key_name', $key)->first();
        if (! $setting) {
            return $default;
        }
        $decoded = json_decode($setting->key_value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function setJsonValue(string $key, array $value, ?string $description = null): SystemSetting
    {
        return self::query()->updateOrCreate(
            ['key_name' => $key],
            [
                'key_value' => json_encode($value),
                'description' => $description,
            ]
        );
    }
}
