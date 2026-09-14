<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeStudent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'committee_id',
        'student_id',
        'registration_id',
    ];

    public function committee()
    {
        return $this->belongsTo(Committee::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
