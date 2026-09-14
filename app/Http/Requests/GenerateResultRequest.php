<?php

namespace App\Http\Requests;

use App\Models\CompetitionBranch;
use Illuminate\Foundation\Http\FormRequest;

class GenerateResultRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'branch_id' => ['required', 'integer', 'exists:competition_branches,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('competition_id') && $this->filled('branch_id')
                && ! CompetitionBranch::query()->whereKey($this->integer('branch_id'))
                    ->where('competition_id', $this->integer('competition_id'))->exists()) {
                $validator->errors()->add('branch_id', 'المستوى المحدد لا يتبع المسابقة المختارة.');
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.'];
    }

    public function attributes(): array
    {
        return ['competition_id' => 'المسابقة', 'branch_id' => 'مستوى المسابقة'];
    }
}
