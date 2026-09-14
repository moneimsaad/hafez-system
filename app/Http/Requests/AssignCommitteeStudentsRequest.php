<?php

namespace App\Http\Requests;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;

class AssignCommitteeStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'registration_ids' => ['nullable', 'array'],
            'registration_ids.*' => ['integer', 'exists:registrations,id', 'distinct'],
            'visible_registration_ids' => ['nullable', 'array'],
            'visible_registration_ids.*' => ['integer', 'exists:registrations,id', 'distinct'],
            'select_all_accepted' => ['nullable', 'boolean'],
            'clear_all' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = $this->input('registration_ids', []);
            if (! is_array($ids) || $ids === []) {
                return;
            }

            if (Registration::query()->whereIn('id', $ids)->where('status', '!=', 'approved')->exists()) {
                $validator->errors()->add('registration_ids', 'لا يمكن إسناد التسجيلات إلا بعد اعتمادها.');
            }
        });
    }
}
