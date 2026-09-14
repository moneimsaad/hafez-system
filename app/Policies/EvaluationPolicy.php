<?php

namespace App\Policies;

use App\Models\CommitteeJudge;
use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : null;
    }

    public function viewAny(User $user): bool { return in_array($user->role, ['Platform Admin', 'User'], true); }
    public function create(User $user): bool { return in_array($user->role, ['Platform Admin', 'User'], true); }

    public function view(User $user, Evaluation $evaluation): bool
    {
        return $user->role === 'User'
            && ($evaluation->competition?->created_by === $user->id || $this->assigned($user, $evaluation));
    }

    private function assigned(User $user, Evaluation $evaluation): bool
    {
        return CommitteeJudge::query()->where('judge_id', $user->id)
            ->whereHas('committee', function ($query) use ($evaluation) {
                $query->where('competition_id', $evaluation->competition_id)
                    ->where('branch_id', $evaluation->branch_id)
                    ->whereHas('committeeStudents', fn ($students) => $students
                        ->where('registration_id', $evaluation->registration_id)
                        ->where('student_id', $evaluation->student_id));
            })->exists();
    }
}
