<?php

namespace App\Services;

use App\Models\CompetitionLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizerDefaultLevelsService
{
    /** Provision the initial reusable levels for an organizer, safely and repeatedly. */
    public function provisionFor(User $organizer): int
    {
        if ($organizer->role !== 'User') return 0;

        return DB::transaction(function () use ($organizer): int {
            $created = 0;
            foreach (config('competition_levels.organizer_defaults', []) as $definition) {
                $level = CompetitionLevel::firstOrCreate(
                    ['created_by' => $organizer->id, 'type' => 'organizer', 'default_key' => $definition['key']],
                    [
                        'name' => $definition['name'],
                        'memorization_amount' => $definition['memorization_amount'],
                        'description' => $definition['description'] ?? null,
                        'status' => 'active',
                        'default_min_age' => $definition['default_min_age'] ?? null,
                        'default_max_age' => $definition['default_max_age'] ?? null,
                        'default_total_score' => $definition['default_total_score'] ?? null,
                        'default_passing_score' => $definition['default_passing_score'] ?? null,
                    ]
                );

                $updates = [];
                foreach (['default_min_age', 'default_max_age', 'default_total_score', 'default_passing_score'] as $field) {
                    if ($level->{$field} === null && array_key_exists($field, $definition)) {
                        $updates[$field] = $definition[$field];
                    }
                }

                // Repair values produced by the original starter dataset while
                // leaving organizer edits intact.
                $legacyDescription = 'مستوى لحفظ '.match ($definition['key']) {
                    'full_quran' => 'القرآن الكريم كاملاً',
                    'half_quran' => 'نصف القرآن الكريم',
                    'ten_parts' => 'عشرة أجزاء',
                    'five_parts' => 'خمسة أجزاء',
                    'juz_amma' => 'جزء عم',
                    default => '',
                };
                if ($legacyDescription !== '' && $level->description === $legacyDescription) {
                    $updates['description'] = $definition['description'];
                }
                if ($definition['key'] === 'juz_amma' && $level->memorization_amount === 'جزء واحد') {
                    $updates['memorization_amount'] = $definition['memorization_amount'];
                }
                if ($definition['key'] === 'quarter_quran' && $level->memorization_amount === '7 أجزاء ونصف تقريباً (ربع القرآن)') {
                    $updates['memorization_amount'] = $definition['memorization_amount'];
                }
                if ($updates !== []) {
                    $level->update($updates);
                }
                $created += $level->wasRecentlyCreated ? 1 : 0;
            }
            return $created;
        });
    }

    public function provisionForExistingOrganizers(): int
    {
        $created = 0;
        User::query()->where('role', 'User')->where(function ($query) {
            $query->whereNotNull('organization_name')->orWhereHas('competitions');
        })->chunkById(100, function ($users) use (&$created) {
            foreach ($users as $user) $created += $this->provisionFor($user);
        });
        return $created;
    }
}
