<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'User';
    }

    public function view(User $user, Competition $competition): bool { return $user->role === 'User' && $competition->created_by === $user->id; }

    public function create(User $user): bool
    {
        return $user->role === 'User';
    }

    public function generate(User $user, Competition $competition): bool
    {
        return $user->role === 'User' && $competition->created_by === $user->id;
    }

    public function update(User $user, Competition $competition): bool { return $user->role === 'User' && $competition->created_by === $user->id; }

    public function delete(User $user, Competition $competition): bool { return $user->role === 'User' && $competition->created_by === $user->id; }
}
