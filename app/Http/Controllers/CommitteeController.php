<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignCommitteeJudgesRequest;
use App\Http\Requests\AssignCommitteeStudentsRequest;
use App\Http\Requests\StoreCommitteeManualJudgeRequest;
use App\Http\Requests\StoreCommitteeRequest;
use App\Http\Requests\UpdateCommitteeRequest;
use App\Models\Committee;
use App\Models\CommitteeJudge;
use App\Models\CommitteeStudent;
use App\Models\CommitteeManualJudge;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use App\Services\CompetitionLifecycleService;

class CommitteeController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Committee::class);
        $authorizedQuery = Committee::query()
            ->when(auth()->user()->role === 'User', fn ($q) => $q->whereHas('competition', fn ($c) => $c->where('created_by', auth()->id())));

        $query = (clone $authorizedQuery)
            ->with(['competition', 'competitionBranch'])
            ->withCount(['users', 'manualJudges', 'students'])
            ->latest();
        if ($search = request('search')) {
            $query->where(function ($scope) use ($search) {
                $scope->where('name', 'like', "%{$search}%")
                    ->orWhereHas('competition', fn ($competition) => $competition->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('competitionBranch', fn ($branch) => $branch->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('users', fn ($judge) => $judge->where('name', 'like', "%{$search}%"));
            });
        }
        $query->when(request('competition_id'), fn ($q, $id) => $q->where('competition_id', $id));
        $query->when(request('branch_id'), fn ($q, $id) => $q->where('branch_id', $id));

        $competitionIds = (clone $authorizedQuery)->select('competition_id')->distinct();
        $branchIds = (clone $authorizedQuery)->select('branch_id')->distinct();
        $authorizedCommitteeIds = (clone $authorizedQuery)->select('id');
        $linkedJudgeCount = CommitteeJudge::query()->whereIn('committee_id', $authorizedCommitteeIds)->count();
        $manualJudgeCount = CommitteeManualJudge::query()->whereIn('committee_id', $authorizedCommitteeIds)->count();

        return view('committee.index', [
            'committees' => $query->paginate(15)->withQueryString(),
            'competitions' => Competition::query()->whereIn('id', $competitionIds)->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->whereIn('id', $branchIds)->orderBy('name')->get(),
            'summary' => [
                'committees' => (clone $authorizedQuery)->count(),
                'judges' => $linkedJudgeCount + $manualJudgeCount,
                'students' => CommitteeStudent::query()->whereIn('committee_id', $authorizedCommitteeIds)->count(),
            ],
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Committee::class);
        return view('committee.create', [
            'competitions' => Competition::query()->when(auth()->user()->role === 'User', fn ($q) => $q->where('created_by', auth()->id()))->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->when(auth()->user()->role === 'User', fn ($q) => $q->whereHas('competition', fn ($c) => $c->where('created_by', auth()->id())))->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCommitteeRequest $request, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('create', Committee::class);
        $lifecycle->assertAllowsCommitteeManagement(Competition::findOrFail($request->integer('competition_id')));
        $committee = Committee::create($request->validated());
        $audit->record($request->user()->id, 'created', $committee, null, null, $committee->only(['competition_id', 'branch_id', 'name', 'exam_date', 'location']));
        return redirect()->route('committees.show', $committee)->with('status', 'تم إنشاء اللجنة بنجاح.');
    }

    public function show(Committee $committee)
    {
        Gate::authorize('view', $committee);
        // Do not load platform User records for this screen. Linking platform
        // judge accounts is intentionally unavailable until its secure flow is
        // complete, and a committee owner must not browse the user directory.
        $committee->load(['competition', 'competitionBranch', 'committeeJudges', 'manualJudges']);
        $committee->loadCount('students');
        $assignedRegistrationIds = CommitteeStudent::query()
            ->where('committee_id', $committee->id)
            ->pluck('registration_id')
            ->all();
        $eligibleRegistrations = $this->eligibleRegistrations($committee)->with('student');
        return view('committee.show', [
            'committee' => $committee,
            'registrations' => $eligibleRegistrations->orderBy('id')->paginate(50)->withQueryString(),
            'eligibleRegistrationCount' => $this->eligibleRegistrations($committee)->count(),
            'assignedRegistrationIds' => $assignedRegistrationIds,
        ]);
    }

    public function edit(Committee $committee)
    {
        Gate::authorize('update', $committee);
        return view('committee.edit', [
            'committee' => $committee,
            'competitions' => Competition::query()->when(auth()->user()->role === 'User', fn ($q) => $q->where('created_by', auth()->id()))->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->when(auth()->user()->role === 'User', fn ($q) => $q->whereHas('competition', fn ($c) => $c->where('created_by', auth()->id())))->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCommitteeRequest $request, Committee $committee, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('update', $committee);
        $lifecycle->assertAllowsCommitteeManagement(Competition::findOrFail($request->integer('competition_id')));
        $old = $committee->getAttributes();
        $committee->update($request->validated());
        $audit->record($request->user()->id, 'updated', $committee, null, $old, $committee->getAttributes());
        return redirect()->route('committees.show', $committee)->with('status', 'تم تحديث اللجنة بنجاح.');
    }

    public function destroy(Committee $committee, AuditLogService $audit)
    {
        Gate::authorize('delete', $committee);
        $old = $committee->getAttributes();
        $committee->delete();
        $audit->record(auth()->id(), 'deleted', $committee, $committee->getKey(), $old, null);
        return redirect()->route('committees.index')->with('status', 'تم حذف اللجنة بنجاح.');
    }

    public function assignJudges(AssignCommitteeJudgesRequest $request, Committee $committee, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('assignJudges', $committee);
        $lifecycle->assertAllowsCommitteeAssignment($committee->competition()->firstOrFail());
        // Legacy judge-id payloads are deliberately treated as the same
        // unavailable feature. No account lookup or relation mutation occurs.
        $request->validated();

        return back()->with('warning', 'ميزة ربط الحكام بحساباتهم على المنصة قيد التطوير حالياً، وستتوفر قريباً.');
    }

    public function storeManualJudge(StoreCommitteeManualJudgeRequest $request, Committee $committee, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('assignJudges', $committee);
        $lifecycle->assertAllowsCommitteeAssignment($committee->competition()->firstOrFail());
        $manualJudge = CommitteeManualJudge::create(['committee_id' => $committee->id, 'name' => $request->validated('name')]);
        $audit->record($request->user()->id, 'manual_judge_added', $committee, null, null, ['manual_judge_id' => $manualJudge->id, 'name' => $manualJudge->name]);

        return back()->with('status', 'تمت إضافة الحكم بالاسم بنجاح.');
    }

    public function destroyManualJudge(Committee $committee, CommitteeManualJudge $manualJudge, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('assignJudges', $committee);
        $lifecycle->assertAllowsCommitteeAssignment($committee->competition()->firstOrFail());
        abort_unless($manualJudge->committee_id === $committee->id, 404);
        $old = $manualJudge->only(['id', 'name']);
        $manualJudge->delete();
        $audit->record(auth()->id(), 'manual_judge_deleted', $committee, null, $old, null);

        return back()->with('status', 'تم حذف الحكم بالاسم.');
    }

    public function assignStudents(AssignCommitteeStudentsRequest $request, Committee $committee, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('assignStudents', $committee);
        $lifecycle->assertAllowsCommitteeAssignment($committee->competition()->firstOrFail());
        $old = CommitteeStudent::query()->where('committee_id', $committee->id)->pluck('registration_id')->all();
        $registrationIds = $request->validated('registration_ids', []);
        $visibleRegistrationIds = $request->validated('visible_registration_ids', []);
        $selectAll = $request->boolean('select_all_accepted');
        $clearAll = $request->boolean('clear_all');
        $eligible = $this->eligibleRegistrations($committee);

        // Preserve the existing endpoint contract for integrations/tests that
        // submit selected registrations without a paginated visible-id list.
        $visibleRegistrationIds = $visibleRegistrationIds ?: $registrationIds;

        if (! $selectAll && ! $clearAll && $registrationIds !== []) {
            $eligibleSelected = (clone $eligible)->whereIn('id', $registrationIds)->count();
            if ($eligibleSelected !== count($registrationIds)) {
                return back()->withErrors(['registration_ids' => 'يمكن اختيار الطلاب المقبولين في مستوى هذه اللجنة فقط.']);
            }
        }
        if (! $selectAll && ! $clearAll && $visibleRegistrationIds !== []) {
            $eligibleVisible = (clone $eligible)->whereIn('id', $visibleRegistrationIds)->count();
            if ($eligibleVisible !== count($visibleRegistrationIds)) {
                return back()->withErrors(['registration_ids' => 'لا يمكن تحديث إلا الطلاب المقبولين في مستوى هذه اللجنة.']);
            }
        }

        DB::transaction(function () use ($committee, $registrationIds, $visibleRegistrationIds, $selectAll, $clearAll, $eligible): void {
            if ($clearAll) {
                CommitteeStudent::query()->where('committee_id', $committee->id)->delete();
                return;
            }

            if ($selectAll) {
                CommitteeStudent::query()->where('committee_id', $committee->id)->delete();
                (clone $eligible)->select(['id', 'student_id'])->orderBy('id')->chunkById(500, function ($registrations) use ($committee): void {
                    CommitteeStudent::query()->insert($registrations->map(fn ($registration) => [
                        'committee_id' => $committee->id,
                        'student_id' => $registration->student_id,
                        'registration_id' => $registration->id,
                    ])->all());
                });
                return;
            }

            if ($visibleRegistrationIds === []) {
                return;
            }

            CommitteeStudent::query()->where('committee_id', $committee->id)->whereIn('registration_id', $visibleRegistrationIds)->delete();
            $selected = (clone $eligible)->whereIn('id', $registrationIds)->get(['id', 'student_id']);
            if ($selected->isNotEmpty()) {
                CommitteeStudent::query()->insert($selected->map(fn ($registration) => [
                    'committee_id' => $committee->id,
                    'student_id' => $registration->student_id,
                    'registration_id' => $registration->id,
                ])->all());
            }
        });
        $new = CommitteeStudent::query()->where('committee_id', $committee->id)->pluck('registration_id')->all();
        $audit->record($request->user()->id, 'students_assigned', $committee, null, ['registration_ids' => $old], ['registration_ids' => $new]);
        return back()->with('status', 'تم تحديث طلاب اللجنة بنجاح.');
    }

    private function eligibleRegistrations(Committee $committee)
    {
        return Registration::query()
            ->where('competition_id', $committee->competition_id)
            ->where('branch_id', $committee->branch_id)
            ->where('status', 'approved')
            ->when(request('student_search'), function ($query, $search) {
                $query->where(fn ($scope) => $scope->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%")));
            });
    }
}
