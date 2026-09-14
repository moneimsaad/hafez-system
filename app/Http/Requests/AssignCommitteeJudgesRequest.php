<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCommitteeJudgesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'judge_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Existing clients that still send user IDs must receive the same
            // generic unavailable-feature response without triggering a lookup.
            if (! $this->hasAny(['judge_ids', 'judge_id']) && ! $this->filled('judge_email')) {
                $validator->errors()->add('judge_email', 'يرجى إدخال البريد الإلكتروني للحكم.');
            }
        });
    }
}
