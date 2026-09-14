<?php

namespace App\Services;

use App\Models\CompetitionLevel;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CompetitionLevelLifecycleService
{
    public function archive(CompetitionLevel $level, User $actor): CompetitionLevel
    {
        $this->authorize($actor);
        $level->update(['status' => 'archived']);
        return $level->refresh();
    }

    public function restore(CompetitionLevel $level, User $actor): CompetitionLevel
    {
        $this->authorize($actor);
        $level->update(['status' => 'active']);
        return $level->refresh();
    }

    private function authorize(User $actor): void
    {
        if ($actor->role !== 'Platform Admin') {
            throw new AuthorizationException('لا يملك المستخدم صلاحية إدارة مستويات المنصة.');
        }
    }
}
