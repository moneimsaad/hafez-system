<?php

namespace App\Policies;

use App\Models\PlatformSetting;
use App\Models\User;

class PlatformSettingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'Platform Admin' ? true : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'Platform Admin';
    }

    public function update(User $user, PlatformSetting $setting): bool
    {
        return $user->role === 'Platform Admin';
    }
}
