<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Competition;
use App\Models\CompetitionLevel;

class UpdateCompetitionBranchRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

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
            if ($this->user()?->role === 'User' && $this->filled('competition_id')
                && ! Competition::whereKey($this->integer('competition_id'))
                    ->where('created_by', $this->user()->id)->exists()) {
                $validator->errors()->add('competition_id', 'يمكنك إدارة فروع مسابقاتك فقط.');
            }
            if ($this->filled('competition_level_id')) {
                $level = CompetitionLevel::find($this->integer('competition_level_id'));
                $allowed = $level && $level->status === 'active' && ($level->type === 'system' || (int) $level->created_by === (int) $this->user()?->id);
                if (! $allowed) {
                    $validator->errors()->add('competition_level_id', 'لا يمكنك استخدام هذا المستوى.');
                }
                if (! $this->filled('competition_id') && $level?->type === 'system') {
                    $validator->errors()->add('competition_level_id', 'المستوى النظامي يجب أن يبقى مرتبطاً بمسابقة.');
                }
            }
        });
    }

    public function messages(): array
    {
        return (new StoreCompetitionBranchRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreCompetitionBranchRequest)->attributes();
    }
}
