<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'working_hours_enabled' => 'sometimes|boolean',
            'start_time' => 'sometimes|string|regex:/^\d{1,2}:\d{2}$/',
            'end_time' => 'sometimes|string|regex:/^\d{1,2}:\d{2}$/',
            'timezone' => 'sometimes|string|timezone',
            'out_of_hours_message' => 'sometimes|string|max:1000',
            'allow_messages_outside_hours' => 'sometimes|boolean',
        ];
    }
}
