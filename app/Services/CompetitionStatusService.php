<?php

namespace App\Services;

use App\Models\Competition;
use Carbon\Carbon;

class CompetitionStatusService
{
    public function __construct(private CompetitionLifecycleService $lifecycle) {}

    public function isReady(Competition $competition): bool
    {
        // Listing queries preload this aggregate with withCount(). Prefer it
        // over a per-model exists() query so status accessors remain cheap in
        // Blade loops. Eager-loaded collections are the next fallback for
        // detail/public contexts; only otherwise issue a single existence
        // query for an isolated model.
        $hasLevels = array_key_exists('competition_branches_count', $competition->getAttributes())
            ? (int) $competition->getAttribute('competition_branches_count') > 0
            : ($competition->relationLoaded('competitionBranches')
                ? $competition->competitionBranches->isNotEmpty()
                : $competition->competitionBranches()->exists());

        return $hasLevels
            && $competition->registration_start_date !== null
            && $competition->registration_end_date !== null
            && $competition->exam_start_date !== null
            && $competition->exam_end_date !== null;
    }

    public function displayStatus(Competition $competition): string
    {
        $internalStatus = strtolower((string) $competition->status);
        if ($this->lifecycle->isLegacyStatus($competition)) {
            if (in_array($internalStatus, ['closed', 'suspended', 'archived'], true)) {
                return match ($internalStatus) {
                    'closed' => 'مغلق',
                    'suspended' => 'موقوفة',
                    default => 'مؤرشفة',
                };
            }

            if (! $this->isReady($competition)) {
                return 'غير جاهزة';
            }

            // Legacy statuses retain their historical timeline display.
            return $this->timelineStatus($competition);
        }

        return $this->lifecycle->label($this->lifecycle->state($competition));
    }

    private function timelineStatus(Competition $competition): string
    {
        $now = Carbon::now();
        if ($now->lt($competition->registration_start_date)) {
            return 'قريباً';
        }
        if ($now->between($competition->registration_start_date, $competition->registration_end_date)) {
            return 'مفتوح للتسجيل';
        }
        if ($now->lt($competition->exam_start_date)) {
            return 'انتهى التسجيل';
        }
        if ($now->between($competition->exam_start_date, $competition->exam_end_date)) {
            return 'جاري الاختبارات';
        }

        return 'منتهية';
    }

    public function isRegistrationOpen(Competition $competition): bool
    {
        if (! $this->isReady($competition) || ! $this->lifecycle->allowsRegistration($competition)) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($competition->registration_start_date, $competition->registration_end_date);
    }
}
