<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionBranch;

class CompetitionScoringRulesService
{
    /**
     * The labels mirror the categories already captured by the evaluation form.
     * Their equal values are only an editable starter template, not a scoring rule.
     */
    public function defaultCriteria(): array
    {
        return [
            ['name' => 'الحفظ', 'max_score' => 50],
            ['name' => 'التجويد', 'max_score' => 25],
            ['name' => 'الأداء', 'max_score' => 25],
        ];
    }

    public function customCriteria(?array $rules): array
    {
        return collect(data_get($rules, 'scoring_criteria', []))
            ->filter(fn ($criterion) => is_array($criterion)
                && filled($criterion['name'] ?? null)
                && is_numeric($criterion['max_score'] ?? null)
                && (float) $criterion['max_score'] > 0)
            ->map(fn (array $criterion) => [
                'name' => trim((string) $criterion['name']),
                'max_score' => (float) $criterion['max_score'],
            ])
            ->values()
            ->all();
    }

    public function criteria(?array $rules): array
    {
        return $this->customCriteria($rules);
    }

    public function effectiveCriteria(Competition|array|null $competition): array
    {
        $rules = $competition instanceof Competition ? $competition->rules : $competition;
        $custom = $this->customCriteria($rules);

        return $custom ?: $this->defaultCriteria();
    }

    public function effectiveMaximumScore(Competition|array|null $competition): float
    {
        return (float) collect($this->effectiveCriteria($competition))->sum('max_score');
    }

    /**
     * Preserve the competition's criterion weights while applying the total
     * configured for the assigned competition level.
     */
    public function criteriaForBranch(CompetitionBranch $branch): array
    {
        $criteria = $this->effectiveCriteria($branch->competition);
        $sourceMaximum = (float) collect($criteria)->sum('max_score');
        $targetMaximum = (float) $branch->total_score;

        if ($sourceMaximum <= 0 || $targetMaximum <= 0 || abs($sourceMaximum - $targetMaximum) < 0.00001) {
            return $criteria;
        }

        $scaled = collect($criteria)->values()->map(fn (array $criterion) => [
            ...$criterion,
            'max_score' => round(((float) $criterion['max_score'] / $sourceMaximum) * $targetMaximum, 2),
        ])->all();
        $last = array_key_last($scaled);
        $scaled[$last]['max_score'] = round($targetMaximum - collect($scaled)->take($last)->sum('max_score'), 2);

        return $scaled;
    }

    public function maximumForBranch(CompetitionBranch $branch): float
    {
        return (float) $branch->total_score;
    }

    public function mergeCriteria(?array $rules, bool $usesCustomScoring, array $criteria): ?array
    {
        $rules = is_array($rules) ? $rules : [];

        if (! $usesCustomScoring) {
            unset($rules['scoring_criteria']);

            return $rules ?: null;
        }

        $rules['scoring_criteria'] = $this->customCriteria(['scoring_criteria' => $criteria]);

        return $rules;
    }
}
