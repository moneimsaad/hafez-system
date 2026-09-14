<?php

namespace App\Http\Requests;

use App\Models\CompetitionBranch;
use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommitteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'integer', 'exists:competition_branches,id'],
            'exam_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
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
            if ($this->user()?->role === 'User' && $this->filled('competition_id')
                && ! Competition::whereKey($this->integer('competition_id'))->where('created_by', $this->user()->id)->exists()) {
                $validator->errors()->add('competition_id', 'يمكنك إدارة لجان مسابقاتك فقط.');
            }
        });
    }

    public function messages(): array
    {
        return ['required' => 'حقل :attribute مطلوب.', 'exists' => 'القيمة المحددة في :attribute غير موجودة.', 'date' => 'يرجى إدخال موعد صحيح.'];
    }

    public function attributes(): array
    {
        return ['competition_id' => 'المسابقة', 'name' => 'اسم اللجنة', 'branch_id' => 'مستوى المسابقة', 'exam_date' => 'موعد الاختبار', 'location' => 'الموقع'];
    }
}
