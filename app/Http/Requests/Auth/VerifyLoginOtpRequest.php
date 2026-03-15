<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'otp_challenge_token' => ['required', 'string', 'size:64'],
            'otp' => ['required', 'string', 'min:4', 'max:12'],
        ];
    }
}