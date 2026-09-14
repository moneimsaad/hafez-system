<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Result;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;

class CertificateGenerationService
{
    public function __construct(private readonly PlatformSettingsService $settings)
    {
    }

    public function generate(Result $result): Certificate
    {
        app(CompetitionLifecycleService::class)->assertAllowsCertificateGeneration($result->competition()->firstOrFail());
        $result->loadMissing(['student', 'competition.creator', 'competitionBranch']);
        $number = $this->uniqueNumber();
        $issuedAt = now();
        $verificationUrl = url('/certificates/verify/'.$number);
        $qrDataUri = (new Builder(data: $verificationUrl, size: 300, margin: 10))->build()->getDataUri();
        $organizationName = $result->competition?->creator?->organization_name
            ?: $result->competition?->creator?->name
            ?: 'الجهة المنظمة';
        $pdf = Pdf::loadView('certificate.pdf', [
            'certificateNumber' => $number,
            'student' => $result->student,
            'competition' => $result->competition,
            'branch' => $result->competitionBranch,
            'type' => 'شهادة تقدير',
            'percentage' => $result->percentage,
            'qrDataUri' => $qrDataUri,
            'showQr' => $this->settings->boolean('certificate.show_qr', true),
            'certificateTitle' => $this->settings->get('certificate.title', 'شهادة تقدير'),
            'certificateIssuer' => $this->settings->get('certificate.issuer', ''),
            'certificateFooter' => $this->settings->get('certificate.footer', ''),
            'organizationName' => $organizationName,
            'issuedAt' => $issuedAt,
        ])->setPaper('a4', 'landscape');
        $filePath = 'certificates/'.$number.'.pdf';
        Storage::disk('public')->put($filePath, $pdf->output());

        return Certificate::create([
            'student_id' => $result->student_id,
            'competition_id' => $result->competition_id,
            'branch_id' => $result->branch_id,
            'result_id' => $result->id,
            'certificate_type' => 'شهادة تقدير',
            'certificate_number' => $number,
            'qr_code' => $verificationUrl,
            'file_path' => $filePath,
            'issued_at' => $issuedAt,
        ]);
    }

    private function uniqueNumber(): string
    {
        do {
            $prefix = trim((string) $this->settings->get('certificate.number_prefix', 'CERT')) ?: 'CERT';
            $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix) ?: 'CERT';
            $number = $prefix.'-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
        } while (Certificate::query()->where('certificate_number', $number)->exists());

        return $number;
    }
}
