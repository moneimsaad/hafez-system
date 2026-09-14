<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use App\Models\User;

function scopedCompetition(User $owner, string $scope, ?string $governorate = null, ?string $title = null): Competition
{
    $competition = Competition::create([
        'title' => $title ?? ('Scoped Competition '.$scope.' '.$owner->id),
        'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3),
        'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id,
        'competition_number' => Competition::where('created_by', $owner->id)->count() + 1, 'publication_scope' => $scope, 'target_governorate' => $governorate,
    ]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'Level', 'memorization_amount' => '5 juz', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    return $competition;
}

function registrationPayload(Competition $competition, ?string $governorate = null): array
{
    return ['competition_id' => $competition->id, 'branch_id' => $competition->competitionBranches()->first()->id, 'full_name' => 'Student Test Name', 'birth_date' => '2012-01-01', 'gender' => 'Male', 'phone' => '01000000000', 'parent_phone' => '01000000001', 'governorate' => $governorate];
}

test('homepage hides unlisted but publishes eligible public scopes', function () {
    $owner = User::factory()->create(['username' => 'scope-owner']);
    $unlisted = scopedCompetition($owner, 'unlisted', null, 'Unlisted Competition');
    $nationwide = scopedCompetition(User::factory()->create(['username' => 'wide-owner']), 'nationwide', null, 'Nationwide Competition');
    $this->get('/')->assertOk()->assertDontSee($unlisted->title)->assertSee($nationwide->title);
    $this->get(route('competitions.public-register-canonical', [$owner->username, $unlisted->competition_number]))->assertOk();
});

test('governorate scope appears and enforces the submitted governorate', function () {
    $owner = User::factory()->create(['username' => 'alex-owner']);
    $competition = scopedCompetition($owner, 'governorate', 'الإسكندرية');
    $this->get('/')->assertSee($competition->title)->assertSee('الإسكندرية');
    $this->post(route('registrations.store'), registrationPayload($competition, 'القاهرة'))->assertSessionHasErrors('governorate');
    $this->post(route('registrations.store'), registrationPayload($competition, 'الإسكندرية'))->assertRedirect(route('registrations.success'));
});

test('governorate scope rejects missing and arbitrary values while other scopes remain optional', function () {
    $owner = User::factory()->create(['username' => 'scope-check']);
    $governorate = scopedCompetition($owner, 'governorate', 'الجيزة');
    $this->post(route('registrations.store'), registrationPayload($governorate))->assertSessionHasErrors('governorate');
    $this->post(route('registrations.store'), registrationPayload($governorate, 'Atlantis'))->assertSessionHasErrors('governorate');
    $nationwide = scopedCompetition($owner, 'nationwide');
    $this->post(route('registrations.store'), registrationPayload($nationwide))->assertRedirect(route('registrations.success'));
});

test('competition publication scope validates and clears stale governorate', function () {
    $owner = User::factory()->create();
    $payload = ['title' => 'Publication', 'registration_start_date' => now(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'publication_scope' => 'governorate', 'target_governorate' => 'القاهرة'];
    $this->actingAs($owner)->post(route('competitions.store'), $payload)->assertRedirect();
    $competition = Competition::where('title', 'Publication')->firstOrFail();
    expect($competition->publication_scope)->toBe('governorate')->and($competition->target_governorate)->toBe('القاهرة');
    $this->actingAs($owner)->put(route('competitions.update', $competition), [...$payload, 'publication_scope' => 'nationwide', 'target_governorate' => 'القاهرة'])->assertRedirect();
    expect($competition->fresh()->target_governorate)->toBeNull();
});

test('organizer cannot update another owners publication settings', function () {
    $owner = User::factory()->create(); $other = User::factory()->create();
    $competition = scopedCompetition($owner, 'unlisted');
    $this->actingAs($other)->put(route('competitions.update', $competition), ['title' => $competition->title, 'registration_start_date' => $competition->registration_start_date, 'registration_end_date' => $competition->registration_end_date, 'exam_start_date' => $competition->exam_start_date, 'exam_end_date' => $competition->exam_end_date, 'location' => $competition->location, 'publication_scope' => 'nationwide'])->assertForbidden();
});

test('governorate to unlisted transition clears target governorate in database', function () {
    $owner = User::factory()->create();
    $competition = scopedCompetition($owner, 'governorate', 'الإسكندرية');

    $this->actingAs($owner)->put(route('competitions.update', $competition), [
        'title' => $competition->title,
        'registration_start_date' => $competition->registration_start_date,
        'registration_end_date' => $competition->registration_end_date,
        'exam_start_date' => $competition->exam_start_date,
        'exam_end_date' => $competition->exam_end_date,
        'location' => $competition->location,
        'publication_scope' => 'unlisted',
    ])->assertRedirect();

    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'publication_scope' => 'unlisted',
        'target_governorate' => null,
    ]);
});

test('homepage eligibility wins over nationwide publication scope', function () {
    $owner = User::factory()->create(['username' => 'eligibility-owner']);
    $future = Competition::create(['title' => 'Future Nationwide', 'registration_start_date' => now()->addDay(), 'registration_end_date' => now()->addDays(2), 'exam_start_date' => now()->addDays(3), 'exam_end_date' => now()->addDays(4), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 1, 'publication_scope' => 'nationwide']);
    CompetitionBranch::create(['competition_id' => $future->id, 'name' => 'Future Level', 'memorization_amount' => '5 juz', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $ended = Competition::create(['title' => 'Ended Nationwide', 'registration_start_date' => now()->subDays(3), 'registration_end_date' => now()->subDay(), 'exam_start_date' => now()->addDay(), 'exam_end_date' => now()->addDays(2), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 2, 'publication_scope' => 'nationwide']);
    CompetitionBranch::create(['competition_id' => $ended->id, 'name' => 'Ended Level', 'memorization_amount' => '5 juz', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $incomplete = Competition::create(['title' => 'Incomplete Nationwide', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 3, 'publication_scope' => 'nationwide']);

    $this->get('/')->assertOk()->assertDontSee('Future Nationwide')->assertDontSee('Ended Nationwide')->assertDontSee('Incomplete Nationwide');
});

test('omitted publication scope defaults to private and legacy numeric link remains available', function () {
    $owner = User::factory()->create(['username' => null]);
    $competition = Competition::create(['title' => 'Legacy Private', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 1]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'Legacy Level', 'memorization_amount' => '5 juz', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'publication_scope' => 'unlisted', 'target_governorate' => null]);
    $this->get(route('competitions.public-register', $competition))->assertOk();
});
