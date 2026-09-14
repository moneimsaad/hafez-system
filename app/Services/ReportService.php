<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Competition;
use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Student;
use App\Models\CompetitionBranch;
use App\Models\User;

class ReportService
{
    public function statistics(User $user): array
    {
        $competition = $this->competitionScope($user);
        $competitionIds = (clone $competition)->select('id');
        return [
            'users' => $user->role === 'Platform Admin' ? User::query()->count() : null,
            'competitions' => (clone $competition)->count(),
            'branches' => CompetitionBranch::whereIn('competition_id', $competitionIds)->count(),
            'students' => Student::whereHas('registrations', fn ($q) => $q->whereIn('competition_id', $competitionIds))->distinct()->count('students.id'),
            'registrations' => Registration::whereIn('competition_id', $competitionIds)->count(),
            'committees' => Committee::whereIn('competition_id', $competitionIds)->count(),
            'evaluations' => Evaluation::whereIn('competition_id', $competitionIds)->count(),
            'results' => Result::whereIn('competition_id', $competitionIds)->count(),
            'successful_results' => Result::whereIn('competition_id', $competitionIds)->where('result_status', 'successful')->count(),
            // Results are final as soon as they are generated; keep the legacy
            // response key for report compatibility without approval filtering.
            'approved_results' => Result::whereIn('competition_id', $competitionIds)->count(),
            'certificates' => Certificate::whereIn('competition_id', $competitionIds)->count(),
        ];
    }

    public function competitionReport(User $user)
    {
        return $this->competitionScope($user)->withCount(['competitionBranches', 'registrations', 'committees', 'evaluations', 'results', 'certificates'])->latest()->paginate(15, ['*'], 'competitions_page');
    }

    public function registrationReport(User $user)
    {
        $query = Registration::with(['student', 'competition', 'competitionBranch'])->latest('registered_at');
        return $this->scopeByCompetition($query, $user)->paginate(15, ['*'], 'registrations_page');
    }

    public function resultsSummary(User $user): array
    {
        $query = Result::query();
        $query = $this->scopeByCompetition($query, $user);
        return $query->selectRaw('result_status, COUNT(*) as total')->groupBy('result_status')->pluck('total', 'result_status')->all();
    }

    public function certificatesSummary(User $user): array
    {
        $query = Certificate::query();
        $query = $this->scopeByCompetition($query, $user);
        return $query->selectRaw('certificate_type, COUNT(*) as total')->groupBy('certificate_type')->pluck('total', 'certificate_type')->all();
    }

    private function competitionScope(User $user)
    {
        return Competition::query()->when($user->role === 'User', fn ($q) => $q->where('created_by', $user->id));
    }

    private function scopeByCompetition($query, User $user)
    {
        return $user->role === 'User'
            ? $query->whereHas('competition', fn ($competition) => $competition->where('created_by', $user->id))
            : $query;
    }
}
