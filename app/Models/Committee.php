<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Committee extends Model
{
    protected $fillable = [
        'competition_id',
        'name',
        'branch_id',
        'exam_date',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
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

    public function committeeJudges()
    {
        return $this->hasMany(CommitteeJudge::class);
    }

    public function committeeStudents()
    {
        return $this->hasMany(CommitteeStudent::class);
    }

    public function manualJudges()
    {
        return $this->hasMany(CommitteeManualJudge::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'committee_judges', 'committee_id', 'judge_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'committee_students', 'committee_id', 'student_id')
            ->withPivot('registration_id');
    }
}
