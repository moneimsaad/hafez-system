<?php

namespace App\Services;

class EvaluationScoreService
{
    public function calculate(array $scores, float $maximumScore): array
    {
        $total = collect($scores)->sum(fn ($score) => (float) $score);
        $percentage = $maximumScore > 0 ? ($total / $maximumScore) * 100 : 0;

        return [
            'total_score' => round($total, 2),
            'percentage' => round($percentage, 2),
        ];
    }
}
