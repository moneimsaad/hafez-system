<?php

namespace App\Policies;

use App\Models\CompetitionBranch;
use App\Models\Competition;
use App\Models\User;

class CompetitionBranchPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'User';
    }

    public function view(User $user, CompetitionBranch $competitionBranch): bool { return $user->role === 'User' && (($competitionBranch->competition?->created_by === $user->id) || ($competitionBranch->competition_id === null && (int) $competitionBranch->level?->created_by === (int) $user->id)); }

    public function create(User $user): bool
    {
        return $user->role === 'User';
    }

    public function update(User $user, CompetitionBranch $competitionBranch): bool { return $this->view($user, $competitionBranch); }

    public function delete(User $user, CompetitionBranch $competitionBranch): bool { return $this->view($user, $competitionBranch); }

    public function createForCompetition(User $user, Competition $competition): bool { return $user->role === 'User' && $competition->created_by === $user->id; }
}
