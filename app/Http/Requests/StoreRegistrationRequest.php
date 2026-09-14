<?php

namespace App\Http\Requests;

use App\Models\CompetitionBranch;
use App\Models\Competition;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\EgyptianNationalIdService;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'branch_id' => ['required', 'integer', 'exists:competition_branches,id'],
            'full_name' => ['required', 'string', 'max:80'],
            'national_id' => ['nullable', 'digits:14'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'phone' => ['required', 'regex:/^(010|011|012|015)\\d{8}$/'],
            'parent_phone' => ['required', 'regex:/^(010|011|012|015)\\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:120'],
            'center_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'governorate' => ['nullable', 'string', 'in:'.implode(',', config('governorates'))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $nationalId = $this->input('national_id');
            if ($nationalId !== null && $nationalId !== '') {
                $parsed = app(EgyptianNationalIdService::class)->parse($nationalId);
                if ($parsed === null) {
                    $validator->errors()->add('national_id', 'يرجى إدخال رقم قومي مصري صحيح مكون من 14 رقماً.');
                } else {
                    if ($this->input('birth_date') !== $parsed['birth_date']) {
                        $validator->errors()->add('birth_date', 'تاريخ الميلاد لا يطابق الرقم القومي.');
                    }
                    if ($this->input('gender') !== $parsed['gender']) {
                        $validator->errors()->add('gender', 'النوع لا يطابق الرقم القومي.');
                    }
                }
            }

            $parts = preg_split('/\\s+/u', trim((string) $this->input('full_name')), -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 3 || count($parts) > 5) {
                $validator->errors()->add('full_name', 'يجب كتابة الاسم بالكامل من 3 إلى 5 أسماء.');
            }

            $competitionId = $this->integer('competition_id');
            $branchId = $this->integer('branch_id');

            if ($competitionId && $branchId) {
                $competition = Competition::find($competitionId);
                $belongsToCompetition = CompetitionBranch::query()
                    ->whereKey($branchId)
                    ->where('competition_id', $competitionId)
                    ->exists();

                if (! $belongsToCompetition) {
                    $validator->errors()->add('branch_id', 'المستوى المحدد لا يتبع المسابقة المختارة.');
                }

                if ($belongsToCompetition && $this->filled('birth_date')) {
                    $branch = CompetitionBranch::find($branchId);
                    $birthDate = Carbon::parse($this->input('birth_date'));
                    // Eligibility means the student's completed age at the
                    // competition/exam date, not merely when the form is sent.
                    $referenceDate = $competition?->exam_start_date ?? now();
                    $age = (int) $birthDate->diffInYears($referenceDate);
                    if ($branch->min_age !== null && $branch->max_age !== null
                        && ($age < (int) $branch->min_age || $age > (int) $branch->max_age)) {
                        $rule = match (true) {
                            $branch->min_age !== null && $branch->max_age !== null => "من {$branch->min_age} إلى {$branch->max_age} سنة",
                            $branch->min_age !== null => "{$branch->min_age} سنوات فأكثر",
                            default => "حتى {$branch->max_age} سنة",
                        };
                        $validator->errors()->add('birth_date', "عمر الطالب لا يناسب شروط المستوى المحدد (العمر المسموح: {$rule}).");
                    }
                }

                if ($competition?->publication_scope === 'governorate') {
                    if (! $this->filled('governorate')) {
                        $validator->errors()->add('governorate', 'يرجى اختيار المحافظة.');
                    } elseif ($this->input('governorate') !== $competition->target_governorate) {
                        $validator->errors()->add('governorate', "هذه المسابقة متاحة لسكان محافظة {$competition->target_governorate} فقط.");
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'date' => 'يرجى إدخال :attribute بصيغة صحيحة.', 'before_or_equal' => 'يجب ألا يكون :attribute في المستقبل.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'in' => 'قيمة :attribute غير صحيحة.', 'email' => 'يرجى إدخال بريد إلكتروني صحيح.', 'image' => 'يجب أن تكون الصورة بصيغة صحيحة.', 'digits' => 'يرجى إدخال رقم قومي مصري صحيح مكون من 14 رقماً.', 'regex' => 'رقم الهاتف يجب أن يكون رقم محمول مصري صحيحاً مكوناً من 11 رقماً.', 'max' => 'حقل :attribute تجاوز الحد المسموح.'];
    }

    public function attributes(): array
    {
        return ['competition_id' => 'المسابقة', 'branch_id' => 'مستوى المسابقة', 'full_name' => 'الاسم بالكامل', 'birth_date' => 'تاريخ الميلاد', 'gender' => 'النوع', 'phone' => 'هاتف الطالب', 'parent_phone' => 'هاتف ولي الأمر', 'governorate' => 'المحافظة'];
    }

    protected function prepareForValidation(): void
    {
        $nationalIdService = app(EgyptianNationalIdService::class);
        $normalizeSpaces = static fn ($value) => is_string($value) ? preg_replace('/\\s+/u', ' ', trim($value)) : $value;
        $normalizedNationalId = $nationalIdService->normalize($this->input('national_id'));
        $derived = $normalizedNationalId ? $nationalIdService->parse($normalizedNationalId) : null;
        $this->merge([
            'national_id' => $normalizedNationalId,
            'full_name' => $normalizeSpaces($this->input('full_name')),
            'phone' => $nationalIdService->normalize($this->input('phone')),
            'parent_phone' => $nationalIdService->normalize($this->input('parent_phone')),
        ]);
        if ($derived) {
            $this->merge([
                'birth_date' => $derived['birth_date'],
                'gender' => $derived['gender'],
            ]);
        }
    }
}
