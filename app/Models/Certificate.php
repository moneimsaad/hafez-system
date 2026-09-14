<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'competition_id',
        'branch_id',
        'result_id',
        'certificate_type',
        'certificate_number',
        'qr_code',
        'file_path',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitionBranch()
    {
        return $this->belongsTo(CompetitionBranch::class, 'branch_id');
    }

    public function result()
    {
        return $this->belongsTo(Result::class);
    }
}
