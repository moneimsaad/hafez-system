<?php

namespace App\Http\Controllers;

use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(ReportService $reports)
    {
        $user = auth()->user();
        return view('reports.index', [
            'statistics' => $reports->statistics($user),
            'competitions' => $reports->competitionReport($user),
            'registrations' => $reports->registrationReport($user),
            'resultsSummary' => $reports->resultsSummary($user),
            'certificatesSummary' => $reports->certificatesSummary($user),
        ]);
    }
}
