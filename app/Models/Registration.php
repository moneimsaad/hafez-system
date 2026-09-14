<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'competition_id',
        'registration_number',
        'branch_id',
        'student_id',
        'governorate',
        'status',
        'rejection_reason',
        'notes',
        'registered_at',
        'approved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Registration $registration): void {
            if (filled($registration->registration_number)) {
                return;
            }

            $year = Carbon::parse($registration->registered_at ?? now())->format('Y');
            do {
                $registration->registration_number = $year.'-'.random_int(10000, 99999);
            } while (static::query()->where('registration_number', $registration->registration_number)->exists());
        });
    }

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function committeeStudents()
    {
        return $this->hasMany(CommitteeStudent::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
