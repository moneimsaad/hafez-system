<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'title',
        'competition_number',
        'description',
        'additional_terms',
        'registration_start_date',
        'registration_end_date',
        'exam_start_date',
        'exam_end_date',
        'location',
        'status',
        'rules',
        'created_by',
        'publication_scope',
        'target_governorate',
    ];

    protected function casts(): array
    {
        return [
            'registration_start_date' => 'datetime',
            'registration_end_date' => 'datetime',
            'exam_start_date' => 'datetime',
            'exam_end_date' => 'datetime',
            'rules' => 'array',
            'competition_number' => 'integer',
        ];
    }

    public function getDisplayStatusAttribute(): string
    {
        return app(\App\Services\CompetitionStatusService::class)->displayStatus($this);
    }

    public function getRegistrationReadyAttribute(): bool
    {
        return app(\App\Services\CompetitionStatusService::class)->isReady($this);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function competitionBranches()
    {
        return $this->hasMany(CompetitionBranch::class);
    }

    public function levelAssignments()
    {
        return $this->hasMany(CompetitionLevelAssignment::class);
    }

    public function competitionLevels()
    {
        return $this->belongsToMany(CompetitionLevel::class, 'competition_level_assignments')
            ->withPivot(['min_age', 'max_age', 'total_score', 'passing_score'])
            ->withTimestamps();
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function committees()
    {
        return $this->hasMany(Committee::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
