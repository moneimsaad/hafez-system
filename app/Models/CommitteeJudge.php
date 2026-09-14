<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeJudge extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'committee_id',
        'judge_id',
    ];

    public function committee()
    {
        return $this->belongsTo(Committee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}
