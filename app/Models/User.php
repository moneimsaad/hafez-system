<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'organization_name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function competitions()
    {
        return $this->hasMany(Competition::class, 'created_by');
    }

    public function competitionLevels()
    {
        return $this->hasMany(CompetitionLevel::class, 'created_by');
    }

    public function committeeJudges()
    {
        return $this->hasMany(CommitteeJudge::class, 'judge_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'judge_id');
    }

    public function approvedResults()
    {
        return $this->hasMany(Result::class, 'approved_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function committees()
    {
        return $this->belongsToMany(Committee::class, 'committee_judges', 'judge_id', 'committee_id');
    }
}
