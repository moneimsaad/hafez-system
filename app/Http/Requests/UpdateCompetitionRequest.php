<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'additional_terms' => ['nullable', 'string', 'max:5000'],
            'registration_start_date' => ['required', 'date'],
            'registration_end_date' => ['required', 'date', 'after_or_equal:registration_start_date'],
            'exam_start_date' => ['required', 'date', 'after_or_equal:registration_end_date'],
            'exam_end_date' => ['required', 'date', 'after_or_equal:exam_start_date'],
            'location' => ['required', 'string', 'max:255'],
            'custom_scoring' => ['nullable', 'boolean'],
            'scoring_criteria' => ['required_if:custom_scoring,1', 'array'],
            'scoring_criteria.*.name' => ['required', 'string', 'max:100'],
            'scoring_criteria.*.max_score' => ['required', 'numeric', 'gt:0'],
            'publication_scope' => ['nullable', Rule::in(['unlisted', 'governorate', 'nationwide'])],
            'target_governorate' => ['nullable', 'required_if:publication_scope,governorate', Rule::in(config('governorates'))],
        ];
    }

    public function messages(): array
    {
        return (new StoreCompetitionRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreCompetitionRequest)->attributes();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->boolean('custom_scoring')) {
                return;
            }

            $names = [];
            foreach ($this->input('scoring_criteria', []) as $index => $criterion) {
                $name = trim((string) data_get($criterion, 'name', ''));
                if ($name !== '' && isset($names[mb_strtolower($name)])) {
                    $validator->errors()->add("scoring_criteria.$index.name", 'لا يمكن تكرار معيار التقييم نفسه.');
                }
                $names[mb_strtolower($name)] = true;
            }
        });
    }
}
