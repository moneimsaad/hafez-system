<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Requests\TransitionCompetitionStatusRequest;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use App\Services\CompetitionScoringRulesService;
use App\Services\CompetitionLifecycleService;

class CompetitionController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Competition::class);
        $query = Competition::query()->when(auth()->user()->role === 'User', fn ($q) => $q->where('created_by', auth()->id()));
        if ($search = request('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%"));
        }
        $competitions = $query->with('creator')->withCount('competitionBranches')->latest()->paginate(15)->withQueryString();
        return view('competition.index', compact('competitions'));
    }

    public function create()
    {
        Gate::authorize('create', Competition::class);
        return view('competition.create');
    }

    public function store(StoreCompetitionRequest $request, AuditLogService $audit, CompetitionScoringRulesService $scoringRules)
    {
        Gate::authorize('create', Competition::class);
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'Draft';
        $data['publication_scope'] = $data['publication_scope'] ?? 'unlisted';
        $data['rules'] = $scoringRules->mergeCriteria(null, $request->boolean('custom_scoring'), $data['scoring_criteria'] ?? []);
        unset($data['custom_scoring'], $data['scoring_criteria']);
        $data['target_governorate'] = $data['publication_scope'] === 'governorate' ? ($data['target_governorate'] ?? null) : null;
        $competition = DB::transaction(function () use ($data) {
            // Serialize numbering per owner by locking the existing owner row.
            // This keeps MAX()+1 safe without changing the approved schema.
            User::query()->whereKey($data['created_by'])->lockForUpdate()->firstOrFail();
            $nextNumber = ((int) Competition::query()
                ->where('created_by', $data['created_by'])
                ->max('competition_number')) + 1;
            $data['competition_number'] = $nextNumber;
            return Competition::create($data);
        });
        $audit->record($request->user()->id, 'created', $competition, null, null, $competition->only(['title', 'status']));
        return redirect()->route('competitions.show', $competition)->with('status', 'تم إنشاء المسابقة بنجاح.');
    }

    public function show(Competition $competition, CompetitionLifecycleService $lifecycle)
    {
        Gate::authorize('view', $competition);
        // The status service can use this collection instead of querying
        // competition_branches again while rendering the details page.
        $competition->load('competitionBranches');
        return view('competition.show', [
            'competition' => $competition,
            'availableTransitions' => $lifecycle->availableTransitions($competition),
            'currentLifecycleState' => $lifecycle->state($competition),
            'currentLifecycleLabel' => $lifecycle->label($lifecycle->state($competition)),
        ]);
    }

    public function transition(TransitionCompetitionStatusRequest $request, Competition $competition, CompetitionLifecycleService $lifecycle, AuditLogService $audit)
    {
        Gate::authorize('update', $competition);
        $old = $competition->getAttributes();
        $competition = $lifecycle->transition(
            $competition,
            $request->validated('status'),
            $request->validated('expected_status'),
        );
        $audit->record($request->user()->id, 'status_changed', $competition, null, $old, $competition->getAttributes());

        return back()->with('status', 'تم تحديث حالة المسابقة بنجاح.');
    }

    public function edit(Competition $competition)
    {
        Gate::authorize('update', $competition);
        return view('competition.edit', compact('competition'));
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition, AuditLogService $audit, CompetitionScoringRulesService $scoringRules)
    {
        Gate::authorize('update', $competition);
        $data = $request->validated();
        $data['publication_scope'] = $data['publication_scope'] ?? $competition->publication_scope ?? 'unlisted';
        $data['target_governorate'] = $data['publication_scope'] === 'governorate' ? ($data['target_governorate'] ?? null) : null;
        $data['rules'] = $scoringRules->mergeCriteria($competition->rules, $request->boolean('custom_scoring'), $data['scoring_criteria'] ?? []);
        if ($competition->evaluations()->exists()
            && $scoringRules->effectiveCriteria($competition) !== $scoringRules->effectiveCriteria($data['rules'])) {
            return back()->withInput()->withErrors([
                'scoring_criteria' => 'لا يمكن تغيير معايير التقييم أو درجاتها بعد إرسال تقييمات لهذه المسابقة.',
            ]);
        }
        unset($data['custom_scoring'], $data['scoring_criteria']);
        $competition->update($data);
        $audit->record($request->user()->id, 'updated', $competition, null, $competition->getOriginal(), $competition->getAttributes());
        return redirect()->route('competitions.show', $competition)->with('status', 'تم تحديث المسابقة بنجاح.');
    }

    public function destroy(Competition $competition, AuditLogService $audit)
    {
        Gate::authorize('delete', $competition);
        $old = $competition->getAttributes();
        $competition->delete();
        $audit->record(auth()->id(), 'deleted', $competition, $competition->getKey(), $old, null);
        return redirect()->route('competitions.index')->with('status', 'تم حذف المسابقة بنجاح.');
    }
}
