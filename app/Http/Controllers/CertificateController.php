<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Result;
use App\Services\CertificateGenerationService;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;

class CertificateController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Certificate::class);
        $authorizedQuery = Certificate::query();
        if (auth()->user()->role === 'User') {
            $authorizedQuery->whereHas('competition', fn ($competition) => $competition->where('created_by', auth()->id()));
        }

        $query = (clone $authorizedQuery)->with(['student', 'competition', 'competitionBranch', 'result'])->latest('issued_at');
        if ($search = request('search')) {
            $query->where(function ($scope) use ($search) {
                $scope->where('certificate_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('competition', fn ($competition) => $competition->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('competitionBranch', fn ($branch) => $branch->where('name', 'like', "%{$search}%"));
            });
        }
        $query->when(request('competition_id'), fn ($q, $id) => $q->where('competition_id', $id));
        $query->when(request('branch_id'), fn ($q, $id) => $q->where('branch_id', $id));
        $query->when(request('certificate_type'), fn ($q, $type) => $q->where('certificate_type', $type));
        if ($from = $this->filterDate(request('issue_from'))) {
            $query->where('issued_at', '>=', $from->startOfDay());
        }
        if ($to = $this->filterDate(request('issue_to'))) {
            $query->where('issued_at', '<=', $to->endOfDay());
        }

        $competitionIds = (clone $authorizedQuery)->select('competition_id')->distinct();
        $branchIds = (clone $authorizedQuery)->select('branch_id')->distinct();
        $resultIds = (clone $authorizedQuery)->select('result_id')->distinct();
        $certificates = $query->paginate(15)->withQueryString();
        $finalResultsQuery = Result::query()
            ->when(auth()->user()->role === 'User', fn ($q) => $q->whereHas('competition', fn ($c) => $c->where('created_by', auth()->id())))
            ->whereDoesntHave('certificates');
        $finalResults = (clone $finalResultsQuery)->with(['student', 'competition', 'competitionBranch'])
            ->latest('id')->limit(100)->get();
        return view('certificate.index', [
            'certificates' => $certificates,
            'finalResults' => $finalResults,
            'competitions' => Competition::query()->whereIn('id', $competitionIds)->orderBy('title')->get(),
            'branches' => CompetitionBranch::query()->with('competition')->whereIn('id', $branchIds)->orderBy('name')->get(),
            'certificateTypes' => (clone $authorizedQuery)->select('certificate_type')->whereNotNull('certificate_type')->distinct()->orderBy('certificate_type')->pluck('certificate_type'),
            'resultStatuses' => Result::query()->whereIn('id', $resultIds)->select('result_status')->whereNotNull('result_status')->distinct()->orderBy('result_status')->pluck('result_status'),
            'summary' => [
                'total' => (clone $authorizedQuery)->count(),
                'generated' => (clone $authorizedQuery)->whereNotNull('file_path')->count(),
                'available_results' => $finalResultsQuery->count(),
                'issued_today' => (clone $authorizedQuery)->whereDate('issued_at', now()->toDateString())->count(),
            ],
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

    public function show(Certificate $certificate)
    {
        Gate::authorize('view', $certificate);
        $certificate->load($this->certificatePresentationRelations());
        [$qrDataUri, $organizationName] = $this->certificatePresentationData($certificate);

        return view('certificate.show', compact('certificate', 'qrDataUri', 'organizationName'));
    }

    public function verify(Request $request, ?string $certificateNumber = null)
    {
        $certificateNumber = $certificateNumber ?? $request->query('certificate_number');
        $certificateNumber = is_string($certificateNumber) ? trim($certificateNumber) : null;
        $state = $certificateNumber === null || $certificateNumber === '' ? 'initial' : 'not_found';
        $certificate = null;

        if ($state !== 'initial') {
            $certificate = $this->findPublicCertificate($certificateNumber);
            $state = $certificate ? 'found' : 'not_found';
        }

        return view('certificate.verify', compact('certificate', 'certificateNumber', 'state'));
    }

    /** Render a public, read-only certificate presentation by its public number. */
    public function publicPreview(string $certificateNumber)
    {
        $certificate = $this->findPublicCertificate(trim($certificateNumber));

        abort_unless($certificate, 404);

        [$qrDataUri, $organizationName] = $this->certificatePresentationData($certificate);

        return view('certificate.public-preview', compact('certificate', 'qrDataUri', 'organizationName'));
    }

    /** @return array<int, string> */
    private function certificatePresentationRelations(): array
    {
        return ['student', 'competition.creator', 'competitionBranch', 'result'];
    }

    private function findPublicCertificate(string $certificateNumber): ?Certificate
    {
        if ($certificateNumber === '') {
            return null;
        }

        return Certificate::query()
            ->with($this->certificatePresentationRelations())
            ->where('certificate_number', $certificateNumber)
            ->first();
    }

    /** @return array{0: string, 1: string} */
    private function certificatePresentationData(Certificate $certificate): array
    {
        // QR codes always resolve to public verification, never to a private ID.
        $qrTarget = is_string($certificate->qr_code) && str_starts_with($certificate->qr_code, 'http')
            ? $certificate->qr_code
            : route('certificates.verify', $certificate->certificate_number);

        return [
            (new Builder(data: $qrTarget, size: 180, margin: 6))->build()->getDataUri(),
            $certificate->competition?->creator?->organization_name
                ?: $certificate->competition?->creator?->name
                ?: 'الجهة المنظمة',
        ];
    }
}
