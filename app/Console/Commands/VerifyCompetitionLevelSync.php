<?php

namespace App\Console\Commands;

use App\Models\CompetitionBranch;
use App\Models\CompetitionLevelAssignment;
use App\Models\CompetitionLevel;
use Illuminate\Console\Command;

class VerifyCompetitionLevelSync extends Command
{
    protected $signature = 'competition-levels:verify-sync';
    protected $description = 'Verify reusable level assignments and compatibility branches are synchronized.';

    public function handle(): int
    {
        $issues = [];

        CompetitionLevelAssignment::query()->chunkById(100, function ($assignments) use (&$issues): void {
            foreach ($assignments as $assignment) {
                if (! CompetitionLevel::query()->whereKey($assignment->competition_level_id)->exists()) {
                    $issues[] = "assignment {$assignment->id} points to a missing level";
                    continue;
                }
                $branch = $assignment->compatibilityBranch;
                if (! $branch) {
                    $issues[] = "assignment {$assignment->id} has no compatibility branch";
                    continue;
                }
                foreach (['min_age', 'max_age', 'total_score', 'passing_score'] as $field) {
                    if ((string) $assignment->{$field} !== (string) $branch->{$field}) {
                        $issues[] = "assignment {$assignment->id} and branch {$branch->id} differ on {$field}";
                    }
                }
            }
        });

        CompetitionBranch::query()->whereNotNull('competition_level_id')->chunkById(100, function ($branches) use (&$issues): void {
            foreach ($branches as $branch) {
                $exists = CompetitionLevelAssignment::query()
                    ->where('competition_id', $branch->competition_id)
                    ->where('competition_level_id', $branch->competition_level_id)
                    ->exists();
                if (! $exists) {
                    $issues[] = "branch {$branch->id} has no level assignment";
                }
            }
        });

        CompetitionLevel::query()->where('status', 'archived')->chunkById(100, function ($levels): void {
            foreach ($levels as $level) {
                // Archived levels may retain historical assignments/branches by
                // design; they must never be offered for new selections.
                $this->line("Archived level {$level->id}: historical references retained.");
            }
        });

        if ($issues !== []) {
            $this->error('Synchronization integrity check failed.');
            foreach ($issues as $issue) {
                $this->line('- '.$issue);
            }
            return self::FAILURE;
        }

        $this->info('Competition level synchronization is valid.');
        return self::SUCCESS;
    }
}
