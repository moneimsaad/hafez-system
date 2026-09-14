<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Platform Admin';
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_description' => ['nullable', 'string', 'max:2000'],
            'organization_address' => ['nullable', 'string', 'max:500'],
            'organization_phone' => ['nullable', 'string', 'max:50'],
            'organization_email' => ['nullable', 'email', 'max:255'],
            'platform_footer' => ['nullable', 'string', 'max:500'],
            'certificate_issuer' => ['nullable', 'string', 'max:255'],
            'certificate_title' => ['required', 'string', 'max:255'],
            'certificate_footer' => ['nullable', 'string', 'max:1000'],
            'certificate_number_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'certificate_show_qr' => ['nullable', 'boolean'],
            'certificate_verification_text' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
