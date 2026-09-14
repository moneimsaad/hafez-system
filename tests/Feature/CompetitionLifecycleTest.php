<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\CompetitionLifecycleService;
use Illuminate\Validation\ValidationException;

function lifecycleTestCompetition(User $owner, string $status = CompetitionLifecycleService::DRAFT, string $scope = 'nationwide'): Competition
{
    $competition = Competition::create([
        'title' => 'Lifecycle Test Competition',
        'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2),
        'exam_end_date' => now()->addDays(3),
        'location' => 'Test Center',
        'status' => $status,
        'created_by' => $owner->id,
        'competition_number' => 1,
        'publication_scope' => $scope,
    ]);

    CompetitionBranch::create([
        'competition_id' => $competition->id,
        'name' => 'Lifecycle Level',
        'memorization_amount' => 'خمسة أجزاء',
        'min_age' => 8,
        'max_age' => 18,
        'total_score' => 100,
        'passing_score' => 50,
    ]);

    return $competition;
}

test('draft competitions stay private and cannot receive public registrations', function () {
    $owner = User::factory()->create(['username' => 'lifecycle-owner']);
    $competition = lifecycleTestCompetition($owner);

    $this->get(route('competitions.public-register-canonical', [$owner->username, $competition->competition_number]))
        ->assertNotFound();
    $this->get('/')->assertDontSee($competition->title);
});

test('competition lifecycle transitions are explicit and ordered', function () {
    $owner = User::factory()->create();
    $competition = lifecycleTestCompetition($owner);

    $this->actingAs($owner)->post(route('competitions.status.update', $competition), [
        'status' => CompetitionLifecycleService::PUBLISHED,
    ])->assertRedirect();
    expect($competition->fresh()->status)->toBe(CompetitionLifecycleService::PUBLISHED);

    $this->actingAs($owner)->post(route('competitions.status.update', $competition), [
        'status' => CompetitionLifecycleService::RESULTS_PUBLISHED,
    ])->assertSessionHasErrors('status');

    app(CompetitionLifecycleService::class)->transition($competition->fresh(), CompetitionLifecycleService::REGISTRATION_OPEN);
    app(CompetitionLifecycleService::class)->transition($competition->fresh(), CompetitionLifecycleService::REGISTRATION_CLOSED);
    app(CompetitionLifecycleService::class)->transition($competition->fresh(), CompetitionLifecycleService::EVALUATION);

    expect($competition->fresh()->status)->toBe(CompetitionLifecycleService::EVALUATION);
});

test('published competitions do not accept registrations until registration is open', function () {
    $owner = User::factory()->create();
    $competition = lifecycleTestCompetition($owner, CompetitionLifecycleService::PUBLISHED);
    $branch = $competition->competitionBranches()->firstOrFail();

    $payload = [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'full_name' => 'Lifecycle Student Name',
        'birth_date' => '2012-01-01',
        'gender' => 'Male',
        'phone' => '01012345678',
        'parent_phone' => '01112345678',
    ];

    $this->post(route('registrations.store'), $payload)->assertSessionHasErrors('competition_id');
    expect(Registration::query()->count())->toBe(0);

    app(CompetitionLifecycleService::class)->transition($competition->fresh(), CompetitionLifecycleService::REGISTRATION_OPEN);
    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    expect(Registration::query()->count())->toBe(1);
});

test('branch and reusable level edits are blocked after historical registrations', function () {
    $owner = User::factory()->create();
    $competition = lifecycleTestCompetition($owner);
    $branch = $competition->competitionBranches()->firstOrFail();
    $student = Student::create([
        'full_name' => 'Historical Student',
        'birth_date' => '2012-01-01',
        'gender' => 'Male',
        'phone' => '01012345678',
        'parent_phone' => '01112345678',
        'address' => 'Address',
        'city' => 'Cairo',
        'center_name' => 'Center',
    ]);
    Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]);

    expect(fn () => $branch->update(['name' => 'Changed Level']))->toThrow(\LogicException::class);
    expect(fn () => $branch->update(['competition_id' => null]))->toThrow(\LogicException::class);

    $level = CompetitionLevel::create([
        'name' => 'Reusable Historical Level',
        'memorization_amount' => 'خمسة أجزاء',
        'type' => 'organizer',
        'created_by' => $owner->id,
        'status' => 'active',
    ]);
    $linkedBranch = CompetitionBranch::create([
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'name' => $level->name,
        'memorization_amount' => $level->memorization_amount,
        'min_age' => 8,
        'max_age' => 18,
        'total_score' => 100,
        'passing_score' => 50,
    ]);
    $secondStudent = Student::create([
        'full_name' => 'Historical Second Student',
        'birth_date' => '2011-01-01',
        'gender' => 'Male',
        'phone' => '01012345679',
        'parent_phone' => '01112345679',
        'address' => 'Address',
        'city' => 'Cairo',
        'center_name' => 'Center',
    ]);
    Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $linkedBranch->id,
        'student_id' => $secondStudent->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]);

    expect(fn () => $level->update(['description' => 'Changed after history']))->toThrow(\LogicException::class);
});

test('lifecycle action guards match the competition state', function () {
    $owner = User::factory()->create();
    $competition = lifecycleTestCompetition($owner, CompetitionLifecycleService::DRAFT);
    $lifecycle = app(CompetitionLifecycleService::class);

    expect(fn () => $lifecycle->assertAllowsRegistration($competition))->toThrow(ValidationException::class);
    expect(fn () => $lifecycle->assertAllowsEvaluation($competition))->toThrow(ValidationException::class);

    $competition->update(['status' => CompetitionLifecycleService::EVALUATION]);
    $lifecycle->assertAllowsEvaluation($competition);
    $lifecycle->assertAllowsResultGeneration($competition);
    expect(fn () => $lifecycle->assertAllowsCertificateGeneration($competition))->toThrow(ValidationException::class);

    $competition->update(['status' => CompetitionLifecycleService::RESULTS_PUBLISHED]);
    expect(fn () => $lifecycle->assertAllowsResultGeneration($competition))->toThrow(ValidationException::class);
    $lifecycle->assertAllowsCertificateGeneration($competition);
});
