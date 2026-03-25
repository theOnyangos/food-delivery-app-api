<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\Carbon;

class ChatSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
    {
        return config('chat.defaults', [
            'working_hours_enabled' => false,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'Africa/Nairobi',
            'out_of_hours_message' => 'Support is currently offline. We will respond during working hours.',
            'allow_messages_outside_hours' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $key = config('chat.settings_key', 'chat_settings');
        $stored = SystemSetting::getJsonValue($key, []);
        $defaults = $this->getDefaultSettings();

        return array_merge($defaults, $stored);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function updateSettings(array $settings): array
    {
        $this->validateSettings($settings);
        $key = config('chat.settings_key', 'chat_settings');
        $current = $this->getSettings();
        $updated = array_merge($current, $settings);
        SystemSetting::setJsonValue($key, $updated, 'Chat working hours and behaviour');

        return $this->getSettings();
    }

    public function isWithinWorkingHours(): bool
    {
        $settings = $this->getSettings();
        if (! ($settings['working_hours_enabled'] ?? false)) {
            return true;
        }

        $tz = $settings['timezone'] ?? 'UTC';
        $now = Carbon::now($tz)->format('H:i');
        $start = $settings['start_time'] ?? '00:00';
        $end = $settings['end_time'] ?? '23:59';

        if (strcmp($start, $end) <= 0) {
            return strcmp($now, $start) >= 0 && strcmp($now, $end) <= 0;
        }

        return strcmp($now, $start) >= 0 || strcmp($now, $end) <= 0;
    }

    public function canSendMessage(): bool
    {
        $settings = $this->getSettings();
        if (! ($settings['working_hours_enabled'] ?? false)) {
            return true;
        }
        if ($settings['allow_messages_outside_hours'] ?? true) {
            return true;
        }

        return $this->isWithinWorkingHours();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateSettings(array $settings): void
    {
        if (isset($settings['working_hours_enabled']) && ! is_bool($settings['working_hours_enabled'])) {
            throw new \InvalidArgumentException('working_hours_enabled must be boolean');
        }
        if (isset($settings['start_time']) && ! preg_match('/^\d{1,2}:\d{2}$/', $settings['start_time'])) {
            throw new \InvalidArgumentException('start_time must be HH:MM');
        }
        if (isset($settings['end_time']) && ! preg_match('/^\d{1,2}:\d{2}$/', $settings['end_time'])) {
            throw new \InvalidArgumentException('end_time must be HH:MM');
        }
        if (isset($settings['timezone']) && ! in_array($settings['timezone'], timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException('Invalid timezone');
        }
        if (isset($settings['allow_messages_outside_hours']) && ! is_bool($settings['allow_messages_outside_hours'])) {
            throw new \InvalidArgumentException('allow_messages_outside_hours must be boolean');
        }
    }
}
