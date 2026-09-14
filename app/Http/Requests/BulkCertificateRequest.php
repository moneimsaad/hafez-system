<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'result_ids' => ['required', 'array', 'min:1', 'max:100'],
            'result_ids.*' => ['required', 'integer', 'distinct', 'exists:results,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'result_ids.required' => 'اختر نتيجة واحدة على الأقل.',
            'result_ids.min' => 'اختر نتيجة واحدة على الأقل.',
            'result_ids.max' => 'لا يمكن إصدار أكثر من 100 شهادة في العملية الواحدة.',
            'result_ids.*.distinct' => 'لا يمكن تكرار النتيجة المحددة.',
        ];
    }
}
