<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InviteUserRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:asl_users,email'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['string', 'uuid', 'exists:asl_roles,id'],
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', 'exists:asl_roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('role_ids');
            $names = $this->input('role_names');
            $hasIds = is_array($ids) && count($ids) > 0;
            $hasNames = is_array($names) && count($names) > 0;

            if (! $hasIds && ! $hasNames) {
                $validator->errors()->add('role_names', 'At least one role is required.');
            }
        });
    }
}
