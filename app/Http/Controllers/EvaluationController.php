<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\StoreBulkEvaluationRequest;
use App\Http\Requests\StoreAutosaveEvaluationRequest;
use App\Models\CommitteeStudent;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Registration;
use App\Models\User;
use App\Services\EvaluationScoreService;
use App\Services\AuditLogService;
use App\Services\CompetitionScoringRulesService;
use App\Services\CompetitionLifecycleService;
use App\Services\ResultCalculationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(CompetitionLifecycleService $lifecycle, ResultCalculationService $results)
    {
        Gate::authorize('viewAny', Evaluation::class);
        $authorizedQuery = Evaluation::query();
        if (auth()->user()->role === 'User') {
            $authorizedQuery->where(function ($scope) {
                $scope->whereHas('competition', fn ($competition) => $competition->where('created_by', auth()->id()))
                    ->orWhere(function ($assigned) {
                        $assigned->where('judge_id', auth()->id())->whereExists(function ($subquery) {
                            $subquery->selectRaw('1')->from('committee_judges')
                                ->join('committees', 'committees.id', '=', 'committee_judges.committee_id')
                                ->join('committee_students', 'committee_students.committee_id', '=', 'committees.id')
                                ->whereColumn('committee_judges.judge_id', 'evaluations.judge_id')
                                ->whereColumn('committee_students.registration_id', 'evaluations.registration_id')
                                ->whereColumn('committee_students.student_id', 'evaluations.student_id')
                                ->whereColumn('committees.competition_id', 'evaluations.competition_id')
                                ->whereColumn('committees.branch_id', 'evaluations.branch_id');
                        });
                    });
            });
        }

        $query = (clone $authorizedQuery)
            ->with(['competition', 'competitionBranch', 'student', 'judge', 'registration.committeeStudents.committee'])
            ->latest();

        $query->when(request('competition_id'), fn ($q, $id) => $q->where('competition_id', $id));
        $query->when(request('branch_id'), fn ($q, $id) => $q->where('branch_id', $id));
        $query->when(request('judge_id'), fn ($q, $id) => $q->where('judge_id', $id));
        $query->when(request('status'), fn ($q, $status) => $q->where('status', $status));
        if ($committeeId = request('committee_id')) {
            $query->whereExists(function ($subquery) use ($committeeId) {
                $subquery->selectRaw('1')->from('committee_students')
                    ->where('committee_students.committee_id', $committeeId)
                    ->whereColumn('committee_students.registration_id', 'evaluations.registration_id')
                    ->whereColumn('committee_students.student_id', 'evaluations.student_id');
            });
        }
        if ($search = request('search')) {
            $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%"));
        }

        $evaluations = $query->paginate(15)->withQueryString();

        $competitionIds = (clone $authorizedQuery)->select('competition_id')->distinct();
        $branchIds = (clone $authorizedQuery)->select('branch_id')->distinct();
        $judgeIds = (clone $authorizedQuery)->select('judge_id')->distinct();
        $committees = Committee::query()
            ->with(['competition', 'competitionBranch'])
            ->when(auth()->user()->role === 'User', fn ($q) => $q->where(function ($scope) {
                $scope->whereHas('competition', fn ($competition) => $competition->where('created_by', auth()->id()))
                    ->orWhereHas('users', fn ($users) => $users->whereKey(auth()->id()));
            }))
            ->orderBy('name')->get();
        $summary = [
            'total' => (clone $authorizedQuery)->count(),
            'pending' => (clone $authorizedQuery)->where('status', 'pending')->count(),
            'completed' => (clone $authorizedQuery)->where('status', 'submitted')->count(),
            'average_percentage' => round((float) ((clone $authorizedQuery)->whereNotNull('percentage')->avg('percentage') ?? 0), 2),
        ];
        $nextStageAvailable = false;
        if (request()->filled('competition_id') && request()->filled('branch_id')) {
            $competition = Competition::query()->find(request()->integer('competition_id'));
            $branch = $competition?->competitionBranches()->whereKey(request()->integer('branch_id'))->first();

            if ($branch !== null
                && $lifecycle->state($competition) === CompetitionLifecycleService::EVALUATION
                && Gate::allows('generate', $competition)) {
                try {
                    $results->assertEvaluationsComplete($competition, $branch->id);
                    $nextStageAvailable = true;
                } catch (ValidationException) {
                    // Keep the navigation unavailable until the existing result
                    // completion rules are satisfied.
                }
            }
        }
        $editableCommitteeIds = $evaluations->getCollection()
            ->filter(fn (Evaluation $evaluation) => (int) $evaluation->judge_id === (int) auth()->id()
                && $lifecycle->state($evaluation->competition) === CompetitionLifecycleService::EVALUATION)
            ->mapWithKeys(function (Evaluation $evaluation): array {
                $committeeIds = $evaluation->registration?->committeeStudents
                    ?->where('student_id', $evaluation->student_id)
                    ->pluck('committee_id')
                    ->unique()
                    ->values() ?? collect();

                return $committeeIds->count() === 1
                    ? [$evaluation->id => $committeeIds->first()]
                    : [];
            })
            ->all();

        return view('evaluation.index', [
            'evaluations' => $evaluations,
            'competitions' => Competition::query()->whereIn('id', $competitionIds)->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->whereIn('id', $branchIds)->orderBy('name')->get(),
            'judges' => User::query()->whereIn('id', $judgeIds)->orderBy('name')->get(),
            'statuses' => (clone $authorizedQuery)->select('status')->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
            'committees' => $committees,
            'summary' => $summary,
            'nextStageAvailable' => $nextStageAvailable,
            'editableCommitteeIds' => $editableCommitteeIds,
        ]);
    }

    public function create(CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('create', Evaluation::class);
        $assignments = CommitteeStudent::query()->with(['committee.competition', 'committee.competitionBranch', 'student', 'registration'])
            ->whereHas('committee.competition', fn ($query) => $query->whereIn('status', [
                CompetitionLifecycleService::EVALUATION,
                'active',
                'Open for Registration',
            ]))
            ->where(function ($query) {
                if (auth()->user()->role === 'Platform Admin') {
                    return;
                }
                $query->whereHas('committee.competition', fn ($q) => $q->where('created_by', auth()->id()))
                    ->orWhereHas('committee.users', fn ($users) => $users->whereKey(auth()->id()));
            })
            ->get();
        $evaluatedRegistrationIds = Evaluation::query()
            ->where('judge_id', auth()->id())
            ->whereIn('registration_id', $assignments->pluck('registration_id'))
            ->pluck('registration_id');
        $assignments = $assignments->reject(fn ($assignment) => $evaluatedRegistrationIds->contains($assignment->registration_id));
        $branches = CompetitionBranch::query()->when(auth()->user()->role === 'User', fn ($q) => $q->whereHas('competition', fn ($c) => $c->where('created_by', auth()->id())))->orderBy('name')->get();
        return view('evaluation.create', ['assignments' => $assignments, 'branches' => $branches, 'evaluator' => User::findOrFail(auth()->id())]);
    }

    public function store(StoreEvaluationRequest $request, EvaluationScoreService $scoreService, AuditLogService $audit, CompetitionScoringRulesService $scoringRules, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('create', Evaluation::class);
        $data = $request->validated();
        $branch = CompetitionBranch::query()->with('competition')->findOrFail($data['branch_id']);
        $lifecycle->assertAllowsEvaluation($branch->competition);
        $criteria = $scoringRules->criteriaForBranch($branch);
        $snapshot = collect($criteria)->values()->map(fn (array $criterion, int $index) => [
            'name' => $criterion['name'],
            'score' => (float) data_get($data, "scores.$index.score"),
            'max_score' => (float) $criterion['max_score'],
        ])->all();
        $legacyScores = collect($snapshot)->keyBy('name');
        $data['judge_id'] = $request->user()->id;
        $data['status'] = 'submitted';
        $data['scores'] = $snapshot;
        $data['memorization_score'] = data_get($legacyScores->get('الحفظ'), 'score', 0);
        $data['tajweed_score'] = data_get($legacyScores->get('التجويد'), 'score', 0);
        $data['performance_score'] = data_get($legacyScores->get('الأداء'), 'score', 0);
        $data['discipline_score'] = 0;
        unset($data['scores']);
        $data += $scoreService->calculate(collect($snapshot)->pluck('score')->all(), $scoringRules->maximumForBranch($branch));
        $data['scores'] = $snapshot;
        try {
            $evaluation = Evaluation::create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'registration_id' => 'تم تسجيل تقييم لهذا الطالب بواسطة الحكم نفسه من قبل.',
            ]);
        }
        $audit->record($request->user()->id, 'submitted', $evaluation, null, null, $evaluation->only(['competition_id', 'branch_id', 'student_id', 'registration_id', 'total_score', 'percentage', 'status']));
        return redirect()->route('evaluations.show', $evaluation)->with('status', 'تم إرسال التقييم بنجاح.');
    }

    public function bulk(Committee $committee, CompetitionLifecycleService $lifecycle)
    {
        abort_unless($this->canEnterCommittee($committee), 403);
        $lifecycle->assertAllowsEvaluation($committee->competition()->firstOrFail());
        $committee->load(['competition', 'competitionBranch']);
        $query = CommitteeStudent::query()->with(['student', 'registration'])
            ->where('committee_id', $committee->id)
            ->whereHas('registration', fn ($q) => $q->where('competition_id', $committee->competition_id)->where('branch_id', $committee->branch_id)->where('status', 'approved'));
        if ($search = request('search')) {
            $query->where(fn ($q) => $q->whereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%"))->orWhereHas('registration', fn ($r) => $r->where('registration_number', 'like', "%{$search}%")));
        }
        if (request('status') === 'evaluated') {
            $query->whereExists(fn ($sub) => $sub->selectRaw('1')->from('evaluations')
                ->whereColumn('evaluations.registration_id', 'committee_students.registration_id')
                ->where('evaluations.judge_id', auth()->id()));
        } elseif (request('status') === 'unevaluated') {
            $query->whereNotExists(fn ($sub) => $sub->selectRaw('1')->from('evaluations')
                ->whereColumn('evaluations.registration_id', 'committee_students.registration_id')
                ->where('evaluations.judge_id', auth()->id()));
        }
        $total = (clone $query)->count();
        $completed = Evaluation::query()->where('judge_id', auth()->id())->whereIn('registration_id', (clone $query)->select('registration_id'))->count();
        $rows = $query->orderBy('id')->paginate(50)->withQueryString();
        $evaluations = Evaluation::query()->where('judge_id', auth()->id())->whereIn('registration_id', $rows->pluck('registration_id'))->get()->keyBy('registration_id');
        return view('evaluation.bulk', ['committee' => $committee, 'rows' => $rows, 'evaluations' => $evaluations, 'criteria' => app(CompetitionScoringRulesService::class)->criteriaForBranch($committee->competitionBranch), 'summary' => ['total' => $total, 'completed' => $completed, 'remaining' => max(0, $total - $completed)]]);
    }

    public function bulkStore(StoreBulkEvaluationRequest $request, Committee $committee, EvaluationScoreService $scoreService, AuditLogService $audit, CompetitionScoringRulesService $scoringRules, CompetitionLifecycleService $lifecycle)
    {
        abort_unless($this->canEnterCommittee($committee), 403);
        $lifecycle->assertAllowsEvaluation($committee->competition()->firstOrFail());
        $criteria = $scoringRules->criteriaForBranch($committee->competitionBranch);
        $maximum = $scoringRules->maximumForBranch($committee->competitionBranch);
        $saved = 0;
        DB::transaction(function () use ($request, $committee, $criteria, $maximum, $scoreService, &$saved): void {
            foreach ($request->validated('rows', []) as $row) {
                $rawScores = $row['scores'] ?? [];
                $values = collect($rawScores)->map(fn ($score) => is_array($score) ? ($score['score'] ?? null) : null);
                if ($values->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) continue;
                $scores = $values->map(fn ($value) => (float) $value)->values();
                $registration = Registration::with('student')->findOrFail($row['registration_id']);
                $snapshot = collect($criteria)->values()->map(fn (array $criterion, int $index) => ['name' => $criterion['name'], 'score' => (float) data_get($rawScores, "$index.score"), 'max_score' => (float) $criterion['max_score']])->all();
                $legacy = collect($snapshot)->keyBy('name');
                $data = ['competition_id' => $committee->competition_id, 'branch_id' => $committee->branch_id, 'student_id' => $registration->student_id, 'registration_id' => $registration->id, 'judge_id' => auth()->id(), 'memorization_score' => data_get($legacy->get('الحفظ'), 'score', 0), 'tajweed_score' => data_get($legacy->get('التجويد'), 'score', 0), 'performance_score' => data_get($legacy->get('الأداء'), 'score', 0), 'discipline_score' => data_get($legacy->get('الانضباط'), 'score', 0), 'scores' => $snapshot, 'status' => 'submitted', 'notes' => $row['notes'] ?? null] + $scoreService->calculate($scores->all(), $maximum);
                Evaluation::updateOrCreate(['registration_id' => $registration->id, 'judge_id' => auth()->id()], $data);
                $saved++;
            }
        });
        $audit->record($request->user()->id, 'bulk_submitted', $committee, null, null, ['count' => $saved]);

        if ($request->boolean('save_and_next')) {
            return redirect()->route('evaluations.index')->with('status', "تم حفظ {$saved} تقييماً بنجاح.");
        }

        $page = $request->integer('page', 1);
        return redirect()->route('committees.evaluations.bulk', [$committee, 'page' => $page])->with('status', "تم حفظ {$saved} تقييماً بنجاح.");
    }

    public function autosave(StoreAutosaveEvaluationRequest $request, Committee $committee, Registration $registration, EvaluationScoreService $scoreService, AuditLogService $audit, CompetitionScoringRulesService $scoringRules, CompetitionLifecycleService $lifecycle)
    {
        abort_unless($this->canEnterCommittee($committee), 403);
        $lifecycle->assertAllowsEvaluation($committee->competition()->firstOrFail());
        abort_unless((int) $request->integer('registration_id') === (int) $registration->id, 422);
        $criteria = $scoringRules->criteriaForBranch($committee->competitionBranch);
        $rawScores = $request->validated('scores');
        $snapshot = collect($criteria)->values()->map(fn (array $criterion, int $index) => [
            'name' => $criterion['name'], 'score' => (float) data_get($rawScores, "$index.score"), 'max_score' => (float) $criterion['max_score'],
        ])->all();
        $legacy = collect($snapshot)->keyBy('name');
        $data = [
            'competition_id' => $committee->competition_id, 'branch_id' => $committee->branch_id,
            'student_id' => $registration->student_id, 'registration_id' => $registration->id,
            'judge_id' => $request->user()->id, 'memorization_score' => data_get($legacy->get('الحفظ'), 'score', 0),
            'tajweed_score' => data_get($legacy->get('التجويد'), 'score', 0), 'performance_score' => data_get($legacy->get('الأداء'), 'score', 0),
            'discipline_score' => data_get($legacy->get('الانضباط'), 'score', 0), 'scores' => $snapshot,
            'status' => 'submitted', 'notes' => $request->validated('notes'),
        ] + $scoreService->calculate(collect($snapshot)->pluck('score')->all(), $scoringRules->maximumForBranch($committee->competitionBranch));
        $evaluation = Evaluation::updateOrCreate(['registration_id' => $registration->id, 'judge_id' => $request->user()->id], $data);
        $audit->record($request->user()->id, 'autosaved', $evaluation, null, null, $evaluation->only(['competition_id', 'branch_id', 'student_id', 'registration_id', 'total_score', 'percentage', 'status']));
        return response()->json(['message' => 'تم الحفظ', 'evaluation_id' => $evaluation->id, 'scores' => $snapshot]);
    }

    private function canEnterCommittee(Committee $committee): bool
    {
        if (auth()->user()->role === 'Platform Admin') return true;
        return $committee->competition?->created_by === auth()->id() || $committee->committeeJudges()->where('judge_id', auth()->id())->exists();
    }

    public function show(Evaluation $evaluation)
    {
        Gate::authorize('view', $evaluation);
        $evaluation->load(['competition', 'competitionBranch', 'student', 'registration', 'judge']);
        $evaluation->load('registration.committeeStudents.committee');
        $committee = $evaluation->registration?->committeeStudents
            ->firstWhere('student_id', $evaluation->student_id)?->committee;
        return view('evaluation.show', compact('evaluation', 'committee'));
    }
}
