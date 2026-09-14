<?php

use App\Models\Certificate;
use App\Models\Committee;
use App\Models\CommitteeJudge;
use App\Models\CommitteeManualJudge;
use App\Models\CommitteeStudent;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use App\Services\CompetitionLevelLifecycleService;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

function workflowCompetition(User $owner): Competition
{
    return Competition::create([
        'title' => 'Approved Competition', 'description' => null,
        'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3),
        'location' => 'Main Center', 'status' => 'Open for Registration', 'rules' => null,
        'created_by' => $owner->id,
    ]);
}

function workflowBranch(Competition $competition): CompetitionBranch
{
    return CompetitionBranch::create([
        'competition_id' => $competition->id, 'name' => 'Branch A', 'description' => null,
        'memorization_amount' => '10 juz', 'min_age' => 10, 'max_age' => 18,
        'total_score' => 100, 'passing_score' => 50,
    ]);
}

test('competition display status follows readiness and timeline', function () {
    $now = Carbon::parse('2026-10-01 12:00:00');
    Carbon::setTestNow($now);
    $owner = User::factory()->create();
    $competition = Competition::create([
        'title' => 'Lifecycle Competition', 'location' => 'Center', 'status' => 'Draft', 'created_by' => $owner->id,
        'registration_start_date' => $now->copy()->addDays(2), 'registration_end_date' => $now->copy()->addDays(4),
        'exam_start_date' => $now->copy()->addDays(5), 'exam_end_date' => $now->copy()->addDays(6),
    ]);

    expect($competition->display_status)->toBe('مسودة');
    workflowBranch($competition);
    expect($competition->fresh()->display_status)->toBe('مسودة');

    // Timeline labels remain available for legacy competitions while new
    // competitions use the explicit lifecycle state until transitioned.
    $competition->update(['status' => 'Open for Registration']);

    $competition->update(['registration_start_date' => $now->copy()->subHour(), 'registration_end_date' => $now->copy()->addHour()]);
    expect($competition->fresh()->display_status)->toBe('مفتوح للتسجيل');
    $competition->update(['registration_end_date' => $now->copy()->subHour(), 'exam_start_date' => $now->copy()->addHour()]);
    expect($competition->fresh()->display_status)->toBe('انتهى التسجيل');
    $competition->update(['exam_start_date' => $now->copy()->subHour(), 'exam_end_date' => $now->copy()->addHour()]);
    expect($competition->fresh()->display_status)->toBe('جاري الاختبارات');
    $competition->update(['exam_end_date' => $now->copy()->subHour()]);
    expect($competition->fresh()->display_status)->toBe('منتهية');
    $competition->update(['status' => 'Closed']);
    expect($competition->fresh()->display_status)->toBe('مغلق');
    Carbon::setTestNow();
});

test('competition list status access reuses preloaded branch counts', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);

    foreach (range(1, 3) as $index) {
        $competition = workflowCompetition($owner);
        $competition->update(['title' => "Competition {$index}"]);
        workflowBranch($competition);
    }

    $branchQueries = 0;
    DB::listen(function ($query) use (&$branchQueries) {
        if (str_contains(strtolower($query->sql), 'competition_branches')) {
            $branchQueries++;
        }
    });

    $this->actingAs($owner)
        ->get(route('competitions.index'))
        ->assertOk()
        ->assertSee('مفتوح للتسجيل');

    // withCount contributes one aggregate to the listing query; status
    // accessors must not issue one additional exists() query per row.
    expect($branchQueries)->toBe(1);
});

test('closed public registration remains visible while blocking submissions', function () {
    $owner = User::factory()->create(['username' => 'blocked-owner', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);

    $competition->update(['status' => 'Closed']);
    expect($competition->fresh()->display_status)->toBe('مغلق');
    $this->get(route('competitions.public-register', $competition))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('انتهت فترة التسجيل في هذه المسابقة ولا يمكن إرسال طلبات جديدة حالياً.')
        ->assertSee('الحالة الحالية:')
        ->assertSee('مغلق')
        ->assertSee($competition->title)
        ->assertSee($branch->name)
        ->assertSee('التسجيل غير متاح حالياً')
        ->assertSee('name="full_name"', false)
        ->assertSee('disabled', false);

    $before = Registration::count();
    $this->post(route('registrations.store'), [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'full_name' => 'طالب مسابقة مغلقة',
        'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male',
        'phone' => '01033333333',
        'parent_phone' => '01133333333',
    ])->assertSessionHasErrors(['competition_id' => 'التسجيل غير متاح حاليًا لهذه المسابقة.']);
    expect(Registration::count())->toBe($before);

    $competition->update(['status' => 'Suspended']);
    expect($competition->fresh()->display_status)->toBe('موقوفة');
    $this->get(route('competitions.public-register', $competition))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('موقوفة');
});

test('registration page remains visible after the registration window ends', function () {
    $owner = User::factory()->create(['username' => 'ended-owner', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $competition->update([
        'competition_number' => 1,
        'registration_end_date' => now()->subHour(),
        'exam_start_date' => now()->addDay(),
    ]);

    $this->get(route('competitions.public-register-canonical', [$owner->username, $competition->competition_number]))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('انتهى التسجيل')
        ->assertSee($competition->title)
        ->assertSee($branch->name)
        ->assertSee('التسجيل غير متاح حالياً');
});

test('incomplete competition cannot be registered publicly even during valid dates', function () {
    $owner = User::factory()->create(['username' => 'incomplete-owner', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $this->get(route('competitions.public-register', $competition))->assertNotFound();
});

function workflowStudent(): Student
{
    return Student::create([
        'full_name' => 'Test Student', 'national_id' => 'N'.fake()->unique()->numerify('########'),
        'birth_date' => now()->subYears(14)->toDateString(), 'gender' => 'Male',
        'phone' => '01000000000', 'parent_phone' => '01100000000', 'email' => null,
        'address' => 'Address', 'city' => 'Cairo', 'center_name' => 'Center',
    ]);
}

test('authorized users can create and update competitions while foreign users are denied', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $response = $this->actingAs($owner)->post(route('competitions.store'), [
        'title' => 'Competition', 'registration_start_date' => now()->toDateString(),
        'registration_end_date' => now()->addDay()->toDateString(), 'exam_start_date' => now()->addDays(2)->toDateString(),
        'exam_end_date' => now()->addDays(3)->toDateString(), 'location' => 'Center', 'status' => 'Draft',
    ])->assertRedirect();
    $competition = Competition::firstOrFail();
    $this->actingAs($owner)->get(route('competitions.show', $competition))
        ->assertOk()->assertSee('إضافة مستوى لهذه المسابقة');
    $this->actingAs($owner)->put(route('competitions.update', $competition), [
        'title' => 'Updated', 'registration_start_date' => now()->toDateString(),
        'registration_end_date' => now()->addDay()->toDateString(), 'exam_start_date' => now()->addDays(2)->toDateString(),
        'exam_end_date' => now()->addDays(3)->toDateString(), 'location' => 'Center', 'status' => 'Draft',
    ])->assertRedirect();
    $this->actingAs($other)->get(route('competitions.show', $competition))->assertForbidden();
});

test('existing reusable levels attach through the competition workflow and protect competition ownership', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $other = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى قابل للإضافة', 'memorization_amount' => 'خمسة أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
    ]);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertSee($competition->title)->assertSee($level->name);
    $this->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect();
    expect(CompetitionBranch::where('competition_id', $competition->id)->where('competition_level_id', $level->id)->exists())->toBeTrue();
    $this->actingAs($other)->get(route('competitions.levels.add-existing', $competition))->assertForbidden();
});

test('organizers see only reusable levels they can attach and cannot duplicate assignments', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $other = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $systemLevel = CompetitionLevel::create(['name' => 'حفظ 5 أجزاء', 'memorization_amount' => '5 أجزاء', 'type' => 'system', 'status' => 'active']);
    $privateLevel = CompetitionLevel::create(['name' => 'مستوى خاص', 'memorization_amount' => 'مخصص', 'type' => 'organizer', 'created_by' => $other->id, 'status' => 'active']);
    $payload = ['competition_id' => $competition->id, 'competition_level_id' => $systemLevel->id, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50];

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertSee($systemLevel->name)->assertDontSee($privateLevel->name);
    $this->post(route('competitions.levels.store-existing', $competition), $payload)->assertRedirect();
    expect(CompetitionBranch::where('competition_id', $competition->id)->count())->toBe(1);
    $this->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertDontSee($systemLevel->name)->assertDontSee($privateLevel->name);
    $this->from(route('competitions.levels.add-existing', $competition))
        ->post(route('competitions.levels.store-existing', $competition), $payload)->assertSessionHasErrors('competition_level_id');
    $payload['competition_level_id'] = $privateLevel->id;
    $this->from(route('competitions.levels.add-existing', $competition))
        ->post(route('competitions.levels.store-existing', $competition), $payload)->assertSessionHasErrors('competition_level_id');
});

test('compatibility branch and reusable level assignment stay synchronized', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'حفظ عشرة أجزاء', 'memorization_amount' => '10 أجزاء',
        'type' => 'system', 'status' => 'active',
    ]);

    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'min_age' => 10,
        'max_age' => 18,
        'total_score' => 100,
        'passing_score' => 60,
    ])->assertRedirect();

    $branch = CompetitionBranch::where('competition_id', $competition->id)->firstOrFail();
    $assignment = CompetitionLevelAssignment::where('competition_id', $competition->id)
        ->where('competition_level_id', $level->id)->firstOrFail();

    expect($branch->competition_level_id)->toBe($level->id)
        ->and((int) $assignment->min_age)->toBe(10)
        ->and((int) $assignment->max_age)->toBe(18)
        ->and((float) $assignment->total_score)->toBe(100.0)
        ->and((float) $assignment->passing_score)->toBe(60.0);

    expect($assignment->compatibilityBranch->id)->toBe($branch->id);
});

test('assignment identity cannot be changed while a compatibility branch exists', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى ثابت', 'memorization_amount' => '5 أجزاء',
        'type' => 'system', 'status' => 'active',
    ]);

    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect();

    $assignment = CompetitionLevelAssignment::where('competition_id', $competition->id)->firstOrFail();
    $otherLevel = CompetitionLevel::create([
        'name' => 'مستوى آخر', 'memorization_amount' => '10 أجزاء',
        'type' => 'system', 'status' => 'active',
    ]);

    expect(fn () => $assignment->update(['competition_level_id' => $otherLevel->id]))
        ->toThrow(\LogicException::class);
});

test('used levels are archived instead of deleted and cannot change identity', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى تاريخي', 'memorization_amount' => '5 أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
    ]);

    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect();

    $branchId = CompetitionBranch::where('competition_id', $competition->id)->value('id');
    $level->refresh()->delete();
    $level->refresh();

    expect($level->status)->toBe('archived')
        ->and(CompetitionBranch::find($branchId))->not->toBeNull();

    expect(fn () => $level->update(['name' => 'اسم جديد']))
        ->toThrow(\LogicException::class);
});

test('archived levels are excluded from attachable reusable levels and can be restored by admin', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $admin = User::factory()->create(['role' => 'Platform Admin', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى مؤرشف', 'memorization_amount' => '10 أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'archived',
    ]);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertDontSee('مستوى مؤرشف');

    app(CompetitionLevelLifecycleService::class)->restore($level, $admin);
    expect($level->refresh()->status)->toBe('active');
    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertSee('مستوى مؤرشف');
});

test('used reusable levels are archived through the canonical lifecycle and retain compatibility history', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى بسجل تاريخي', 'memorization_amount' => 'خمسة أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
    ]);
    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id,
        'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect();
    $branchId = CompetitionBranch::where('competition_level_id', $level->id)->value('id');

    $this->delete(route('competition-branches.destroy', $level))->assertRedirect();
    expect($level->fresh()->status)->toBe('archived')
        ->and(CompetitionBranch::find($branchId))->not->toBeNull();
});

test('public registration enforces age and duplicate prevention', function () {
    $owner = User::factory()->create(); $competition = workflowCompetition($owner); $branch = workflowBranch($competition);
    $payload = ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => 'Public Test Student',
        'national_id' => null, 'birth_date' => now()->subYears(14)->toDateString(), 'gender' => 'Male',
        'phone' => '01011111111', 'parent_phone' => '01111111111', 'address' => 'A', 'city' => 'Cairo', 'center_name' => 'Center'];
    $this->post(route('registrations.store'), $payload)
        ->assertRedirect(route('registrations.success'))
        ->assertSessionHas('registration_number');
    $this->from(route('registrations.create'))->post(route('registrations.store'), $payload)->assertRedirect()->assertSessionHasErrors('student');
    $payload['birth_date'] = now()->subYears(25)->toDateString();
    $this->from(route('registrations.create'))->post(route('registrations.store'), $payload)->assertRedirect()->assertSessionHasErrors('birth_date');
});

test('public registration submission is rate limited without affecting admin routes', function () {
    $registrationMiddleware = Route::getRoutes()->getByName('registrations.store')->gatherMiddleware();
    $competitionMiddleware = Route::getRoutes()->getByName('competitions.index')->gatherMiddleware();

    expect($registrationMiddleware)->toContain('throttle:10,1')
        ->and($competitionMiddleware)->not->toContain('throttle:10,1');
});

test('public registration accepts the essential student data without optional details', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);

    $this->post(route('registrations.store'), [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'full_name' => 'طالب بدون بيانات إضافية',
        'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male',
        'phone' => '01022222222',
        'parent_phone' => '01122222222',
    ])->assertRedirect(route('registrations.success'))->assertSessionHas('registration_number');

    $this->get(route('registrations.success'))->assertViewIs('registration.success');

    expect(Student::where('full_name', 'طالب بدون بيانات إضافية')->value('city'))->toBe('غير محدد');
});

test('full workflow covers public registration through result certificate and verification', function () {
    $owner = User::factory()->create(['role' => 'User', 'username' => 'workflow-owner', 'status' => 'active']);
    $judge = User::factory()->create(['role' => 'User']);
    $competitionData = ['title' => 'مسابقة التحقق الشامل', 'location' => 'القاهرة', 'registration_start_date' => now()->subDay()->toDateString(), 'registration_end_date' => now()->addDay()->toDateString(), 'exam_start_date' => now()->addDays(2)->toDateString(), 'exam_end_date' => now()->addDays(3)->toDateString(), 'publication_scope' => 'nationwide'];

    $this->actingAs($owner)->post(route('competitions.store'), $competitionData)->assertRedirect();
    $competition = Competition::query()->where('title', $competitionData['title'])->firstOrFail();
    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Evaluation', 'expected_status' => 'Draft'])->assertSessionHasErrors('status');
    $this->actingAs($owner)->post(route('competition-branches.store'), ['competition_id' => $competition->id, 'name' => 'المستوى الكامل', 'memorization_amount' => 'خمسة أجزاء', 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50])->assertRedirect();
    $branch = CompetitionBranch::query()->where('competition_id', $competition->id)->firstOrFail();

    foreach ([['Published', 'Draft'], ['Registration Open', 'Published']] as [$target, $expected]) {
        $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => $target, 'expected_status' => $expected])->assertRedirect();
        $competition->refresh();
    }
    expect($competition->status)->toBe('Registration Open');

    $this->get(route('competitions.public-register-canonical', [$owner->username, $competition->competition_number]))->assertOk();
    $approvedPayload = ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => 'طالب التحقق الكامل', 'birth_date' => now()->subYears(14)->toDateString(), 'gender' => 'Male', 'phone' => '01033333333', 'parent_phone' => '01133333333', 'city' => 'القاهرة'];
    $this->post(route('registrations.store'), $approvedPayload)->assertRedirect(route('registrations.success'));
    $approvedRegistration = Registration::query()->where('competition_id', $competition->id)->whereHas('student', fn ($student) => $student->where('phone', $approvedPayload['phone']))->firstOrFail();
    $rejectedPayload = [...$approvedPayload, 'full_name' => 'طالب مرفوض تاريخي', 'phone' => '01044444444', 'parent_phone' => '01144444444'];
    $this->post(route('registrations.store'), $rejectedPayload)->assertRedirect(route('registrations.success'));
    $rejectedRegistration = Registration::query()->where('competition_id', $competition->id)->whereHas('student', fn ($student) => $student->where('phone', $rejectedPayload['phone']))->firstOrFail();

    $this->actingAs($owner)->post(route('registrations.approve', $approvedRegistration))->assertRedirect();
    $this->actingAs($owner)->post(route('registrations.reject', $rejectedRegistration), ['rejection_reason' => 'طلب اختبار مرفوض'])->assertRedirect();
    expect($approvedRegistration->fresh()->status)->toBe('approved')->and($rejectedRegistration->fresh()->status)->toBe('rejected');

    $this->actingAs($owner)->post(route('committees.store'), ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'name' => 'لجنة التحقق الشامل', 'exam_date' => now()->addDays(2)->toDateString(), 'location' => 'القاهرة'])->assertRedirect();
    $committee = Committee::query()->where('competition_id', $competition->id)->firstOrFail();
    $this->actingAs($owner)->post(route('committees.assign-students', $committee), ['registration_ids' => [$approvedRegistration->id], 'visible_registration_ids' => [$approvedRegistration->id]])->assertRedirect();
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]);
    CommitteeManualJudge::create(['committee_id' => $committee->id, 'name' => 'حكم يدوي تاريخي']);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $rejectedRegistration->student_id, 'registration_id' => $rejectedRegistration->id]);
    expect($committee->committeeJudges()->count())->toBe(1)->and($committee->manualJudges()->count())->toBe(1);

    foreach ([['Registration Closed', 'Registration Open'], ['Evaluation', 'Registration Closed']] as [$target, $expected]) {
        $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => $target, 'expected_status' => $expected])->assertRedirect();
        $competition->refresh();
    }
    $this->actingAs($judge)->get(route('committees.evaluations.bulk', $committee))->assertOk();
    $this->actingAs($judge)->post(route('committees.evaluations.bulk.store', $committee), ['rows' => [['registration_id' => $approvedRegistration->id, 'scores' => [['score' => 45], ['score' => 22], ['score' => 23]]]]])->assertRedirect();
    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Registration Closed', 'expected_status' => 'Evaluation'])->assertSessionHasErrors('status');

    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])->assertRedirect();
    $result = Result::query()->where('registration_id', $approvedRegistration->id)->firstOrFail();
    expect($result->rank)->toBe(1)->and((float) $result->final_score)->toBe(90.0)->and(Result::query()->where('registration_id', $rejectedRegistration->id)->exists())->toBeFalse();

    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Results Published', 'expected_status' => 'Evaluation'])->assertRedirect();
    $competition->refresh();
    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Registration Closed', 'expected_status' => 'Results Published'])->assertSessionHasErrors('status');
    $this->actingAs($owner)->post(route('results.certificate', $result))->assertRedirect();
    $certificate = Certificate::query()->where('result_id', $result->id)->firstOrFail();
    $this->get(route('certificates.verify', $certificate->certificate_number))->assertOk()->assertSee('شهادة صحيحة');

    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Completed', 'expected_status' => 'Results Published'])->assertRedirect();
    $this->actingAs($owner)->post(route('competitions.status.update', $competition), ['status' => 'Results Published', 'expected_status' => 'Completed'])->assertSessionHasErrors('status');
});

test('registration list filters remain owner-scoped and preserve pagination', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'status' => 'pending', 'registered_at' => now(),
    ]);
    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    $foreignStudent = workflowStudent();
    $foreignStudent->update(['full_name' => 'Foreign Registration Student']);
    Registration::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'status' => 'pending', 'registered_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('registrations.index', [
        'search' => $registration->registration_number,
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'status' => 'pending',
        'date_from' => now()->subDay()->toDateString(),
        'date_to' => now()->addDay()->toDateString(),
        'page' => 1,
    ]));

    $response->assertOk()
        ->assertViewHas('registrations', function ($registrations) use ($registration) {
            $nextPageUrl = $registrations->url(2);
            return $registrations->total() === 1
                && $registrations->first()->id === $registration->id
                && str_contains($nextPageUrl, 'competition_id=')
                && str_contains($nextPageUrl, 'branch_id=')
                && str_contains($nextPageUrl, 'status=pending')
                && str_contains($nextPageUrl, 'date_from=')
                && str_contains($nextPageUrl, 'date_to=');
        })
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1 && $summary['pending'] === 1 && $summary['approved'] === 0 && $summary['rejected'] === 0)
        ->assertViewHas('competitions', fn ($competitions) => $competitions->contains('id', $competition->id) && ! $competitions->contains('id', $foreignCompetition->id))
        ->assertDontSee('Foreign Registration Student');
});

test('validation errors are displayed in Arabic', function () {
    $response = $this->from(route('registrations.create'))
        ->post(route('registrations.store'), [])
        ->assertRedirect()
        ->assertSessionHasErrors('full_name');

    expect($response->getSession()->get('errors')->first('full_name'))->toBe('حقل الاسم بالكامل مطلوب.');

    $this->get(route('registrations.create'))->assertOk();
});

test('each open competition has a public registration page with the competition preselected', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    workflowBranch($competition);

    $this->get(route('competitions.public-register', $competition))
        ->assertOk()
        ->assertSee($competition->title)
        ->assertSee('name="competition_id"', false)
        ->assertSee('type="hidden"', false);

    // Draft competitions are not publicly registrable, even when their dates
    // happen to fall inside the registration window.
    $competition->update(['status' => 'Draft']);
    $this->get(route('competitions.public-register', $competition))->assertNotFound();
});

test('students can track a registration by its generated number', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'pending', 'registered_at' => now()]);

    $this->get(route('registrations.status'))->assertOk();
    $this->post(route('registrations.status.search'), ['registration_number' => $registration->registration_number])
        ->assertOk()
        ->assertSee($registration->registration_number)
        ->assertSee($competition->title)
        ->assertSee($branch->name)
        ->assertSee('قيد المراجعة');

    $registration->update(['status' => 'rejected', 'rejection_reason' => 'بيانات غير مكتملة']);
    $this->post(route('registrations.status.search'), ['registration_number' => $registration->registration_number])
        ->assertOk()
        ->assertSee('بيانات غير مكتملة')
        ->assertDontSee($student->phone);
});

test('unknown registration numbers do not expose registration data', function () {
    $this->post(route('registrations.status.search'), ['registration_number' => '2026-99999'])
        ->assertOk()
        ->assertSee('لم يتم العثور على تسجيل');
});

test('platform admin can update platform settings and users cannot', function () {
    $admin = User::factory()->create(['role' => 'Platform Admin']);
    $user = User::factory()->create(['role' => 'User']);
    $payload = [
        'platform_name' => 'منصة حافظ', 'organization_name' => 'الجهة المنظمة',
        'organization_description' => 'وصف', 'organization_address' => 'القاهرة',
        'organization_phone' => '01000000000', 'organization_email' => 'info@example.com',
        'platform_footer' => 'حقوق النشر محفوظة', 'certificate_issuer' => 'الجهة المنظمة',
        'certificate_title' => 'شهادة حفظ', 'certificate_footer' => 'مع تمنياتنا بالتوفيق',
        'certificate_number_prefix' => 'HAFEZ', 'certificate_show_qr' => '1',
        'certificate_verification_text' => 'تحقق من الشهادة',
    ];

    $this->actingAs($user)->get(route('platform-admin.settings.edit'))->assertForbidden();
    $this->actingAs($admin)->get(route('platform-admin.settings.edit'))->assertOk();
    $this->actingAs($admin)->put(route('platform-admin.settings.update'), $payload)->assertRedirect();

    $this->assertDatabaseHas('settings', ['key' => 'platform.name', 'value' => 'منصة حافظ']);
    $this->assertDatabaseHas('settings', ['key' => 'certificate.number_prefix', 'value' => 'HAFEZ']);
});

test('committee creation keeps student assignment working while platform judge linking is unavailable', function () {
    $owner = User::factory()->create(); $judge = User::factory()->create();
    $competition = workflowCompetition($owner); $branch = workflowBranch($competition); $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $this->actingAs($owner)->post(route('committees.store'), ['competition_id' => $competition->id, 'name' => 'Committee', 'branch_id' => $branch->id, 'exam_date' => now()->addDay()->toDateTimeString(), 'location' => 'Room'])->assertRedirect();
    $committee = Committee::firstOrFail();
    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), ['judge_ids' => [$judge->id]])
        ->assertRedirect()->assertSessionHas('warning', 'ميزة ربط الحكام بحساباتهم على المنصة قيد التطوير حالياً، وستتوفر قريباً.');
    $this->actingAs($owner)->post(route('committees.assign-students', $committee), ['registration_ids' => [$registration->id]])->assertRedirect();
    expect(CommitteeJudge::where('committee_id', $committee->id)->where('judge_id', $judge->id)->exists())->toBeFalse();
    expect(CommitteeStudent::where('committee_id', $committee->id)->where('registration_id', $registration->id)->exists())->toBeTrue();
});

test('committee judge account linking is a no-op and preserves authorization', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $existingJudge = User::factory()->create(['email' => 'existing.judge@example.test']);
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $committee = Committee::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'name' => 'Secure Committee', 'exam_date' => now(), 'location' => 'Room',
    ]);

    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $existingJudge->id]);

    $this->actingAs($otherOwner)->post(route('committees.assign-judges', $committee), [
        'judge_email' => 'outsider@example.test',
    ])->assertForbidden();

    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), [
        'judge_email' => $existingJudge->email,
    ])->assertRedirect()->assertSessionHas('warning', 'ميزة ربط الحكام بحساباتهم على المنصة قيد التطوير حالياً، وستتوفر قريباً.');
    $this->actingAs($owner)->post(route('committees.assign-judges', $committee), [
        'judge_ids' => [$otherOwner->id],
    ])->assertRedirect()->assertSessionHas('warning', 'ميزة ربط الحكام بحساباتهم على المنصة قيد التطوير حالياً، وستتوفر قريباً.');

    expect(CommitteeJudge::where('committee_id', $committee->id)->pluck('judge_id')->all())->toBe([$existingJudge->id]);
});

test('committee list search and filters preserve owner scope while admins see all', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['role' => 'Platform Admin']);
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    Committee::create([
        'competition_id' => $competition->id, 'name' => 'Owner Committee',
        'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room',
    ]);
    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    Committee::create([
        'competition_id' => $foreignCompetition->id, 'name' => 'Foreign Committee',
        'branch_id' => $foreignBranch->id, 'exam_date' => now(), 'location' => 'Room',
    ]);

    $ownerResponse = $this->actingAs($owner)->get(route('committees.index', [
        'search' => 'Foreign Committee',
        'competition_id' => $foreignCompetition->id,
        'branch_id' => $foreignBranch->id,
    ]));
    $ownerResponse->assertOk()
        ->assertViewHas('committees', fn ($committees) => $committees->total() === 0)
        ->assertViewHas('competitions', fn ($competitions) => ! $competitions->contains('id', $foreignCompetition->id));

    $this->actingAs($admin)->get(route('committees.index', ['search' => 'Foreign Committee']))
        ->assertOk()
        ->assertSee('Foreign Committee');
});

test('committee index preloads judge and student counts without row count queries', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $committee = Committee::create([
        'competition_id' => $competition->id,
        'name' => 'Counted Committee',
        'branch_id' => $branch->id,
        'exam_date' => now(),
        'location' => 'Room',
    ]);
    $judge = User::factory()->create();
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'approved',
        'registered_at' => now(),
    ]);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);

    DB::enableQueryLog();
    $response = $this->actingAs($owner)->get(route('committees.index'));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $response->assertOk()->assertViewHas('committees', function ($committees) use ($committee) {
        $row = $committees->getCollection()->firstWhere('id', $committee->id);
        return $row !== null && $row->users_count === 1 && $row->students_count === 1;
    })->assertViewHas('summary', fn ($summary) => $summary['committees'] === 1 && $summary['judges'] === 1 && $summary['students'] === 1);
    expect(collect($queries)->filter(fn ($query) => str_contains(strtolower($query['query']), 'committee_judges') || str_contains(strtolower($query['query']), 'committee_manual_judges') || str_contains(strtolower($query['query']), 'committee_students'))->count())->toBe(4);
});

test('committee index keeps pagination and owner scope while using aggregate counts', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    foreach (range(1, 16) as $number) {
        Committee::create([
            'competition_id' => $competition->id,
            'name' => 'Owner Committee '.$number,
            'branch_id' => $branch->id,
            'exam_date' => now(),
            'location' => 'Room',
        ]);
    }
    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    Committee::create([
        'competition_id' => $foreignCompetition->id,
        'name' => 'Foreign Committee',
        'branch_id' => $foreignBranch->id,
        'exam_date' => now(),
        'location' => 'Room',
    ]);

    $response = $this->actingAs($owner)->get(route('committees.index', ['page' => 2]));

    $response->assertOk()->assertViewHas('committees', function ($committees) {
        return $committees->total() === 16
            && $committees->currentPage() === 2
            && $committees->count() === 1
            && $committees->first()->users_count === 0
            && $committees->first()->students_count === 0;
    })->assertViewHas('summary', fn ($summary) => $summary['committees'] === 16 && $summary['judges'] === 0 && $summary['students'] === 0)
        ->assertDontSee('Foreign Committee');
});

test('committee assignment rejects pending registrations', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'pending', 'registered_at' => now()]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);

    $this->from(route('committees.show', $committee))
        ->actingAs($owner)
        ->post(route('committees.assign-students', $committee), ['registration_ids' => [$registration->id]])
        ->assertRedirect()
        ->assertSessionHasErrors('registration_ids');

    expect(CommitteeStudent::where('committee_id', $committee->id)->exists())->toBeFalse();
});

test('committee details only list approved registrations for assignment', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]);
    $committee = Committee::create([
        'competition_id' => $competition->id,
        'name' => 'Committee',
        'branch_id' => $branch->id,
        'exam_date' => now(),
        'location' => 'Room',
    ]);

    $this->actingAs($owner)
        ->get(route('committees.show', $committee))
        ->assertOk()
        ->assertDontSee($student->full_name);
});

test('assigned judge can submit evaluation and an unassigned user cannot', function () {
    $owner = User::factory()->create(); $judge = User::factory()->create(); $outsider = User::factory()->create();
    $competition = workflowCompetition($owner); $branch = workflowBranch($competition); $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]); CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    $payload = ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15];
    $this->actingAs($judge)->post(route('evaluations.store'), $payload)->assertRedirect();
    $this->actingAs($outsider)->post(route('evaluations.store'), $payload)->assertSessionHasErrors('judge_id');
    $this->actingAs($judge)->from(route('evaluations.create'))
        ->post(route('evaluations.store'), $payload)
        ->assertRedirect()
        ->assertSessionHasErrors('registration_id');
    expect(Evaluation::count())->toBe(1);
});

test('evaluation list filters stay within authorization scope and preserve pagination', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $other = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'approved',
        'registered_at' => now(),
    ]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Evaluation Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    Evaluation::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25,
        'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90,
        'percentage' => 90, 'status' => 'submitted',
    ]);
    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    $foreignStudent = workflowStudent();
    $foreignStudent->update(['full_name' => 'Foreign Student']);
    $foreignRegistration = Registration::create([
        'competition_id' => $foreignCompetition->id,
        'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id,
        'status' => 'approved',
        'registered_at' => now(),
    ]);
    Evaluation::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'registration_id' => $foreignRegistration->id,
        'judge_id' => $other->id, 'memorization_score' => 10, 'tajweed_score' => 10,
        'performance_score' => 10, 'discipline_score' => 10, 'total_score' => 40,
        'percentage' => 40, 'status' => 'submitted',
    ]);

    $response = $this->actingAs($owner)->get(route('evaluations.index', [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'judge_id' => $judge->id,
        'search' => $student->full_name,
        'status' => 'submitted',
        'committee_id' => $committee->id,
        'page' => 1,
    ]));

    $response->assertOk()
        ->assertViewHas('evaluations', function ($evaluations) use ($student) {
            $nextPageUrl = $evaluations->url(2);
            return $evaluations->total() === 1
                && $evaluations->first()->student_id === $student->id
                && str_contains($nextPageUrl, 'competition_id=')
                && str_contains($nextPageUrl, 'branch_id=')
                && str_contains($nextPageUrl, 'judge_id=')
                && str_contains($nextPageUrl, 'status=submitted')
                && str_contains($nextPageUrl, 'committee_id=');
        })
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1 && $summary['pending'] === 0 && $summary['completed'] === 1 && $summary['average_percentage'] === 90.0)
        ->assertViewHas('committees', fn ($committees) => $committees->contains('id', $committee->id))
        ->assertViewHas('competitions', fn ($competitions) => $competitions->contains('id', $competition->id) && ! $competitions->contains('id', $foreignCompetition->id))
        ->assertDontSee($foreignStudent->full_name);
});

test('evaluation list only shows next-stage navigation for an authorized completed evaluation context', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $competition = workflowCompetition($owner);
    $competition->update(['status' => 'Evaluation']);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Navigation Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);

    $context = ['competition_id' => $competition->id, 'branch_id' => $branch->id];
    $this->actingAs($owner)->get(route('evaluations.index', $context))
        ->assertOk()
        ->assertSee(route('results.index'), false)
        ->assertViewHas('nextStageAvailable', true);
    expect($competition->fresh()->status)->toBe('Evaluation')
        ->and(Result::query()->where('competition_id', $competition->id)->count())->toBe(0);

    $this->actingAs($owner)->get(route('evaluations.index', ['competition_id' => $competition->id]))
        ->assertViewHas('nextStageAvailable', false);
    $this->actingAs($judge)->get(route('evaluations.index', $context))
        ->assertViewHas('nextStageAvailable', false);

    $secondJudge = User::factory()->create();
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $secondJudge->id]);
    $this->actingAs($owner)->get(route('evaluations.index', $context))
        ->assertViewHas('nextStageAvailable', false);
});

test('evaluation list exposes edit only for the current judges unambiguous assignment and keeps details navigation', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $otherJudge = User::factory()->create();
    $competition = workflowCompetition($owner);
    $competition->update(['status' => 'Evaluation']);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Edit Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeJudge::insert([
        ['committee_id' => $committee->id, 'judge_id' => $judge->id],
        ['committee_id' => $committee->id, 'judge_id' => $otherJudge->id],
    ]);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    $ownEvaluation = Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);
    $otherEvaluation = Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $otherJudge->id, 'memorization_score' => 29, 'tajweed_score' => 24, 'performance_score' => 19, 'discipline_score' => 14, 'total_score' => 86, 'percentage' => 86, 'status' => 'submitted']);

    $this->actingAs($judge)->get(route('evaluations.index', ['competition_id' => $competition->id, 'branch_id' => $branch->id]))
        ->assertOk()
        ->assertSee(route('committees.evaluations.bulk', $committee), false)
        ->assertSee(route('evaluations.show', $ownEvaluation), false)
        ->assertSee('data-evaluation-row', false)
        ->assertSee("event.target.closest('a, button, input, select, textarea, label, form')", false)
        ->assertViewHas('editableCommitteeIds', fn ($ids) => ($ids[$ownEvaluation->id] ?? null) === $committee->id && ! array_key_exists($otherEvaluation->id, $ids));

    $this->actingAs($owner)->get(route('evaluations.index', ['competition_id' => $competition->id, 'branch_id' => $branch->id]))
        ->assertDontSee('data-evaluation-edit', false)
        ->assertSee(route('evaluations.show', $ownEvaluation), false)
        ->assertSee(route('evaluations.show', $otherEvaluation), false);
});

test('evaluation list hides edit for ambiguous committee assignments', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $competition = workflowCompetition($owner);
    $competition->update(['status' => 'Evaluation']);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $firstCommittee = Committee::create(['competition_id' => $competition->id, 'name' => 'First Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    $secondCommittee = Committee::create(['competition_id' => $competition->id, 'name' => 'Second Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeJudge::insert([
        ['committee_id' => $firstCommittee->id, 'judge_id' => $judge->id],
        ['committee_id' => $secondCommittee->id, 'judge_id' => $judge->id],
    ]);
    CommitteeStudent::insert([
        ['committee_id' => $firstCommittee->id, 'student_id' => $student->id, 'registration_id' => $registration->id],
        ['committee_id' => $secondCommittee->id, 'student_id' => $student->id, 'registration_id' => $registration->id],
    ]);
    $evaluation = Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);

    $this->actingAs($judge)->get(route('evaluations.index', ['competition_id' => $competition->id, 'branch_id' => $branch->id]))
        ->assertDontSee('data-evaluation-edit', false)
        ->assertSee(route('evaluations.show', $evaluation), false)
        ->assertViewHas('editableCommitteeIds', fn ($ids) => ! array_key_exists($evaluation->id, $ids));
});

test('authorized owner can generate results with ranking and cannot expose foreign results', function () {
    $owner = User::factory()->create(); $other = User::factory()->create(); $student = workflowStudent();
    $competition = workflowCompetition($owner); $branch = workflowBranch($competition);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $owner->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);
    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])->assertRedirect();
    $result = Result::firstOrFail(); expect($result->rank)->toBe(1);
    $this->actingAs($other)->get(route('results.show', $result))->assertForbidden();
});

test('result generation rejects a registration until every assigned judge submits', function () {
    $owner = User::factory()->create();
    $judgeOne = User::factory()->create();
    $judgeTwo = User::factory()->create();
    $student = workflowStudent();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $committee = Committee::create(['competition_id' => $competition->id, 'name' => 'Complete Committee', 'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room']);
    CommitteeJudge::insert([
        ['committee_id' => $committee->id, 'judge_id' => $judgeOne->id],
        ['committee_id' => $committee->id, 'judge_id' => $judgeTwo->id],
    ]);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judgeOne->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);

    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])
        ->assertSessionHasErrors(['branch_id' => 'لم تكتمل تقييمات جميع الحكام لهذا الطالب بعد.']);
    expect(Result::where('registration_id', $registration->id)->exists())->toBeFalse();

    Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $judgeTwo->id, 'memorization_score' => 28, 'tajweed_score' => 24, 'performance_score' => 19, 'discipline_score' => 14, 'total_score' => 85, 'percentage' => 85, 'status' => 'submitted']);
    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])->assertRedirect();
    expect(Result::where('registration_id', $registration->id)->exists())->toBeTrue();
});

test('rejected committee assignments do not block results or receive generated results', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $approvedStudent = workflowStudent();
    $rejectedStudent = workflowStudent();
    $approvedRegistration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $approvedStudent->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    $rejectedRegistration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $rejectedStudent->id, 'status' => 'rejected', 'registered_at' => now(),
    ]);
    $committee = Committee::create([
        'competition_id' => $competition->id, 'name' => 'Results Committee',
        'branch_id' => $branch->id, 'exam_date' => now(), 'location' => 'Room',
    ]);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]);
    CommitteeStudent::insert([
        ['committee_id' => $committee->id, 'student_id' => $approvedStudent->id, 'registration_id' => $approvedRegistration->id],
        ['committee_id' => $committee->id, 'student_id' => $rejectedStudent->id, 'registration_id' => $rejectedRegistration->id],
    ]);
    Evaluation::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $approvedStudent->id, 'registration_id' => $approvedRegistration->id,
        'judge_id' => $judge->id, 'memorization_score' => 30, 'tajweed_score' => 25,
        'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90,
        'percentage' => 90, 'status' => 'submitted',
    ]);
    $rejectedEvaluation = Evaluation::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $rejectedStudent->id, 'registration_id' => $rejectedRegistration->id,
        'judge_id' => $judge->id, 'memorization_score' => 10, 'tajweed_score' => 10,
        'performance_score' => 10, 'discipline_score' => 10, 'total_score' => 40,
        'percentage' => 40, 'status' => 'submitted',
    ]);

    $this->actingAs($owner)->post(route('results.generate'), [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
    ])->assertRedirect();

    expect(Result::query()->where('registration_id', $approvedRegistration->id)->exists())->toBeTrue()
        ->and(Result::query()->where('registration_id', $rejectedRegistration->id)->exists())->toBeFalse()
        ->and(CommitteeStudent::query()->where('registration_id', $rejectedRegistration->id)->exists())->toBeTrue()
        ->and(Evaluation::query()->whereKey($rejectedEvaluation->id)->exists())->toBeTrue();
});

test('results list filters are scoped to the owner and preserve query parameters', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    Result::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'final_score' => 90, 'percentage' => 90, 'rank' => 1,
        'result_status' => 'successful',
    ]);

    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    $foreignStudent = workflowStudent();
    $foreignStudent->update(['full_name' => 'Foreign Result Student']);
    $foreignRegistration = Registration::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    Result::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'registration_id' => $foreignRegistration->id,
        'final_score' => 40, 'percentage' => 40, 'rank' => 1,
        'result_status' => 'failed',
    ]);

    $response = $this->actingAs($owner)->get(route('results.index', [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'status' => 'successful',
        'rank' => 1,
        'page' => 1,
    ]));

    $response->assertOk()
        ->assertViewHas('results', function ($results) use ($student) {
            $nextPageUrl = $results->url(2);
            return $results->total() === 1
                && $results->first()->student_id === $student->id
                && str_contains($nextPageUrl, 'branch_id=')
                && str_contains($nextPageUrl, 'rank=1');
        })
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1 && $summary['successful'] === 1 && $summary['failed'] === 0)
        ->assertViewHas('competitions', fn ($competitions) => $competitions->contains('id', $competition->id) && ! $competitions->contains('id', $foreignCompetition->id))
        ->assertDontSee('Foreign Result Student');
});

test('result generation options include owned competitions with no existing results', function () {
    $owner = User::factory()->create();
    $competition = workflowCompetition($owner);
    workflowBranch($competition);

    $response = $this->actingAs($owner)->get(route('results.index'));

    $response->assertOk()
        ->assertViewHas('generationCompetitions', fn ($competitions) => $competitions->contains('id', $competition->id))
        ->assertViewHas('results', fn ($results) => $results->total() === 0);
});

test('generated results remain final when results are regenerated', function () {
    $owner = User::factory()->create();
    $student = workflowStudent();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $owner->id, 'memorization_score' => 30, 'tajweed_score' => 25, 'performance_score' => 20, 'discipline_score' => 15, 'total_score' => 90, 'percentage' => 90, 'status' => 'submitted']);

    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])->assertRedirect();
    $result = Result::firstOrFail();
    $this->actingAs($owner)->post(route('results.generate'), ['competition_id' => $competition->id, 'branch_id' => $branch->id])->assertRedirect();

    expect($result->fresh()->approved_at)->not->toBeNull()
        ->and((float) $result->fresh()->final_score)->toBe(90.0);
});

test('final result generates a certificate and public verification succeeds', function () {
    $owner = User::factory()->create(); $student = workflowStudent(); $competition = workflowCompetition($owner); $branch = workflowBranch($competition);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $result = Result::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'final_score' => 90, 'percentage' => 90, 'rank' => 1, 'result_status' => 'successful']);
    $this->actingAs($owner)->post(route('results.certificate', $result))->assertRedirect();
    $certificate = Certificate::firstOrFail(); expect($certificate->certificate_number)->not->toBeEmpty();
    expect($certificate->certificate_type)->toBe('شهادة تقدير')->and($certificate->qr_code)->toContain('/certificates/verify/');
    $this->get(route('certificates.verify', $certificate->certificate_number))->assertOk()->assertSee('شهادة صحيحة');
});

test('certificate list filters remain owner-scoped and preserve query parameters', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $competition = workflowCompetition($owner);
    $branch = workflowBranch($competition);
    $student = workflowStudent();
    $registration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    $result = Result::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'final_score' => 90, 'percentage' => 90, 'rank' => 1,
        'result_status' => 'successful',
    ]);
    $certificate = Certificate::create([
        'student_id' => $student->id, 'competition_id' => $competition->id,
        'branch_id' => $branch->id, 'result_id' => $result->id,
        'certificate_type' => 'شهادة تقدير', 'certificate_number' => 'CERT-OWNER-1',
        'qr_code' => 'CERT-OWNER-1', 'file_path' => 'certificates/CERT-OWNER-1.pdf', 'issued_at' => now(),
    ]);
    $foreignCompetition = workflowCompetition($other);
    $foreignBranch = workflowBranch($foreignCompetition);
    $foreignStudent = workflowStudent();
    $foreignStudent->update(['full_name' => 'Foreign Certificate Student']);
    $foreignRegistration = Registration::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    $foreignResult = Result::create([
        'competition_id' => $foreignCompetition->id, 'branch_id' => $foreignBranch->id,
        'student_id' => $foreignStudent->id, 'registration_id' => $foreignRegistration->id,
        'final_score' => 40, 'percentage' => 40, 'rank' => 1,
        'result_status' => 'failed',
    ]);
    Certificate::create([
        'student_id' => $foreignStudent->id, 'competition_id' => $foreignCompetition->id,
        'branch_id' => $foreignBranch->id, 'result_id' => $foreignResult->id,
        'certificate_type' => 'شهادة مشاركة', 'certificate_number' => 'CERT-FOREIGN-1',
        'qr_code' => 'CERT-FOREIGN-1', 'file_path' => 'certificates/CERT-FOREIGN-1.pdf', 'issued_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('certificates.index', [
        'search' => $certificate->certificate_number,
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'certificate_type' => 'شهادة تقدير',
        'result_status' => 'successful',
        'page' => 1,
    ]));

    $response->assertOk()
        ->assertViewHas('certificates', function ($certificates) use ($certificate) {
            $nextPageUrl = $certificates->url(2);
            return $certificates->total() === 1
                && $certificates->first()->id === $certificate->id
                && str_contains($nextPageUrl, 'branch_id=')
                && str_contains($nextPageUrl, 'certificate_type=');
        })
        ->assertViewHas('competitions', fn ($competitions) => $competitions->contains('id', $competition->id) && ! $competitions->contains('id', $foreignCompetition->id))
        ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1
            && $summary['generated'] === 1
            && $summary['available_results'] === 0
            && $summary['issued_today'] === 1)
        ->assertDontSee('Foreign Certificate Student');
});

test('report statistics remain scoped to the authenticated competition owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    workflowCompetition($owner);
    workflowCompetition($other);

    $this->actingAs($owner)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertViewHas('statistics', fn ($statistics) => $statistics['competitions'] === 1
            && $statistics['branches'] === 0
            && $statistics['registrations'] === 0
            && $statistics['results'] === 0
            && $statistics['certificates'] === 0);
});
