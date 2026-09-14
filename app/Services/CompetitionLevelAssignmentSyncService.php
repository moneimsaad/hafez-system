<?php

namespace App\Services;

use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Keeps the reusable-level mapping and the legacy runtime branch in lockstep.
 *
 * Branches remain the runtime source for registrations, committees, evaluations,
 * results and certificates during the compatibility period.
 */
class CompetitionLevelAssignmentSyncService
{
    public function createFromData(array $data): CompetitionBranch
    {
        return DB::transaction(function () use ($data): CompetitionBranch {
            $level = ! empty($data['competition_level_id'])
                ? CompetitionLevel::findOrFail($data['competition_level_id'])
                : CompetitionLevel::firstOrCreate([
                    'created_by' => auth()->id(),
                    'type' => 'organizer',
                    'name' => trim($data['name']),
                ], [
                    'description' => $data['description'] ?? null,
                    'memorization_amount' => $data['memorization_amount'],
                    'status' => 'active',
                ]);

            return $this->createBranch($data, $level);
        });
    }

    public function createBranch(array $data, CompetitionLevel $level): CompetitionBranch
    {
        return DB::transaction(function () use ($data, $level): CompetitionBranch {
            $branch = CompetitionBranch::create([
                'competition_id' => $data['competition_id'] ?? null,
                'competition_level_id' => $level->id,
                'name' => $level->name,
                'description' => $level->description,
                'memorization_amount' => $level->memorization_amount,
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'total_score' => $data['total_score'],
                'passing_score' => $data['passing_score'],
            ]);

            if ($branch->competition_id) {
                $this->syncAssignmentFromBranch($branch, $level);
            }

            return $branch;
        });
    }

    public function syncBranch(CompetitionBranch $branch, ?CompetitionLevel $level = null): CompetitionBranch
    {
        return DB::transaction(function () use ($branch, $level): CompetitionBranch {
            $level ??= $branch->level()->first();

            if (! $level) {
                // Legacy branches are linked lazily when they are next edited.
                $level = CompetitionLevel::firstOrCreate([
                    'created_by' => $branch->competition?->created_by,
                    'type' => 'organizer',
                    'name' => trim($branch->name),
                ], [
                    'description' => $branch->description,
                    'memorization_amount' => $branch->memorization_amount,
                    'status' => 'active',
                ]);
                $branch->forceFill(['competition_level_id' => $level->id])->save();
            }

            // A system level owns its display identity; organizer levels may be
            // updated from the compatibility branch by the owning organizer.
            if ($level->type === 'organizer' && (int) $level->created_by === (int) $branch->competition?->created_by) {
                $level->update([
                    'name' => $branch->name,
                    'description' => $branch->description,
                    'memorization_amount' => $branch->memorization_amount,
                ]);
            } else {
                $branch->forceFill([
                    'name' => $level->name,
                    'description' => $level->description,
                    'memorization_amount' => $level->memorization_amount,
                ])->save();
            }

            if ($branch->competition_id) {
                $this->syncAssignmentFromBranch($branch, $level);
            }

            return $branch->fresh(['level']);
        });
    }

    public function syncAssignmentFromBranch(CompetitionBranch $branch, ?CompetitionLevel $level = null): CompetitionLevelAssignment
    {
        $level ??= $branch->level()->firstOrFail();

        return CompetitionLevelAssignment::updateOrCreate(
            [
                'competition_id' => $branch->competition_id,
                'competition_level_id' => $level->id,
            ],
            [
                'min_age' => $branch->min_age,
                'max_age' => $branch->max_age,
                'total_score' => $branch->total_score,
                'passing_score' => $branch->passing_score,
            ]
        );
    }

    /**
     * Explicit reverse synchronization entry point for administrative tooling.
     * Runtime reads remain on competition_branches.
     */
    public function syncBranchFromAssignment(CompetitionLevelAssignment $assignment): CompetitionBranch
    {
        return DB::transaction(function () use ($assignment): CompetitionBranch {
            $branch = $assignment->compatibilityBranch()->first();

            if (! $branch) {
                throw new LogicException('لا يوجد فرع توافق لتعيين مستوى المسابقة.');
            }

            $level = $assignment->relationLoaded('level') ? $assignment->level : $assignment->level()->firstOrFail();
            $branch->update([
                'name' => $level->name,
                'description' => $level->description,
                'memorization_amount' => $level->memorization_amount,
                'min_age' => $assignment->min_age,
                'max_age' => $assignment->max_age,
                'total_score' => $assignment->total_score,
                'passing_score' => $assignment->passing_score,
            ]);

            return $branch->fresh(['level']);
        });
    }

    /** @deprecated Use syncAssignmentFromBranch(). */
    public function syncAssignment(CompetitionBranch $branch, CompetitionLevel $level): CompetitionLevelAssignment
    {
        return $this->syncAssignmentFromBranch($branch, $level);
    }
}
