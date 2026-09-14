<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'competition_id',
        'branch_id',
        'student_id',
        'registration_id',
        'judge_id',
        'memorization_score',
        'tajweed_score',
        'performance_score',
        'discipline_score',
        'scores',
        'total_score',
        'percentage',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'memorization_score' => 'decimal:2',
            'tajweed_score' => 'decimal:2',
            'performance_score' => 'decimal:2',
            'discipline_score' => 'decimal:2',
            'scores' => 'array',
            'total_score' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function scoreItems(): array
    {
        $snapshot = collect($this->scores ?? [])
            ->filter(fn ($item) => is_array($item) && isset($item['name'], $item['score'], $item['max_score']))
            ->values()
            ->all();

        if ($snapshot) {
            return $snapshot;
        }

        return [
            ['name' => 'الحفظ', 'score' => (float) $this->memorization_score, 'max_score' => null],
            ['name' => 'التجويد', 'score' => (float) $this->tajweed_score, 'max_score' => null],
            ['name' => 'الأداء', 'score' => (float) $this->performance_score, 'max_score' => null],
            ['name' => 'الانضباط', 'score' => (float) $this->discipline_score, 'max_score' => null],
        ];
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitionBranch()
    {
        return $this->belongsTo(CompetitionBranch::class, 'branch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}
