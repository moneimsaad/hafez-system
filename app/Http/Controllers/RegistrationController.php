<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\RegistrationStatusRequest;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\CompetitionStatusService;
use App\Services\AuditLogService;
use App\Services\RegistrationReviewService;
use App\Services\CompetitionLifecycleService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\UniqueConstraintViolationException;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    public function status()
    {
        return view('registration.status', ['registration' => null]);
    }

    public function success()
    {
        $registrationNumber = session('registration_number');

        if (! $registrationNumber) {
            return redirect()->route('registrations.status');
        }

        $registration = Registration::query()
            ->with(['student', 'competition.creator', 'competitionBranch'])
            ->where('registration_number', $registrationNumber)
            ->first();

        if (! $registration) {
            return redirect()->route('registrations.status');
        }

        return view('registration.success', compact('registration'));
    }

    public function searchStatus(RegistrationStatusRequest $request)
    {
        $registration = Registration::query()
            ->with(['student', 'competition', 'competitionBranch'])
            ->where('registration_number', $request->validated('registration_number'))
            ->first();

        return response()
            ->view('registration.status', compact('registration'))
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function index()
    {
        Gate::authorize('viewAny', Registration::class);
        $authorizedQuery = Registration::query();
        if (auth()->user()->role === 'User') {
            $authorizedQuery->whereHas('competition', fn ($q) => $q->where('created_by', auth()->id()));
        }

        $query = (clone $authorizedQuery)->with(['student', 'competition', 'competitionBranch'])->latest('registered_at');
        if ($search = request('search')) {
            $query->where(function ($scope) use ($search) {
                $scope->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('parent_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }
        $query->when(request('competition_id'), fn ($q, $id) => $q->where('competition_id', $id));
        $query->when(request('branch_id'), fn ($q, $id) => $q->where('branch_id', $id));
        $query->when(request('status'), fn ($q, $status) => $q->where('status', $status));
        if ($from = $this->filterDate(request('date_from'))) {
            $query->where('registered_at', '>=', $from->startOfDay());
        }
        if ($to = $this->filterDate(request('date_to'))) {
            $query->where('registered_at', '<=', $to->endOfDay());
        }

        $competitionIds = (clone $authorizedQuery)->select('competition_id')->distinct();
        $branchIds = (clone $authorizedQuery)->select('branch_id')->distinct();
        $summary = [
            'total' => (clone $authorizedQuery)->count(),
            'pending' => (clone $authorizedQuery)->where('status', 'pending')->count(),
            'approved' => (clone $authorizedQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $authorizedQuery)->where('status', 'rejected')->count(),
        ];
        return view('registration.index', [
            'registrations' => $query->paginate(15)->withQueryString(),
            'competitions' => Competition::query()->whereIn('id', $competitionIds)->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->whereIn('id', $branchIds)->orderBy('name')->get(),
            'summary' => $summary,
        ]);
    }

    private function filterDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function show(Registration $registration)
    {
        Gate::authorize('view', $registration);
        $registration->load(['student', 'competition', 'competitionBranch']);
        return view('registration.show', compact('registration'));
    }

    public function approve(Registration $registration, RegistrationReviewService $review)
    {
        Gate::authorize('approve', $registration);
        $review->approve($registration, request()->user());
        return back()->with('status', 'تم اعتماد التسجيل بنجاح.');
    }

    public function reject(Registration $registration, \Illuminate\Http\Request $request, RegistrationReviewService $review)
    {
        Gate::authorize('reject', $registration);
        $data = $request->validate(['rejection_reason' => ['required', 'string']]);
        $review->reject($registration, $data['rejection_reason'], $request->user());
        return back()->with('status', 'تم رفض التسجيل بنجاح.');
    }

    public function bulkReview(\Illuminate\Http\Request $request, RegistrationReviewService $review)
    {
        $data = $request->validate([
            'registration_ids' => ['required', 'array', 'min:1', 'max:50'],
            'registration_ids.*' => ['integer', 'distinct', 'exists:registrations,id'],
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ]);
        \DB::transaction(function () use ($data, $request, $review): void {
            $registrations = Registration::query()->with('competition')->whereIn('id', $data['registration_ids'])->lockForUpdate()->get();
            if ($registrations->count() !== count($data['registration_ids'])) {
                abort(422);
            }
            foreach ($registrations as $registration) {
                Gate::forUser($request->user())->authorize($data['action'], $registration);
                if ($registration->status !== 'pending') {
                    throw ValidationException::withMessages(['registration_ids' => 'لا يمكن مراجعة طلبات تم اتخاذ قرار بشأنها بالفعل.']);
                }
            }
            foreach ($registrations as $registration) {
                if ($data['action'] === 'approve') {
                    $review->approve($registration, $request->user());
                } else {
                    $review->reject($registration, $data['rejection_reason'], $request->user());
                }
            }
        });
        $message = $data['action'] === 'approve' ? 'تم قبول الطلبات المحددة بنجاح.' : 'تم رفض الطلبات المحددة بنجاح.';
        return redirect()->route('registrations.index', $request->only(['search', 'competition_id', 'branch_id', 'status', 'date_from', 'date_to', 'page']))->with('status', $message);
    }

    public function create(CompetitionLifecycleService $lifecycle)
    {
        $competitions = Competition::query()
            ->with(['competitionBranches', 'creator'])
            ->whereHas('competitionBranches')
            ->whereIn('publication_scope', ['governorate', 'nationwide'])
            ->whereIn('status', [
                CompetitionLifecycleService::REGISTRATION_OPEN,
                'Open for Registration',
                'active',
            ])
            ->whereNotNull('exam_start_date')
            ->whereNotNull('exam_end_date')
            ->where('registration_start_date', '<=', now())
            ->where('registration_end_date', '>=', now())
            ->orderBy('registration_start_date')
            ->get();

        return view('registration.create', compact('competitions'));
    }

    public function createForCompetition(Competition $competition, CompetitionLifecycleService $lifecycle)
    {
        $competition->load('creator');
        $competition->load('competitionBranches');

        $statusService = app(CompetitionStatusService::class);

        // Keep incomplete competitions out of the public flow. A complete
        // competition remains viewable after registration closes so visitors
        // can still read its details and conditions.
        if (! $statusService->isReady($competition) || ! $lifecycle->isPubliclyVisible($competition)) {
            abort(404);
        }

        $registrationOpen = $statusService->isRegistrationOpen($competition);
        $registrationStatus = $statusService->displayStatus($competition);

        if ($competition->creator?->username && $competition->competition_number) {
            return redirect()->route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number]);
        }
        // Legacy numeric URLs remain the safe fallback for owners without usernames
        // and for competitions created by Platform Admin accounts.
        return view('registration.create', [
            'competitions' => collect([$competition]),
            'selectedCompetition' => $competition,
            'registrationOpen' => $registrationOpen,
            'registrationStatus' => $registrationStatus,
        ]);
    }

    public function createForOwnerCompetition(string $username, int $competition_number, CompetitionLifecycleService $lifecycle)
    {
        $owner = User::query()->where('username', strtolower($username))->where('role', 'User')->where('status', 'active')->firstOrFail();
        $competition = Competition::query()->where('created_by', $owner->id)->where('competition_number', $competition_number)->firstOrFail();
        $competition->load('competitionBranches');
        $statusService = app(CompetitionStatusService::class);
        if (! $statusService->isReady($competition) || ! $lifecycle->isPubliclyVisible($competition)) {
            abort(404);
        }
        $registrationOpen = $statusService->isRegistrationOpen($competition);
        return view('registration.create', [
            'competitions' => collect([$competition]),
            'selectedCompetition' => $competition,
            'registrationOpen' => $registrationOpen,
            'registrationStatus' => $statusService->displayStatus($competition),
        ]);
    }

    public function store(StoreRegistrationRequest $request, AuditLogService $audit, CompetitionLifecycleService $lifecycle)
    {
        $data = $request->validated();
        $competition = Competition::query()->findOrFail($data['competition_id']);

        $now = now();
        if (! app(CompetitionStatusService::class)->isRegistrationOpen($competition)) {
            $lifecycle->assertAllowsRegistration($competition);
            throw ValidationException::withMessages(['competition_id' => 'التسجيل غير متاح حاليًا لهذه المسابقة.']);
        }

        $studentData = [
            'full_name' => $data['full_name'],
            'national_id' => $data['national_id'] ?? null,
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'parent_phone' => $data['parent_phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? 'غير محدد',
            'city' => $data['city'] ?? 'غير محدد',
            'center_name' => $data['center_name'] ?? 'غير محدد',
        ];

        try {
            $transactionResult = DB::transaction(function () use ($request, $data, $studentData) {
            $student = null;

            if (! empty($studentData['national_id'])) {
                $student = Student::query()->where('national_id', $studentData['national_id'])->first();
            } elseif (! empty($studentData['email'])) {
                $student = Student::query()
                    ->where('email', $studentData['email'])
                    ->where('phone', $studentData['phone'])
                    ->first();
            }

            if (! $student) {
                $student = Student::query()
                    ->where('full_name', $studentData['full_name'])
                    ->whereDate('birth_date', $studentData['birth_date'])
                    ->where('phone', $studentData['phone'])
                    ->where('parent_phone', $studentData['parent_phone'])
                    ->first();
            }

            if (! $student) {
                $student = Student::create($studentData);
            }

            $duplicate = Registration::query()
                ->where('competition_id', $data['competition_id'])
                ->where('student_id', $student->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'student' => 'هذا الطالب مسجل بالفعل في المسابقة المختارة.',
                ]);
            }

            $registration = Registration::create([
                'competition_id' => $data['competition_id'],
                'branch_id' => $data['branch_id'],
            'student_id' => $student->id,
            'governorate' => $data['governorate'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'registered_at' => now(),
            ]);

                return [$student, $registration];
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'student' => 'هذا الطالب مسجل بالفعل في المسابقة المختارة.',
            ]);
        }
        [$student, $registration] = $transactionResult;
        $audit->record(null, 'created', $registration, null, null, $registration->only(['registration_number', 'competition_id', 'branch_id', 'student_id', 'status']));
        return redirect()->route('registrations.success')->with('registration_number', $registration->registration_number);
    }
}
