<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class CompetitionBranch extends Model
{
    protected $fillable = [
        'competition_id',
        'competition_level_id',
        'name',
        'description',
        'memorization_amount',
        'min_age',
        'max_age',
        'total_score',
        'passing_score',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'total_score' => 'decimal:2',
            'passing_score' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (CompetitionBranch $branch): void {
            if ($branch->isDirty() && $branch->hasHistoricalRecords()) {
                throw new LogicException('لا يمكن تعديل مستوى مرتبط بسجلات تاريخية.');
            }
        });
    }

    public function hasHistoricalRecords(): bool
    {
        return $this->registrations()->exists()
            || $this->evaluations()->exists()
            || $this->results()->exists()
            || $this->certificates()->exists();
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function level()
    {
        return $this->belongsTo(CompetitionLevel::class, 'competition_level_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'branch_id');
    }

    public function committees()
    {
        return $this->hasMany(Committee::class, 'branch_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'branch_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'branch_id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'branch_id');
    }
}
