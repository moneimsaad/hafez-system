<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CompetitionLevel extends Model
{
    protected $fillable = [
        'name', 'description', 'memorization_amount', 'created_by', 'type', 'status', 'default_key',
        'default_min_age', 'default_max_age', 'default_total_score', 'default_passing_score',
    ];

    protected function casts(): array
    {
        return [
            'default_min_age' => 'integer',
            'default_max_age' => 'integer',
            'default_total_score' => 'decimal:2',
            'default_passing_score' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (CompetitionLevel $level): void {
            $changedFields = array_keys($level->getDirty());
            $nonLifecycleChanges = array_diff($changedFields, ['status']);
            if ($nonLifecycleChanges !== [] && $level->compatibilityBranches()->get()->contains(fn (CompetitionBranch $branch) => $branch->hasHistoricalRecords())) {
                throw new LogicException('لا يمكن تعديل مستوى مرتبط بسجلات تاريخية.');
            }

            if (! $level->isDirty(['name', 'memorization_amount'])) {
                return;
            }

            if ($level->assignments()->exists() || $level->compatibilityBranches()->exists()) {
                throw new LogicException('لا يمكن تغيير اسم أو مقدار حفظ مستوى مستخدم في مسابقات.');
            }
        });

        static::deleting(function (CompetitionLevel $level): bool {
            if (! $level->assignments()->exists() && ! $level->compatibilityBranches()->exists()) {
                return true;
            }

            $level->forceFill(['status' => 'archived'])->saveQuietly();
            return false;
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(CompetitionLevelAssignment::class);
    }

    public function competitions()
    {
        return $this->belongsToMany(Competition::class, 'competition_level_assignments')
            ->withPivot(['min_age', 'max_age', 'total_score', 'passing_score'])
            ->withTimestamps();
    }

    public function compatibilityBranches()
    {
        return $this->hasMany(CompetitionBranch::class, 'competition_level_id');
    }

    public function isUsed(): bool
    {
        return $this->assignments()->exists() || $this->compatibilityBranches()->exists();
    }
}
