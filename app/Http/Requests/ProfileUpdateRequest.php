<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->filled('username')) {
            $this->merge(['username' => strtolower(trim((string) $this->input('username')))]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'username' => [
                'nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'organization_name' => 'اسم الجهة',
            'username' => 'اسم المستخدم',
            'phone' => 'رقم الهاتف',
        ];
    }
}
