<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;

function reviewFixture(User $owner, string $name): Registration
{
    $competition = Competition::create([
        'title' => "مسابقة {$name}", 'location' => 'المركز', 'status' => 'Open for Registration',
        'created_by' => $owner->id, 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3),
    ]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى', 'memorization_amount' => 'جزء', 'min_age' => 8, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $student = Student::create(['full_name' => "طالب {$name}", 'birth_date' => now()->subYears(14), 'gender' => 'Male', 'phone' => fake()->unique()->numerify('010########'), 'parent_phone' => fake()->unique()->numerify('011########'), 'address' => 'العنوان', 'city' => 'القاهرة', 'center_name' => 'المركز']);
    return Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'pending', 'registered_at' => now()]);
}

test('registration index exposes quick review controls without the old view button', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $registration = reviewFixture($owner, 'واحد');

    $this->actingAs($owner)->get(route('registrations.index'))
        ->assertOk()->assertSee('قبول')->assertSee('رفض')->assertSee('select-all-registrations', false)
        ->assertDontSee('>عرض<', false)->assertSee(route('registrations.show', $registration));
});

test('individual and bulk review use the same persisted registration state', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $first = reviewFixture($owner, 'واحد');
    $second = reviewFixture($owner, 'اثنان');
    $third = reviewFixture($owner, 'ثلاثة');

    $this->actingAs($owner)->post(route('registrations.approve', $first))->assertRedirect();
    $this->actingAs($owner)->post(route('registrations.bulk-review'), ['registration_ids' => [$second->id], 'action' => 'approve'])->assertRedirect();
    $this->actingAs($owner)->post(route('registrations.bulk-review'), ['registration_ids' => [$third->id], 'action' => 'reject', 'rejection_reason' => 'البيانات غير مكتملة'])->assertRedirect();

    expect($first->fresh()->status)->toBe('approved')->and($second->fresh()->status)->toBe('approved')
        ->and($third->fresh()->status)->toBe('rejected')->and($third->fresh()->rejection_reason)->toBe('البيانات غير مكتملة');
    $this->actingAs($owner)->get(route('registrations.show', $third))->assertOk()->assertSee('البيانات غير مكتملة');
});

test('bulk review rejects unauthorized or non reviewable registrations without partial changes', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $other = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $owned = reviewFixture($owner, 'المالك');
    $foreign = reviewFixture($other, 'آخر');

    $this->actingAs($owner)->post(route('registrations.bulk-review'), ['registration_ids' => [$owned->id, $foreign->id], 'action' => 'approve'])->assertForbidden();
    expect($owned->fresh()->status)->toBe('pending')->and($foreign->fresh()->status)->toBe('pending');
    $this->actingAs($owner)->post(route('registrations.bulk-review'), ['registration_ids' => [], 'action' => 'approve'])->assertSessionHasErrors('registration_ids');
});
