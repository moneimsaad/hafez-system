<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionBranchRequest;
use App\Http\Requests\UpdateCompetitionBranchRequest;
use App\Models\CompetitionBranch;
use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use App\Services\CompetitionLevelAssignmentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompetitionBranchController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', CompetitionBranch::class);
        $query = CompetitionLevel::query()
            ->where('type', 'organizer')
            ->where('created_by', auth()->id())
            ->where('status', 'active')
            ->withCount('assignments');
        if ($search = request('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        $levels = $query->latest()->paginate(15)->withQueryString();
        return view('competition_branch.index', compact('levels'));
    }

    public function create()
    {
        Gate::authorize('create', CompetitionBranch::class);
        return view('competition_branch.create');
    }

    public function store(Request $request, AuditLogService $audit, CompetitionLevelAssignmentSyncService $sync)
    {
        Gate::authorize('create', CompetitionBranch::class);
        if ($request->boolean('reusable_level')) {
            $data = $this->validateReusableLevel($request);
            $level = CompetitionLevel::create([...$data, 'created_by' => $request->user()->id, 'type' => 'organizer', 'status' => 'active']);
            $audit->record($request->user()->id, 'created', $level, null, null, $level->only(['name', 'type']));
            return redirect()->route('competition-branches.index')->with('status', 'تم إنشاء المستوى بنجاح.');
        }
        $data = app(StoreCompetitionBranchRequest::class)->validated();
        $branch = $sync->createFromData($data);
        $audit->record($request->user()->id, 'created', $branch, null, null, $branch->only(['competition_id', 'name']));
        return redirect()->route('competition-branches.show', $branch)->with('status', 'تمت إضافة مستوى المسابقة بنجاح.');
    }

    public function createExistingLevel(Competition $competition)
    {
        Gate::authorize('update', $competition);

        $assignedLevelIds = CompetitionLevelAssignment::query()
            ->where('competition_id', $competition->id)
            ->pluck('competition_level_id');
        $ownedDefaults = CompetitionLevel::query()->where('type', 'organizer')->where('created_by', auth()->id())->pluck('name');
        $levels = CompetitionLevel::query()
            ->where('status', 'active')
            ->whereNotIn('id', $assignedLevelIds)
            ->where(fn ($query) => $query->where(fn ($system) => $system->where('type', 'system')->whereNotIn('name', $ownedDefaults))
                ->orWhere(fn ($owned) => $owned->where('type', 'organizer')->where('created_by', auth()->id())))
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('competition_branch.add-existing', compact('competition', 'levels'));
    }

    public function editReusable(CompetitionLevel $competitionLevel)
    {
        abort_unless($competitionLevel->type === 'organizer' && (int) $competitionLevel->created_by === (int) auth()->id(), 403);
        return view('competition_branch.edit-reusable', compact('competitionLevel'));
    }

    public function updateReusable(Request $request, CompetitionLevel $competitionLevel)
    {
        abort_unless($competitionLevel->type === 'organizer' && (int) $competitionLevel->created_by === (int) auth()->id(), 403);
        if ($competitionLevel->compatibilityBranches()->get()->contains(fn (CompetitionBranch $branch) => $branch->hasHistoricalRecords())) {
            return back()->withInput()->withErrors(['name' => 'لا يمكن تعديل مستوى مرتبط بسجلات تاريخية.']);
        }
        $competitionLevel->update($this->validateReusableLevel($request));
        return redirect()->route('competition-branches.index')->with('status', 'تم تحديث المستوى بنجاح.');
    }

    private function validateReusableLevel(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'memorization_amount' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_min_age' => ['nullable', 'integer', 'min:0'],
            'default_max_age' => ['nullable', 'integer', 'min:0'],
            'default_total_score' => ['nullable', 'numeric', 'min:0'],
            'default_passing_score' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'default_min_age' => 'الحد الأدنى للعمر',
            'default_max_age' => 'الحد الأقصى للعمر',
            'default_total_score' => 'الدرجة الكلية',
            'default_passing_score' => 'درجة النجاح',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $hasMin = $request->filled('default_min_age');
            $hasMax = $request->filled('default_max_age');
            if ($hasMin xor $hasMax) {
                $validator->errors()->add($hasMin ? 'default_max_age' : 'default_min_age', $hasMin
                    ? 'يجب تحديد الحد الأقصى للعمر عند تحديد الحد الأدنى.'
                    : 'يجب تحديد الحد الأدنى للعمر عند تحديد الحد الأقصى.');
            } elseif ($hasMin && (int) $request->input('default_max_age') < (int) $request->input('default_min_age')) {
                $validator->errors()->add('default_max_age', 'يجب أن يكون الحد الأقصى للعمر أكبر من أو يساوي الحد الأدنى.');
            }

            $hasTotal = $request->filled('default_total_score');
            $hasPassing = $request->filled('default_passing_score');
            if ($hasTotal xor $hasPassing) {
                $validator->errors()->add($hasTotal ? 'default_passing_score' : 'default_total_score', $hasTotal
                    ? 'يجب تحديد درجة النجاح عند تحديد الدرجة الكلية.'
                    : 'يجب تحديد الدرجة الكلية عند تحديد درجة النجاح.');
            } elseif ($hasTotal && (float) $request->input('default_passing_score') > (float) $request->input('default_total_score')) {
                $validator->errors()->add('default_passing_score', 'درجة النجاح يجب ألا تتجاوز الدرجة الكلية.');
            }
        });

        return $validator->validate();
    }

    public function destroyReusable(CompetitionLevel $competitionLevel)
    {
        abort_unless($competitionLevel->type === 'organizer' && (int) $competitionLevel->created_by === (int) auth()->id(), 403);
        $used = $competitionLevel->isUsed();
        if ($used) $competitionLevel->update(['status' => 'archived']); else $competitionLevel->delete();
        return back()->with('status', $used ? 'تمت أرشفة المستوى لحفظ السجل التاريخي.' : 'تم حذف المستوى بنجاح.');
    }

    public function restoreReusable(CompetitionLevel $competitionLevel)
    {
        abort_unless($competitionLevel->type === 'organizer' && (int) $competitionLevel->created_by === (int) auth()->id(), 403);
        $competitionLevel->update(['status' => 'active']);
        return back()->with('status', 'تمت استعادة المستوى.');
    }

    public function storeExistingLevel(Competition $competition, StoreCompetitionBranchRequest $request, AuditLogService $audit, CompetitionLevelAssignmentSyncService $sync)
    {
        Gate::authorize('update', $competition);
        $data = $request->validated();
        $data['competition_id'] = $competition->id;
        $level = CompetitionLevel::findOrFail($data['competition_level_id']);
        $branch = $sync->createBranch($data, $level);
        $audit->record($request->user()->id, 'created', $branch, null, null, $branch->only(['competition_id', 'name']));

        return redirect()->route('competition-branches.index', ['competition_id' => $competition->id])
            ->with('status', 'تمت إضافة المستوى إلى المسابقة بنجاح.');
    }

    public function show(CompetitionBranch $competitionBranch)
    {
        Gate::authorize('view', $competitionBranch);
        $competitionBranch->load(['competition', 'level']);
        return view('competition_branch.show', compact('competitionBranch'));
    }

    public function edit(CompetitionBranch $competitionBranch)
    {
        Gate::authorize('update', $competitionBranch);
        return view('competition_branch.edit', [
            'competitionBranch' => $competitionBranch,
            'competitions' => Competition::query()
                ->when(auth()->user()->role === 'User', fn ($q) => $q->where('created_by', auth()->id()))
                ->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateCompetitionBranchRequest $request, CompetitionBranch $competitionBranch, AuditLogService $audit, CompetitionLevelAssignmentSyncService $sync)
    {
        Gate::authorize('update', $competitionBranch);
        $old = $competitionBranch->getAttributes();
        $data = $request->validated();
        if ($competitionBranch->hasHistoricalRecords()) {
            return back()->withInput()->withErrors(['name' => 'لا يمكن تعديل مستوى مرتبط بسجلات تاريخية.']);
        }
        if ($competitionBranch->competition_id && empty($data['competition_id']) && ($competitionBranch->registrations()->exists() || $competitionBranch->committees()->exists() || $competitionBranch->evaluations()->exists() || $competitionBranch->results()->exists() || $competitionBranch->certificates()->exists())) {
            return back()->withInput()->withErrors(['competition_id' => 'لا يمكن إلغاء ربط مستوى مستخدم بسجلات تاريخية.']);
        }
        $competitionBranch->update($data);
        if (! $competitionBranch->competition_id) {
            CompetitionLevelAssignment::query()->where('competition_level_id', $competitionBranch->competition_level_id)->delete();
        }
        $sync->syncBranch($competitionBranch);
        $audit->record($request->user()->id, 'updated', $competitionBranch, null, $old, $competitionBranch->getAttributes());
        return redirect()->route('competition-branches.show', $competitionBranch)->with('status', 'تم تحديث مستوى المسابقة بنجاح.');
    }

    public function destroy(CompetitionBranch $competitionBranch, AuditLogService $audit)
    {
        Gate::authorize('delete', $competitionBranch);
        $hasHistory = $competitionBranch->registrations()->exists()
            || $competitionBranch->committees()->exists()
            || $competitionBranch->evaluations()->exists()
            || $competitionBranch->results()->exists()
            || $competitionBranch->certificates()->exists();

        if ($hasHistory) {
            return back()->with('error', 'لا يمكن حذف مستوى مرتبط بسجلات تاريخية.');
        }

        $old = $competitionBranch->getAttributes();
        DB::transaction(function () use ($competitionBranch): void {
            CompetitionLevelAssignment::query()
                ->where('competition_id', $competitionBranch->competition_id)
                ->where('competition_level_id', $competitionBranch->competition_level_id)
                ->delete();
            $competitionBranch->delete();
        });
        $audit->record(auth()->id(), 'deleted', $competitionBranch, $competitionBranch->getKey(), $old, null);
        return redirect()->route('competition-branches.index')->with('status', 'تم حذف مستوى المسابقة بنجاح.');
    }
}
