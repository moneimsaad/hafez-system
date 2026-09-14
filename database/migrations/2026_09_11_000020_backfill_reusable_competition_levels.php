<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('competition_branches')
            ->join('competitions', 'competitions.id', '=', 'competition_branches.competition_id')
            ->select([
                'competition_branches.id as branch_id',
                'competition_branches.competition_id',
                'competition_branches.name',
                'competition_branches.description',
                'competition_branches.memorization_amount',
                'competition_branches.min_age',
                'competition_branches.max_age',
                'competition_branches.total_score',
                'competition_branches.passing_score',
                'competitions.created_by',
            ])
            ->orderBy('competition_branches.id')
            ->chunkById(100, function ($branches): void {
                foreach ($branches as $branch) {
                    $level = DB::table('competition_levels')
                        ->where('created_by', $branch->created_by)
                        ->where('type', 'organizer')
                        ->where('name', $branch->name)
                        ->where('memorization_amount', $branch->memorization_amount)
                        ->where(function ($query) use ($branch) {
                            if ($branch->description === null) {
                                $query->whereNull('description');
                            } else {
                                $query->where('description', $branch->description);
                            }
                        })
                        ->first();

                    if (! $level) {
                        $levelId = DB::table('competition_levels')->insertGetId([
                            'name' => $branch->name,
                            'description' => $branch->description,
                            'memorization_amount' => $branch->memorization_amount,
                            'created_by' => $branch->created_by,
                            'type' => 'organizer',
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $levelId = $level->id;
                    }

                    DB::table('competition_branches')
                        ->where('id', $branch->branch_id)
                        ->whereNull('competition_level_id')
                        ->update(['competition_level_id' => $levelId]);

                    DB::table('competition_level_assignments')->insertOrIgnore([
                        'competition_id' => $branch->competition_id,
                        'competition_level_id' => $levelId,
                        'min_age' => $branch->min_age,
                        'max_age' => $branch->max_age,
                        'total_score' => $branch->total_score,
                        'passing_score' => $branch->passing_score,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'competition_branches.id', 'branch_id');
    }

    public function down(): void
    {
        // Backfill is intentionally non-destructive. Existing branch IDs and
        // operational records must remain available during rollback. A later
        // approved cleanup can archive generated mappings after verification.
    }
};
