<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateResultRequest;
use App\Http\Requests\BulkCertificateRequest;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Result;
use App\Services\CertificateGenerationService;
use App\Services\ResultCalculationService;
use Illuminate\Support\Facades\Gate;
use App\Services\AuditLogService;
use App\Services\CompetitionLifecycleService;

class ResultController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Result::class);
        $authorizedQuery = Result::query();
        if (auth()->user()->role === 'User') {
            $authorizedQuery->whereHas('competition', fn($competition) => $competition->where('created_by', auth()->id()));
        }

        $query = (clone $authorizedQuery)->with(['competition', 'competitionBranch', 'student'])->latest('id');
        if ($search = request('search')) {
            $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%"));
        }
        $query->when(request('competition_id'), fn ($q, $id) => $q->where('competition_id', $id));
        $query->when(request('branch_id'), fn ($q, $id) => $q->where('branch_id', $id));
        $query->when(request('status'), fn ($q, $status) => $q->where('result_status', $status));
        if (request()->filled('rank') && is_numeric(request('rank'))) {
            $query->where('rank', (int) request('rank'));
        }
        $summary = [
            'total' => (clone $authorizedQuery)->count(),
            'successful' => (clone $authorizedQuery)->where('result_status', 'successful')->count(),
            'failed' => (clone $authorizedQuery)->where('result_status', 'failed')->count(),
        ];
        $results = $query->with('certificates')->paginate(15)->withQueryString();

        $competitionIds = (clone $authorizedQuery)->select('competition_id')->distinct();
        $branchIds = (clone $authorizedQuery)->select('branch_id')->distinct();
        $generationCompetitions = Competition::query()
            ->with('competitionBranches')
            ->when(auth()->user()->role === 'User', fn ($q) => $q->where('created_by', auth()->id()))
            ->whereHas('competitionBranches')
            ->orderBy('title')
            ->get();
        return view('result.index', [
            'results' => $results,
            'competitions' => Competition::query()->whereIn('id', $competitionIds)->orderBy('title')->get(),
            'generationCompetitions' => $generationCompetitions,
            'branches' => CompetitionBranch::query()->with('competition')->whereIn('id', $branchIds)->orderBy('name')->get(),
            'statuses' => (clone $authorizedQuery)->select('result_status')->whereNotNull('result_status')->distinct()->orderBy('result_status')->pluck('result_status'),
            'summary' => $summary,
        ]);
    }

    public function show(Result $result)
    {
        $result->load('competition');
        Gate::authorize('view', $result);
        $result->load(['competitionBranch', 'student', 'registration', 'certificates']);
        return view('result.show', compact('result'));
    }

    public function generate(GenerateResultRequest $request, ResultCalculationService $calculator, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        $data = $request->validated();
        $competition = Competition::findOrFail($data['competition_id']);
        Gate::authorize('generate', $competition);
        $lifecycle->assertAllowsResultGeneration($competition);
        $results = $calculator->generate($competition, (int) $data['branch_id']);
        foreach ($results as $result) {
            $audit->record($request->user()->id, 'generated', $result, null, null, $result->only(['competition_id', 'branch_id', 'student_id', 'registration_id', 'final_score', 'percentage', 'rank', 'result_status']));
        }
        return redirect()->route('results.index')->with('status', 'تم توليد '.$results->count().' نتيجة بنجاح.');
    }

    public function issueCertificate(Result $result, CertificateGenerationService $generator, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('generate', [\App\Models\Certificate::class, $result]);
        $lifecycle->assertAllowsCertificateGeneration($result->competition()->firstOrFail());
        if ($certificate = $result->certificates()->first()) {
            return redirect()->route('certificates.show', $certificate)->with('status', 'تم إصدار شهادة لهذه النتيجة مسبقاً.');
        }
        $certificate = $generator->generate($result);
        $audit->record(request()->user()->id, 'generated', $certificate, null, null, $certificate->only(['result_id', 'certificate_number', 'file_path', 'issued_at']));
        return redirect()->route('certificates.show', $certificate)->with('status', 'تم إصدار الشهادة بنجاح.');
    }

    public function issueCertificates(BulkCertificateRequest $request, CertificateGenerationService $generator, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        $ids = $request->validated('result_ids');
        $results = Result::query()->with(['competition', 'certificates'])->whereIn('id', $ids)->get()->keyBy('id');
        abort_if($results->count() !== count($ids), 422);
        foreach ($results as $result) {
            Gate::authorize('generate', [\App\Models\Certificate::class, $result]);
            $lifecycle->assertAllowsCertificateGeneration($result->competition);
            if ($result->certificates->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['result_ids' => 'تتضمن القائمة نتيجة تم إصدار شهادة لها مسبقاً.']);
            }
        }

        $issued = 0;
        $failed = 0;
        foreach ($results as $result) {
            try {
                $certificate = $generator->generate($result);
                $audit->record($request->user()->id, 'generated', $certificate, null, null, $certificate->only(['result_id', 'certificate_number', 'file_path', 'issued_at']));
                $issued++;
            } catch (\Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $message = $failed > 0
            ? "تم إصدار {$issued} شهادة تقدير، وتعذر إصدار {$failed} شهادة."
            : "تم إصدار {$issued} شهادة تقدير بنجاح.";
        return redirect()->route('results.index', $request->except('result_ids'))->with('status', $message);
    }

}
