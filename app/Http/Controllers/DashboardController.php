<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Competition;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(ReportService $reports)
    {
        $user = auth()->user();
        $activities = AuditLog::query()->with('user')->latest('created_at');

        if ($user->role === 'User') {
            $ownedCompetitionIds = Competition::query()->where('created_by', $user->id)->select('id');
            $activities->where(function ($query) use ($user, $ownedCompetitionIds) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($related) use ($ownedCompetitionIds) {
                        $related->where('table_name', 'competitions')->whereIn('record_id', $ownedCompetitionIds);
                    })
                    ->orWhere(function ($related) use ($ownedCompetitionIds) {
                        $related->where('table_name', 'competition_branches')->whereIn('record_id', function ($ids) use ($ownedCompetitionIds) {
                            $ids->from('competition_branches')->whereIn('competition_id', $ownedCompetitionIds)->select('id');
                        });
                    })
                    ->orWhere(function ($related) use ($ownedCompetitionIds) {
                        foreach (['registrations', 'committees', 'evaluations', 'results', 'certificates'] as $table) {
                            $related->orWhere(function ($item) use ($table, $ownedCompetitionIds) {
                                $item->where('table_name', $table)->whereIn('record_id', function ($ids) use ($table, $ownedCompetitionIds) {
                                    $ids->from($table)->whereIn('competition_id', $ownedCompetitionIds)->select('id');
                                });
                            });
                        }
                    });
            });
        }

        return view('dashboard.index', [
            'statistics' => $reports->statistics($user),
            'activities' => $activities->limit(10)->get(),
        ]);
    }
}
