<?php

namespace App\Http\Requests;

use App\Models\Committee;
use App\Models\CommitteeStudent;
use App\Models\Registration;
use App\Services\CompetitionScoringRulesService;
use Illuminate\Foundation\Http\FormRequest;

class StoreAutosaveEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'scores' => ['required', 'array'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $committee = $this->route('committee');
            $registration = Registration::query()->find($this->integer('registration_id'));
            if (! $committee instanceof Committee || ! $registration) return;

            if ($registration->competition_id !== $committee->competition_id
                || $registration->branch_id !== $committee->branch_id
                || $registration->status !== 'approved'
                || ! CommitteeStudent::query()->where('committee_id', $committee->id)
                    ->where('registration_id', $registration->id)
                    ->where('student_id', $registration->student_id)->exists()) {
                $validator->errors()->add('registration_id', 'لا يمكن تقييم طالب غير مسند إلى هذه اللجنة.');
            }

            $criteria = app(CompetitionScoringRulesService::class)->criteriaForBranch($committee->competitionBranch);
            foreach ($criteria as $index => $criterion) {
                $value = data_get($this->input('scores', []), "$index.score");
                if ($value === null || $value === '') {
                    $validator->errors()->add("scores.$index.score", "درجة {$criterion['name']} مطلوبة.");
                } elseif (is_numeric($value) && (float) $value > (float) $criterion['max_score']) {
                    $validator->errors()->add("scores.$index.score", "لا يمكن أن تتجاوز درجة {$criterion['name']} {$criterion['max_score']}.");
                }
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'numeric' => 'يجب أن تكون :attribute قيمة رقمية.', 'min' => 'لا يمكن أن تكون قيمة :attribute سالبة.'];
    }

    public function attributes(): array
    {
        return ['registration_id' => 'التسجيل', 'scores.*.score' => 'درجة معيار التقييم'];
    }
}
