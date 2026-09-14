<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RegistrationReviewService
{
    public function __construct(private AuditLogService $audit) {}

    public function approve(Registration $registration, User $actor): void
    {
        $this->ensurePending($registration);
        $old = $registration->getAttributes();
        $registration->update(['status' => 'approved', 'approved_at' => now(), 'rejection_reason' => null]);
        $this->audit->record($actor->id, 'approved', $registration, null, $old, $registration->getAttributes());
    }

    public function reject(Registration $registration, string $reason, User $actor): void
    {
        $this->ensurePending($registration);
        $old = $registration->getAttributes();
        $registration->update(['status' => 'rejected', 'rejection_reason' => $reason, 'approved_at' => null]);
        $this->audit->record($actor->id, 'rejected', $registration, null, $old, $registration->getAttributes());
    }

    private function ensurePending(Registration $registration): void
    {
        if ($registration->status !== 'pending') {
            throw ValidationException::withMessages(['registration_ids' => 'لا يمكن مراجعة طلبات تم اتخاذ قرار بشأنها بالفعل.']);
        }
    }
}
