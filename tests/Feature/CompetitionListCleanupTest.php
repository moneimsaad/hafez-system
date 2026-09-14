<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\User;

it('shows the canonical public registration action only when a competition is eligible and removes copy-link UI', function () {
    $owner = User::factory()->create(['username' => 'cleanup-owner']);
    $competition = Competition::create(['created_by' => $owner->id, 'competition_number' => 7, 'title' => 'Cleanup Competition', 'status' => 'Open for Registration', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Test']);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى المؤهل', 'memorization_amount' => 'خمسة أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    $this->actingAs($owner)->get(route('competitions.index'))
        ->assertOk()
        ->assertSee('فتح التسجيل العام')
        ->assertDontSee('نسخ الرابط')
        ->assertDontSee('data-copy-url');
    expect(route('competitions.public-register-canonical', [$owner->username, $competition->competition_number]))
        ->toEndWith('/competitions/cleanup-owner/7/register');
});

it('shows the intended closed state without an active public registration action when ineligible', function () {
    $owner = User::factory()->create(['username' => 'closed-owner']);
    $competition = Competition::create(['created_by' => $owner->id, 'competition_number' => 8, 'title' => 'Closed Competition', 'status' => 'Closed', 'registration_start_date' => now()->subDays(3), 'registration_end_date' => now()->subDay(), 'exam_start_date' => now(), 'exam_end_date' => now()->addDay(), 'location' => 'Test']);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى المغلق', 'memorization_amount' => 'خمسة أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    $this->actingAs($owner)->get(route('competitions.index'))
        ->assertOk()->assertSee('التسجيل مغلق')->assertDontSee('فتح التسجيل العام')
        ->assertDontSee('نسخ الرابط')->assertDontSee('data-copy-url');
});
