<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'Platform Admin'; }

    public function rules(): array
    {
        $user = $this->route('user');
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['Platform Admin', 'User'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return (new StoreUserRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreUserRequest)->attributes();
    }
}
