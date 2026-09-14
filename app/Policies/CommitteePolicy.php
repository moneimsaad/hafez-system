<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : null;
    }

    public function viewAny(User $user): bool { return $user->role === 'User'; }
    public function view(User $user, Committee $committee): bool { return $user->role === 'User' && $committee->competition?->created_by === $user->id; }
    public function create(User $user): bool { return $user->role === 'User'; }
    public function update(User $user, Committee $committee): bool { return $user->role === 'User' && $committee->competition?->created_by === $user->id; }
    public function delete(User $user, Committee $committee): bool { return $user->role === 'User' && $committee->competition?->created_by === $user->id; }
    public function assignJudges(User $user, Committee $committee): bool { return $user->role === 'User' && $committee->competition?->created_by === $user->id; }
    public function assignStudents(User $user, Committee $committee): bool { return $user->role === 'User' && $committee->competition?->created_by === $user->id; }
}
