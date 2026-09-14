<?php

namespace App\Http\Requests;

use App\Models\CommitteeJudge;
use App\Models\CompetitionBranch;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\CommitteeStudent;
use App\Services\CompetitionScoringRulesService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('scores') && ($this->has('memorization_score') || $this->has('tajweed_score') || $this->has('performance_score'))) {
            $this->merge(['scores' => [
                ['score' => $this->input('memorization_score')],
                ['score' => $this->input('tajweed_score')],
                ['score' => $this->input('performance_score')],
            ]]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'branch_id' => ['required', 'integer', 'exists:competition_branches,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'judge_id' => ['required', 'integer', 'exists:users,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $registration = Registration::query()->whereKey($this->integer('registration_id'))->first();
            $branch = CompetitionBranch::query()->with('competition')->whereKey($this->integer('branch_id'))->first();

            if ($registration && ($registration->student_id !== $this->integer('student_id')
                || $registration->competition_id !== $this->integer('competition_id')
                || $registration->branch_id !== $this->integer('branch_id'))) {
                $validator->errors()->add('registration_id', 'بيانات التسجيل لا تتطابق مع المسابقة ومستوى المسابقة والطالب المحددين.');
            }

            if ($branch && $branch->competition_id !== $this->integer('competition_id')) {
                $validator->errors()->add('branch_id', 'المستوى المحدد لا يتبع المسابقة المختارة.');
            }

            if ($branch?->competition) {
                $criteria = app(CompetitionScoringRulesService::class)->criteriaForBranch($branch);
                $scores = $this->input('scores', []);
                if (! is_array($scores)) {
                    return;
                }
                if (count($scores) !== count($criteria)) {
                    $validator->errors()->add('scores', 'يجب إدخال درجة لكل معيار تقييم محدد للمسابقة.');
                }
                foreach ($criteria as $index => $criterion) {
                    $score = data_get($scores, "$index.score");
                    if (is_numeric($score) && (float) $score > (float) $criterion['max_score']) {
                        $validator->errors()->add("scores.$index.score", "لا يمكن أن تتجاوز درجة {$criterion['name']} {$criterion['max_score']}.");
                    }
                }
            }

            if ($registration && $this->filled('judge_id') && Evaluation::query()
                ->where('registration_id', $registration->id)
                ->where('judge_id', $this->integer('judge_id'))
                ->exists()) {
                $validator->errors()->add('registration_id', 'تم تسجيل تقييم لهذا الطالب بواسطة الحكم نفسه من قبل.');
            }

            if ($this->user()?->role === 'User') {
                $owner = $branch?->competition?->created_by === $this->user()->id;
                $assigned = CommitteeJudge::query()->where('judge_id', $this->integer('judge_id'))
                    ->whereHas('committee', function ($query) {
                        $query->where('competition_id', $this->integer('competition_id'))
                            ->where('branch_id', $this->integer('branch_id'))
                            ->whereHas('committeeStudents', fn ($students) => $students->where('registration_id', $this->integer('registration_id')));
                    })->exists();

                $member = $registration && CommitteeStudent::query()->where('registration_id', $registration->id)->whereHas('committee', fn ($committee) => $committee->where('competition_id', $registration->competition_id)->where('branch_id', $registration->branch_id))->exists();
                if (! $owner && (! $assigned || $this->integer('judge_id') !== $this->user()->id)) {
                    $validator->errors()->add('judge_id', 'يمكنك تقييم الطلاب المسندين إلى لجنتك فقط.');
                }
                if (! $member) $validator->errors()->add('registration_id', 'لا يمكن تقييم طالب غير مسند إلى لجنة.');
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'numeric' => 'يجب أن تكون :attribute قيمة رقمية.', 'min' => 'لا يمكن أن تكون قيمة :attribute سالبة.'];
    }

    public function attributes(): array
    {
        return ['competition_id' => 'المسابقة', 'branch_id' => 'مستوى المسابقة', 'student_id' => 'الطالب', 'registration_id' => 'التسجيل', 'judge_id' => 'الحكم', 'scores.*.score' => 'درجة معيار التقييم'];
    }
}
