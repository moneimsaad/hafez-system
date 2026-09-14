<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function before(User $user): ?bool { return $user->role === 'Platform Admin' ? true : null; }

    public function viewAny(User $user): bool { return in_array($user->role, ['Platform Admin', 'User'], true); }

    public function view(User $user, Result $result): bool
    {
        return $user->role === 'User' && $result->competition?->created_by === $user->id;
    }

    public function generate(User $user, Competition $competition): bool
    {
        return $user->role === 'User' && $competition->created_by === $user->id;
    }

}
