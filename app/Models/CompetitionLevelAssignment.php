<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CompetitionBranch;
use LogicException;

class CompetitionLevelAssignment extends Model
{
    protected $fillable = ['competition_id', 'competition_level_id', 'min_age', 'max_age', 'total_score', 'passing_score'];

    protected function casts(): array
    {
        return ['min_age' => 'integer', 'max_age' => 'integer', 'total_score' => 'decimal:2', 'passing_score' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(function (CompetitionLevelAssignment $assignment): void {
            if (! $assignment->isDirty(['competition_id', 'competition_level_id'])) {
                return;
            }

            $hasCompatibilityBranch = CompetitionBranch::query()
                ->where('competition_id', $assignment->getOriginal('competition_id'))
                ->where('competition_level_id', $assignment->getOriginal('competition_level_id'))
                ->exists();

            if ($hasCompatibilityBranch) {
                throw new LogicException('لا يمكن تغيير هوية تعيين مستوى مرتبط بفرع توافق.' );
            }
        });
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function level()
    {
        return $this->belongsTo(CompetitionLevel::class, 'competition_level_id');
    }

    /**
     * Compatibility branch used by the existing runtime workflows.
     */
    public function compatibilityBranch()
    {
        return $this->hasOne(CompetitionBranch::class, 'competition_id', 'competition_id')
            ->where('competition_branches.competition_level_id', $this->competition_level_id);
    }
}
