<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool { return $user->role === 'Platform Admin' ? true : false; }
    public function viewAny(User $user): bool { return $user->role === 'Platform Admin'; }
    public function view(User $user, User $managedUser): bool { return $user->role === 'Platform Admin'; }
    public function create(User $user): bool { return $user->role === 'Platform Admin'; }
    public function update(User $user, User $managedUser): bool { return $user->role === 'Platform Admin'; }
    public function delete(User $user, User $managedUser): bool { return $user->role === 'Platform Admin'; }
}
