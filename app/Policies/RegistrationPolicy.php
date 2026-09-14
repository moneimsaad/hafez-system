<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'User';
    }

    public function view(User $user, Registration $registration): bool { return $user->role === 'User' && $registration->competition?->created_by === $user->id; }

    public function update(User $user, Registration $registration): bool { return $user->role === 'User' && $registration->competition?->created_by === $user->id; }

    public function approve(User $user, Registration $registration): bool { return $user->role === 'User' && $registration->competition?->created_by === $user->id; }

    public function reject(User $user, Registration $registration): bool { return $user->role === 'User' && $registration->competition?->created_by === $user->id; }
}
