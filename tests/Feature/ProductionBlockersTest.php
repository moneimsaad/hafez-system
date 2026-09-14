<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

function phaseOneCompetition(User $owner): Competition
{
    return Competition::create([
        'title' => 'Phase One Competition',
        'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2),
        'exam_end_date' => now()->addDays(3),
        'location' => 'Main Center',
        'status' => 'active',
        'created_by' => $owner->id,
    ]);
}

function phaseOneBranch(Competition $competition): CompetitionBranch
{
    return CompetitionBranch::create([
        'competition_id' => $competition->id,
        'name' => 'Phase One Branch',
        'memorization_amount' => '10 juz',
        'min_age' => 10,
        'max_age' => 18,
        'total_score' => 100,
        'passing_score' => 50,
    ]);
}

test('inactive users lose existing sessions before protected routes are served', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['status' => 'inactive']);

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('registration status responses are private, minimal, and rate limited', function () {
    $owner = User::factory()->create();
    $competition = phaseOneCompetition($owner);
    $branch = phaseOneBranch($competition);
    $student = Student::create([
        'full_name' => 'طالب الاختبار',
        'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male',
        'phone' => '01012345678',
        'parent_phone' => '01112345678',
        'address' => 'غير محدد',
        'city' => 'القاهرة',
        'center_name' => 'مركز الاختبار',
    ]);
    $registration = Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]);

    $response = $this->post(route('registrations.status.search'), [
        'registration_number' => $registration->registration_number,
    ]);

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee($student->full_name)
        ->assertDontSee($student->phone);

    $this->post(route('registrations.status.search'), ['registration_number' => $registration->registration_number]);
    $this->post(route('registrations.status.search'), ['registration_number' => $registration->registration_number]);
    $this->post(route('registrations.status.search'), ['registration_number' => $registration->registration_number])
        ->assertRedirect(route('registrations.status'))
        ->assertSessionHasErrors('registration_number');
});

test('database prevents duplicate registration for the same competition and student', function () {
    $owner = User::factory()->create();
    $competition = phaseOneCompetition($owner);
    $branch = phaseOneBranch($competition);
    $student = Student::create([
        'full_name' => 'طالب التسجيل',
        'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male',
        'phone' => '01012345679',
        'parent_phone' => '01112345679',
        'address' => 'غير محدد',
        'city' => 'القاهرة',
        'center_name' => 'مركز الاختبار',
    ]);

    Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]);

    expect(fn () => Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'pending',
        'registered_at' => now(),
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('database prevents duplicate evaluation for the same registration and judge', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();
    $competition = phaseOneCompetition($owner);
    $branch = phaseOneBranch($competition);
    $student = Student::create([
        'full_name' => 'طالب التقييم',
        'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male',
        'phone' => '01012345680',
        'parent_phone' => '01112345680',
        'address' => 'غير محدد',
        'city' => 'القاهرة',
        'center_name' => 'مركز الاختبار',
    ]);
    $registration = Registration::create([
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'status' => 'approved',
        'registered_at' => now(),
    ]);
    $data = [
        'competition_id' => $competition->id,
        'branch_id' => $branch->id,
        'student_id' => $student->id,
        'registration_id' => $registration->id,
        'judge_id' => $judge->id,
        'memorization_score' => 40,
        'tajweed_score' => 20,
        'performance_score' => 20,
        'discipline_score' => 10,
        'total_score' => 90,
        'percentage' => 90,
        'status' => 'submitted',
    ];

    Evaluation::create($data);

    expect(fn () => Evaluation::create($data))->toThrow(UniqueConstraintViolationException::class);
});
