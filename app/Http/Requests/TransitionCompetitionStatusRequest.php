<?php

namespace App\Http\Requests;

use App\Services\CompetitionLifecycleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionCompetitionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(CompetitionLifecycleService::states())],
            'expected_status' => ['required', 'string', 'max:255'],
        ];
    }
}
