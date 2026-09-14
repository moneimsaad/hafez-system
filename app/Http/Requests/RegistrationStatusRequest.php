<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $value = (string) $this->input('registration_number', '');
        $value = strtr($value, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);

        $this->merge(['registration_number' => preg_replace('/\s+/', '', $value)]);
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'regex:/^\d{4}-\d{5}$/'],
        ];
    }
}
