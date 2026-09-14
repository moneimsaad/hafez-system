<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'competition_id',
        'branch_id',
        'student_id',
        'registration_id',
        'final_score',
        'percentage',
        'rank',
        'result_status',
        'is_winner',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'rank' => 'integer',
            'is_winner' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Result $result): void {
            if ($result->is_winner === null) {
                $result->is_winner = $result->result_status === 'successful';
            }
        });
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
