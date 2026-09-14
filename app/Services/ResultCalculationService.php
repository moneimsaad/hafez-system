<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CommitteeStudent;
use App\Models\Evaluation;
use App\Models\Result;
use App\Services\CompetitionScoringRulesService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResultCalculationService
{
    public function __construct(private CompetitionScoringRulesService $scoringRules, private CompetitionLifecycleService $lifecycle) {}

    public function generate(Competition $competition, int $branchId): Collection
    {
        $this->lifecycle->assertAllowsResultGeneration($competition);
        $branch = $competition->competitionBranches()->whereKey($branchId)->firstOrFail();
        $mode = data_get($competition->rules, 'multiple_judges', 'average');
        $mode = $mode === 'sum' ? 'sum' : 'average';
        $evaluations = Evaluation::query()->where('competition_id', $competition->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'submitted')
            ->whereHas('registration', fn ($registration) => $registration
                ->whereColumn('registrations.competition_id', 'evaluations.competition_id')
                ->whereColumn('registrations.branch_id', 'evaluations.branch_id')
                ->whereColumn('registrations.student_id', 'evaluations.student_id'))
            ->get()->groupBy('registration_id');
        $assignments = CommitteeStudent::query()
            ->whereHas('committee', fn ($query) => $query->where('competition_id', $competition->id)->where('branch_id', $branch->id))
            ->with('committee.committeeJudges:id,committee_id,judge_id')
            ->get()
            ->groupBy('registration_id');

        // Final results may only be created once every platform judge assigned
        // to that registration has submitted a valid evaluation. Existing
        // results are intentionally exempt so regeneration never alters them.
        foreach ($assignments as $registrationId => $studentAssignments) {
            if (Result::query()->where('registration_id', $registrationId)->exists()) {
                continue;
            }
            $requiredJudges = $studentAssignments->flatMap(fn ($assignment) => $assignment->committee->committeeJudges->pluck('judge_id'))->unique()->values();
            $submittedJudges = collect($evaluations->get($registrationId, collect()))
                ->filter(fn (Evaluation $evaluation) => $requiredJudges->contains($evaluation->judge_id))
                ->pluck('judge_id')->unique();
            if ($requiredJudges->isEmpty() || $submittedJudges->count() !== $requiredJudges->count()) {
                throw ValidationException::withMessages(['branch_id' => 'لم تكتمل تقييمات جميع الحكام لهذا الطالب بعد.']);
            }
        }
        $results = collect();
        $lockedIds = [];

        foreach ($evaluations as $registrationId => $studentEvaluations) {
            $existing = Result::query()->where('registration_id', $registrationId)->first();
            if ($existing !== null) {
                $results->push($existing);
                $lockedIds[] = $existing->id;
                continue;
            }
            $values = $studentEvaluations->pluck('total_score')->map(fn ($value) => (float) $value);
            $finalScore = $mode === 'sum' ? $values->sum() : $values->avg();
            $maximumScore = $this->scoringRules->maximumForBranch($branch);
            if ($mode === 'sum') {
                $maximumScore *= $studentEvaluations->count();
            }
            $percentage = $maximumScore > 0 ? ($finalScore / $maximumScore) * 100 : 0;
            $first = $studentEvaluations->first();
            $results->push(Result::updateOrCreate(
                ['registration_id' => $registrationId],
                [
                    'competition_id' => $competition->id,
                    'branch_id' => $branch->id,
                    'student_id' => $first->student_id,
                    'final_score' => round($finalScore, 2),
                    'percentage' => round($percentage, 2),
                    'result_status' => $finalScore >= (float) $branch->passing_score ? 'successful' : 'failed',
                    'is_winner' => $finalScore >= (float) $branch->passing_score,
                    // Legacy approved_at now records automatic finalization.
                    'approved_at' => now(),
                ]
            ));
        }

        $ordered = $results->sortByDesc(fn (Result $result) => (float) $result->final_score)->values();
        $rank = 0;
        $previousScore = null;
        foreach ($ordered as $index => $result) {
            $score = (float) $result->final_score;
            if ($previousScore === null || $score < $previousScore) {
                $rank = $index + 1;
            }
            if (! in_array($result->id, $lockedIds, true)) {
                $result->update(['rank' => $rank]);
            }
            $previousScore = $score;
        }

        return $ordered;
    }
}
