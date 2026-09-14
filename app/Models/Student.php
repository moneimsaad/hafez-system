<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'full_name',
        'national_id',
        'birth_date',
        'gender',
        'phone',
        'parent_phone',
        'email',
        'address',
        'city',
        'center_name',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function committeeStudents()
    {
        return $this->hasMany(CommitteeStudent::class);
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

    public function committees()
    {
        return $this->belongsToMany(Committee::class, 'committee_students', 'student_id', 'committee_id')
            ->withPivot('registration_id');
    }
}
