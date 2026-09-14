<?php

use App\Models\Committee;
use App\Models\CommitteeJudge;
use App\Models\CommitteeManualJudge;
use App\Models\CommitteeStudent;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;

function committeeUxFixture(): array
{
    $owner = User::factory()->create(['role' => 'User']);
    $competition = Competition::create(['title' => 'Committee Competition', 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3)]);
    $levelFive = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'Level Five', 'memorization_amount' => '5 أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $levelFour = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'Level Four', 'memorization_amount' => '4 أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $committee = Committee::create(['competition_id' => $competition->id, 'branch_id' => $levelFive->id, 'name' => 'Committee A', 'exam_date' => now()->addDays(2), 'location' => 'Hall']);

    return [$owner, $competition, $levelFive, $levelFour, $committee];
}

function committeeUxRegistration(Competition $competition, CompetitionBranch $branch, string $status = 'approved'): Registration
{
    $student = Student::create(['full_name' => fake()->unique()->name(), 'birth_date' => '2012-01-01', 'gender' => 'Male', 'phone' => fake()->unique()->numerify('010#######'), 'parent_phone' => fake()->unique()->numerify('011#######'), 'address' => 'Address', 'city' => 'City', 'center_name' => 'Center']);
    return Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => $status, 'registered_at' => now()]);
}

it('shows only accepted registrations from the committees exact competition level', function () {
    [$owner, $competition, $levelFive, $levelFour, $committee] = committeeUxFixture();
    $eligible = committeeUxRegistration($competition, $levelFive);
    $pending = committeeUxRegistration($competition, $levelFive, 'pending');
    $otherLevel = committeeUxRegistration($competition, $levelFour);
    $otherCompetition = Competition::create(['title' => 'Other', 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3)]);
    $otherBranch = CompetitionBranch::create(['competition_id' => $otherCompetition->id, 'name' => 'Other level', 'memorization_amount' => '5 أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $foreign = committeeUxRegistration($otherCompetition, $otherBranch);

    $response = $this->actingAs($owner)->get(route('committees.show', $committee));
    $response->assertOk()->assertSee($eligible->student->full_name)->assertDontSee($pending->student->full_name)->assertDontSee($otherLevel->student->full_name)->assertDontSee($foreign->student->full_name);
});

it('select all assigns every eligible accepted student without assigning ineligible registrations', function () {
    [$owner, $competition, $levelFive, $levelFour, $committee] = committeeUxFixture();
    $eligible = collect(range(1, 4))->map(fn () => committeeUxRegistration($competition, $levelFive));
    $pending = committeeUxRegistration($competition, $levelFive, 'pending');
    $otherLevel = committeeUxRegistration($competition, $levelFour);

    $this->actingAs($owner)->post(route('committees.assign-students', $committee), ['select_all_accepted' => 1])->assertRedirect();
    expect(CommitteeStudent::query()->where('committee_id', $committee->id)->pluck('registration_id')->sort()->values()->all())->toBe($eligible->pluck('id')->sort()->values()->all());
    expect(CommitteeStudent::query()->where('committee_id', $committee->id)->whereIn('registration_id', [$pending->id, $otherLevel->id])->exists())->toBeFalse();
});

it('select all remains authoritative when the paginated page ids are also submitted', function () {
    [$owner, $competition, $levelFive, , $committee] = committeeUxFixture();
    $eligible = collect(range(1, 55))->map(fn () => committeeUxRegistration($competition, $levelFive));
    $visiblePage = $eligible->take(50)->pluck('id')->all();

    $this->actingAs($owner)->post(route('committees.assign-students', $committee), [
        'select_all_accepted' => '1',
        'visible_registration_ids' => $visiblePage,
    ])->assertRedirect();

    expect(CommitteeStudent::query()->where('committee_id', $committee->id)->count())->toBe(55);
});

it('keeps hidden assignments when saving a different visible page selection', function () {
    [$owner, $competition, $levelFive, , $committee] = committeeUxFixture();
    $first = committeeUxRegistration($competition, $levelFive);
    $second = committeeUxRegistration($competition, $levelFive);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $first->student_id, 'registration_id' => $first->id]);

    $this->actingAs($owner)->post(route('committees.assign-students', $committee), ['visible_registration_ids' => [$second->id], 'registration_ids' => [$second->id]])->assertRedirect();
    expect(CommitteeStudent::query()->where('committee_id', $committee->id)->pluck('registration_id')->sort()->values()->all())->toBe([$first->id, $second->id]);
});

it('adds normalizes and removes manual judges without creating user accounts', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $usersBefore = User::count();
    $this->actingAs($owner)->post(route('committees.manual-judges.store', $committee), ['name' => '  الشيخ   أحمد محمد  '])->assertRedirect();
    $manualJudge = CommitteeManualJudge::query()->where('committee_id', $committee->id)->firstOrFail();
    expect($manualJudge->name)->toBe('الشيخ أحمد محمد');
    expect(User::count())->toBe($usersBefore);
    $this->actingAs($owner)->delete(route('committees.manual-judges.destroy', [$committee, $manualJudge]))->assertRedirect();
    expect(CommitteeManualJudge::query()->whereKey($manualJudge->id)->exists())->toBeFalse();
});

it('shows the combined account-linked and manual judge total in the committees list', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $linkedJudge = User::factory()->create();
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $linkedJudge->id]);
    CommitteeManualJudge::insert([
        ['committee_id' => $committee->id, 'name' => 'الشيخ أحمد'],
        ['committee_id' => $committee->id, 'name' => 'الشيخ محمود'],
    ]);

    $this->actingAs($owner)->get(route('committees.index'))
        ->assertOk()
        ->assertSee('الحكام: 3')
        ->assertDontSee('حكام الحسابات')
        ->assertDontSee('الحكام بالاسم')
        ->assertViewHas('committees', function ($committees) use ($committee) {
            $listed = $committees->firstWhere('id', $committee->id);

            return $listed !== null
                && $listed->users_count === 1
                && $listed->manual_judges_count === 2;
        })
        ->assertViewHas('summary', fn ($summary) => $summary['judges'] === 3);
});

it('does not treat manual judges as evaluation or result-completion judges', function () {
    [$owner, $competition, $branch, , $committee] = committeeUxFixture();
    $linkedJudge = User::factory()->create();
    $sameNameUser = User::factory()->create(['name' => 'الشيخ عبد الرحمن']);
    $registration = committeeUxRegistration($competition, $branch);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $linkedJudge->id]);
    CommitteeManualJudge::create(['committee_id' => $committee->id, 'name' => 'الشيخ عبد الرحمن']);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $registration->student_id, 'registration_id' => $registration->id]);
    Evaluation::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $registration->student_id, 'registration_id' => $registration->id,
        'judge_id' => $linkedJudge->id, 'memorization_score' => 30, 'tajweed_score' => 25,
        'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90,
        'percentage' => 90, 'status' => 'submitted',
    ]);

    $this->actingAs($sameNameUser)
        ->get(route('committees.evaluations.bulk', $committee))
        ->assertForbidden();

    $this->actingAs($owner)->post(route('results.generate'), [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
    ])->assertRedirect();

    expect(Result::query()->where('registration_id', $registration->id)->exists())->toBeTrue()
        ->and(CommitteeJudge::query()->where('committee_id', $committee->id)->count())->toBe(1)
        ->and(CommitteeManualJudge::query()->where('committee_id', $committee->id)->count())->toBe(1);
});

it('prevents another organizer from changing manual judges', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $other = User::factory()->create(['role' => 'User']);
    $this->actingAs($other)->post(route('committees.manual-judges.store', $committee), ['name' => 'Unauthorized Judge'])->assertForbidden();
});

it('does not render a platform account directory on committee details', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $linkedJudge = User::factory()->create(['name' => 'Linked Platform Judge', 'email' => 'linked.judge@example.test']);
    $unrelatedUser = User::factory()->create(['name' => 'Unrelated Platform User', 'email' => 'unrelated.user@example.test']);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $linkedJudge->id]);

    $this->actingAs($owner)->get(route('committees.show', $committee))
        ->assertOk()
        ->assertSee('ربط حكم بحساب المنصة')
        ->assertSee('البريد الإلكتروني للحكم')
        ->assertDontSee('حكام لديهم حساب على المنصة')
        ->assertDontSee('حفظ حكام المنصة')
        ->assertDontSee($linkedJudge->email)
        ->assertDontSee($unrelatedUser->email)
        ->assertViewMissing('judges');
});

it('returns the same generic response for known and unknown judge emails without changing links', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $linkedJudge = User::factory()->create(['email' => 'already.linked@example.test']);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $linkedJudge->id]);
    $message = 'ميزة ربط الحكام بحساباتهم على المنصة قيد التطوير حالياً، وستتوفر قريباً.';

    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), ['judge_email' => $linkedJudge->email])
        ->assertRedirect()->assertSessionHas('warning', $message);
    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), ['judge_email' => 'unknown.judge@example.test'])
        ->assertRedirect()->assertSessionHas('warning', $message);
    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), ['judge_ids' => [$linkedJudge->id]])
        ->assertRedirect()->assertSessionHas('warning', $message);

    expect(CommitteeJudge::query()->where('committee_id', $committee->id)->pluck('judge_id')->all())->toBe([$linkedJudge->id]);
});

it('prevents another organizer from submitting a platform judge email', function () {
    [$owner, , , , $committee] = committeeUxFixture();
    $other = User::factory()->create(['role' => 'User']);

    $this->actingAs($other)->post(route('committees.assign-judges', $committee), ['judge_email' => 'judge@example.test'])
        ->assertForbidden();
});
