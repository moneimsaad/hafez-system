<?php

namespace App\Http\Requests;

use App\Models\Committee;
use App\Models\CommitteeStudent;
use App\Models\Registration;
use App\Services\CompetitionScoringRulesService;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'rows' => ['nullable', 'array'],
            'rows.*.registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'rows.*.scores' => ['nullable', 'array'],
            'rows.*.scores.*.score' => ['nullable', 'numeric', 'min:0'],
            'rows.*.notes' => ['nullable', 'string'],
            'save_and_next' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
        $committee = $this->route('committee');
        if (! $committee instanceof Committee) return;
        $criteria = app(CompetitionScoringRulesService::class)->criteriaForBranch($committee->competitionBranch);
        foreach ((array) $this->input('rows', []) as $index => $row) {
            $scores = is_array($row['scores'] ?? null) ? $row['scores'] : [];
            $values = collect($scores)->map(fn ($score) => is_array($score) ? ($score['score'] ?? null) : null);
            if ($values->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) continue;
            if ($values->count() !== count($criteria)) {
                $validator->errors()->add("rows.$index.scores", 'يجب إدخال درجات جميع معايير التقييم للطالب.');
                continue;
            }
            foreach ($criteria as $criterionIndex => $criterion) {
                $value = data_get($scores, "$criterionIndex.score");
                if ($value === null || $value === '') {
                    $validator->errors()->add("rows.$index.scores.$criterionIndex.score", "درجة {$criterion['name']} مطلوبة عند إدخال تقييم الطالب.");
                    continue;
                }
                if (is_numeric($value) && (float) $value > (float) $criterion['max_score']) {
                    $validator->errors()->add("rows.$index.scores.$criterionIndex.score", "لا يمكن أن تتجاوز درجة {$criterion['name']} {$criterion['max_score']}.");
                }
            }
            $registration = Registration::find($row['registration_id'] ?? null);
            if (! $registration || ! CommitteeStudent::where('committee_id', $committee->id)->where('registration_id', $registration->id)->where('student_id', $registration->student_id)->exists()) {
                $validator->errors()->add("rows.$index.registration_id", 'لا يمكن تقييم طالب غير مسند إلى هذه اللجنة.');
            }
        }
        });
    }

    public function messages(): array { return ['required' => 'حقل :attribute مطلوب.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'numeric' => 'يجب أن تكون :attribute قيمة رقمية.', 'min' => 'لا يمكن أن تكون قيمة :attribute سالبة.']; }
    public function attributes(): array { return ['rows.*.registration_id' => 'التسجيل', 'rows.*.scores.*.score' => 'درجة معيار التقييم']; }
}
