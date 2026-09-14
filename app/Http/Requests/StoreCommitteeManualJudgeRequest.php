<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommitteeManualJudgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => preg_replace('/\s+/u', ' ', trim($this->input('name')))]);
        }
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:100']];
    }

    public function messages(): array
    {
        return ['required' => 'يرجى إدخال اسم الحكم.', 'min' => 'اسم الحكم قصير جداً.', 'max' => 'اسم الحكم تجاوز الحد المسموح.'];
    }
}
