<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications_enabled' => ['sometimes', 'boolean'],
            'notification_types' => ['sometimes', 'array'],
            'notification_types.*' => ['string', 'max:100'],
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'sms_notifications_enabled' => ['sometimes', 'boolean'],
            'sms_phone_number' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ];
    }
}
