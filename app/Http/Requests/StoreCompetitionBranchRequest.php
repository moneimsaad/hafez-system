<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Competition;
use App\Models\CompetitionLevel;

class StoreCompetitionBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $competition = $this->route('competition');

        return $user !== null
            && (! $competition || $user->role === 'Platform Admin' || (int) $competition->created_by === (int) $user->id);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('competition_level_id') && ($level = CompetitionLevel::find($this->integer('competition_level_id')))) {
            $this->merge([
                'name' => $level->name,
                'description' => $level->description,
                'memorization_amount' => $level->memorization_amount,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'competition_id' => ['nullable', 'integer', 'exists:competitions,id'],
            'competition_level_id' => ['nullable', 'integer', 'exists:competition_levels,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'memorization_amount' => ['required', 'string', 'max:255'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0'],
            'total_score' => ['required', 'numeric', 'min:0'],
            'passing_score' => ['required', 'numeric', 'min:0', 'lte:total_score'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasMin = $this->filled('min_age');
            $hasMax = $this->filled('max_age');
            if ($hasMin xor $hasMax) {
                $validator->errors()->add($hasMin ? 'max_age' : 'min_age', $hasMin
                    ? 'يجب تحديد الحد الأقصى للعمر عند تحديد الحد الأدنى.'
                    : 'يجب تحديد الحد الأدنى للعمر عند تحديد الحد الأقصى.');
            } elseif ($hasMin && (int) $this->input('max_age') < (int) $this->input('min_age')) {
                $validator->errors()->add('max_age', 'يجب أن يكون الحد الأقصى للعمر أكبر من أو يساوي الحد الأدنى.');
            }
            if ($this->user()?->role === 'User' && $this->filled('competition_id')) {
                $owned = Competition::whereKey($this->integer('competition_id'))->where('created_by', $this->user()->id)->exists();
                if (! $owned) {
                    $validator->errors()->add('competition_id', 'يمكنك إدارة فروع مسابقاتك فقط.');
                }
            }
            if ($this->filled('competition_level_id')) {
                $level = CompetitionLevel::find($this->integer('competition_level_id'));
                $allowed = $level && $level->status === 'active' && ($level->type === 'system' || (int) $level->created_by === (int) $this->user()?->id);
                if (! $allowed) {
                    $validator->errors()->add('competition_level_id', 'لا يمكنك استخدام هذا المستوى.');
                }
                if (! $this->filled('competition_id') && $level?->type === 'system') {
                    $validator->errors()->add('competition_level_id', 'المستوى النظامي يجب ربطه بمسابقة عند إنشائه.');
                }
                if ($level && $this->filled('competition_id') && \DB::table('competition_level_assignments')->where('competition_id', $this->integer('competition_id'))->where('competition_level_id', $level->id)->exists()) {
                    $validator->errors()->add('competition_level_id', 'تمت إضافة هذا المستوى إلى المسابقة مسبقاً.');
                }
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.', 'numeric' => 'يجب أن يكون :attribute رقمًا.', 'min' => 'قيمة :attribute لا يمكن أن تكون سالبة.', 'lte' => 'قيمة :attribute يجب ألا تتجاوز الحد المسموح.', 'gte' => 'قيمة :attribute يجب ألا تقل عن الحد الأدنى.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.'];
    }

    public function attributes(): array
    {
        return ['competition_id' => 'المسابقة', 'competition_level_id' => 'مستوى المسابقة', 'name' => 'اسم المستوى', 'memorization_amount' => 'مقدار الحفظ', 'min_age' => 'العمر الأدنى', 'max_age' => 'العمر الأقصى', 'total_score' => 'الدرجة الكلية', 'passing_score' => 'درجة النجاح'];
    }
}
