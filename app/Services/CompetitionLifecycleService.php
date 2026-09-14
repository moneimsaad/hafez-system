<?php

namespace App\Services;

use App\Models\Competition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionLifecycleService
{
    public const DRAFT = 'Draft';
    public const PUBLISHED = 'Published';
    public const REGISTRATION_OPEN = 'Registration Open';
    public const REGISTRATION_CLOSED = 'Registration Closed';
    public const EVALUATION = 'Evaluation';
    public const RESULTS_PUBLISHED = 'Results Published';
    public const COMPLETED = 'Completed';

    /** @return array<int, string> */
    public static function states(): array
    {
        return [
            self::DRAFT,
            self::PUBLISHED,
            self::REGISTRATION_OPEN,
            self::REGISTRATION_CLOSED,
            self::EVALUATION,
            self::RESULTS_PUBLISHED,
            self::COMPLETED,
        ];
    }

    public function state(Competition|string $competition): string
    {
        $status = $competition instanceof Competition ? $competition->status : $competition;
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'draft' => self::DRAFT,
            'published' => self::PUBLISHED,
            'registration open', 'open for registration', 'active' => self::REGISTRATION_OPEN,
            'registration closed', 'closed', 'suspended', 'archived' => self::REGISTRATION_CLOSED,
            'evaluation' => self::EVALUATION,
            'results published' => self::RESULTS_PUBLISHED,
            'completed', 'finished' => self::COMPLETED,
            default => self::DRAFT,
        };
    }

    public function isLegacyStatus(Competition $competition): bool
    {
        return in_array(strtolower(trim((string) $competition->status)), [
            'active',
            'open for registration',
            'closed',
            'suspended',
            'archived',
            'finished',
        ], true);
    }

    public function isPubliclyVisible(Competition $competition): bool
    {
        return $this->state($competition) !== self::DRAFT;
    }

    public function allowsRegistration(Competition $competition): bool
    {
        if ($this->state($competition) === self::REGISTRATION_OPEN) {
            return true;
        }

        return $this->isLegacyStatus($competition)
            && in_array(strtolower(trim((string) $competition->status)), ['active', 'open for registration'], true);
    }

    public function isReady(Competition $competition): bool
    {
        return $competition->competitionBranches()->exists()
            && $competition->registration_start_date !== null
            && $competition->registration_end_date !== null
            && $competition->exam_start_date !== null
            && $competition->exam_end_date !== null;
    }

    public function allowsCommitteeManagement(Competition $competition): bool
    {
        return ($this->isLegacyStatus($competition) && ! in_array(strtolower(trim((string) $competition->status)), ['archived', 'suspended', 'finished'], true))
            || in_array($this->state($competition), [
                self::PUBLISHED,
                self::REGISTRATION_OPEN,
                self::REGISTRATION_CLOSED,
                self::EVALUATION,
            ], true);
    }

    public function allowsCommitteeAssignment(Competition $competition): bool
    {
        return $this->allowsCommitteeManagement($competition);
    }

    public function allowsEvaluation(Competition $competition): bool
    {
        return ($this->isLegacyStatus($competition) && $this->isLegacyOperational($competition))
            || $this->state($competition) === self::EVALUATION;
    }

    public function allowsResultGeneration(Competition $competition): bool
    {
        return ($this->isLegacyStatus($competition) && $this->isLegacyOperational($competition))
            || $this->state($competition) === self::EVALUATION;
    }

    public function allowsCertificateGeneration(Competition $competition): bool
    {
        return ($this->isLegacyStatus($competition) && $this->isLegacyOperational($competition))
            || in_array($this->state($competition), [self::RESULTS_PUBLISHED, self::COMPLETED], true);
    }

    public function assertAllowsRegistration(Competition $competition): void
    {
        if (! $this->allowsRegistration($competition)) {
            throw ValidationException::withMessages([
                'competition_id' => 'التسجيل غير متاح حاليًا لهذه المسابقة.',
            ]);
        }
    }

    public function assertAllowsCommitteeManagement(Competition $competition): void
    {
        if (! $this->allowsCommitteeManagement($competition)) {
            throw ValidationException::withMessages([
                'competition_id' => 'لا يمكن إدارة اللجان في حالة المسابقة الحالية.',
            ]);
        }
    }

    public function assertAllowsCommitteeAssignment(Competition $competition): void
    {
        if (! $this->allowsCommitteeAssignment($competition)) {
            throw ValidationException::withMessages([
                'competition_id' => 'لا يمكن إسناد المشاركين في حالة المسابقة الحالية.',
            ]);
        }
    }

    public function assertAllowsEvaluation(Competition $competition): void
    {
        if (! $this->allowsEvaluation($competition)) {
            $currentState = $this->label($this->state($competition));
            $requiredState = $this->label(self::EVALUATION);

            throw ValidationException::withMessages([
                'competition_id' => "لا يمكن إدخال التقييمات لأن حالة المسابقة الحالية هي «{$currentState}». يتطلب إدخال التقييمات أن تكون الحالة «{$requiredState}».",
            ]);
        }
    }

    public function assertAllowsResultGeneration(Competition $competition): void
    {
        if (! $this->allowsResultGeneration($competition)) {
            throw ValidationException::withMessages([
                'competition_id' => 'لا يمكن توليد النتائج في حالة المسابقة الحالية.',
            ]);
        }
    }

    public function assertAllowsCertificateGeneration(Competition $competition): void
    {
        if (! $this->allowsCertificateGeneration($competition)) {
            throw ValidationException::withMessages([
                'competition_id' => 'لا يمكن إصدار الشهادات في حالة المسابقة الحالية.',
            ]);
        }
    }

    /** @return array<int, array{state: string, label: string}> */
    public function availableTransitions(Competition $competition): array
    {
        $state = $this->state($competition);
        $next = match ($state) {
            self::DRAFT => self::PUBLISHED,
            self::PUBLISHED => self::REGISTRATION_OPEN,
            self::REGISTRATION_OPEN => self::REGISTRATION_CLOSED,
            self::REGISTRATION_CLOSED => self::EVALUATION,
            self::EVALUATION => self::RESULTS_PUBLISHED,
            self::RESULTS_PUBLISHED => self::COMPLETED,
            default => null,
        };

        $transitions = [];
        if ($next !== null && ! ($state === self::DRAFT && ! $this->isReady($competition))) {
            $transitions[] = ['state' => $next, 'label' => $this->label($next)];
        }

        if ($state === self::EVALUATION && $this->canRollbackEvaluation($competition)) {
            $transitions[] = ['state' => self::REGISTRATION_CLOSED, 'label' => $this->label(self::REGISTRATION_CLOSED)];
        }

        return $transitions;
    }

    public function canRollbackEvaluation(Competition $competition): bool
    {
        return ! $competition->results()->exists()
            && ! $competition->certificates()->exists()
            && ! $competition->evaluations()->where('status', 'submitted')->exists();
    }

    public function transition(Competition $competition, string $target, string $expectedStatus): Competition
    {
        return DB::transaction(function () use ($competition, $target, $expectedStatus): Competition {
            $competition = Competition::query()->lockForUpdate()->findOrFail($competition->getKey());

            if ($competition->status !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'expected_status' => 'تم تغيير حالة المسابقة بواسطة عملية أخرى. يرجى تحديث الصفحة قبل المحاولة مرة أخرى.',
                ]);
            }

            if (! in_array($target, self::states(), true)) {
                throw ValidationException::withMessages(['status' => 'حالة المسابقة غير صحيحة.']);
            }

            $current = $this->state($competition);
            if ($current === self::EVALUATION && $target === self::REGISTRATION_CLOSED && ! $this->canRollbackEvaluation($competition)) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن العودة إلى حالة التسجيل مغلق بعد وجود تقييمات مرسلة أو نتائج مولدة أو شهادات صادرة.',
                ]);
            }

            $allowed = collect($this->availableTransitions($competition))->pluck('state')->all();
            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'لا يمكن الانتقال إلى هذه الحالة من الحالة الحالية.']);
            }

            if ($target === self::PUBLISHED && ! $this->isReady($competition)) {
                throw ValidationException::withMessages(['status' => 'يجب إكمال إعداد المسابقة والمستويات قبل نشرها.']);
            }

            if ($target === self::RESULTS_PUBLISHED && ! $competition->results()->exists()) {
                throw ValidationException::withMessages(['status' => 'يجب توليد نتيجة واحدة على الأقل قبل نشر النتائج.']);
            }

            $competition->update(['status' => $target]);

            return $competition->refresh();
        });
    }

    public function label(string $state): string
    {
        return match ($state) {
            self::DRAFT => 'مسودة',
            self::PUBLISHED => 'منشورة',
            self::REGISTRATION_OPEN => 'التسجيل مفتوح',
            self::REGISTRATION_CLOSED => 'التسجيل مغلق',
            self::EVALUATION => 'التقييم جارٍ',
            self::RESULTS_PUBLISHED => 'النتائج منشورة',
            self::COMPLETED => 'مكتملة',
            default => $state,
        };
    }

    private function isLegacyOperational(Competition $competition): bool
    {
        return in_array(strtolower(trim((string) $competition->status)), ['active', 'open for registration'], true);
    }

}
