<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use App\Models\Student;
use App\Services\EgyptianNationalIdService;
use App\Models\User;

function nationalIdRegistrationFixture(): array
{
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = Competition::create([
        'title' => 'National ID Competition', 'location' => 'Center', 'status' => 'Open for Registration',
        'created_by' => $owner->id, 'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2),
        'exam_end_date' => now()->addDays(3),
    ]);
    $branch = CompetitionBranch::create([
        'competition_id' => $competition->id, 'name' => 'Level', 'memorization_amount' => '5 juz',
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ]);
    $nationalId = '31201010200011';
    $parsed = app(EgyptianNationalIdService::class)->parse($nationalId);

    return [$competition, $branch, $nationalId, $parsed];
}

function nationalIdPayload(Competition $competition, CompetitionBranch $branch, string $nationalId, array $parsed): array
{
    return [
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'full_name' => 'محمد أحمد علي', 'national_id' => $nationalId,
        'birth_date' => $parsed['birth_date'], 'gender' => $parsed['gender'],
        'phone' => '01012345678', 'parent_phone' => '01112345678',
    ];
}

it('accepts a valid national ID and stores its normalized identity data', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();

    $this->post(route('registrations.store'), nationalIdPayload($competition, $branch, $nationalId, $parsed))
        ->assertRedirect(route('registrations.success'));

    expect(Registration::query()->where('competition_id', $competition->id)->exists())->toBeTrue();
});

it('derives the documented DOB from national ID 30101011361418', function () {
    $service = app(EgyptianNationalIdService::class);
    expect($service->parse('30101011361418'))->toMatchArray(['birth_date' => '2001-01-01', 'gender' => 'Male', 'governorate' => 'الشرقية']);
});

it('ignores DOB or gender tampering when a national ID is supplied', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
    $payload['birth_date'] = '2011-01-01';
    $payload['gender'] = $parsed['gender'] === 'Male' ? 'Female' : 'Male';

    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    $student = Student::query()->latest('id')->firstOrFail();
    expect($student->birth_date->format('Y-m-d'))->toBe($parsed['birth_date'])
        ->and($student->gender)->toBe($parsed['gender']);
});

it('allows the user to change the national ID governorate for an unrestricted competition', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
    $payload['governorate'] = 'البحيرة';

    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    expect(Registration::query()->latest('id')->value('governorate'))->toBe('البحيرة');
});

it('enforces the final selected governorate for a governorate-scoped competition', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    $competition->update(['publication_scope' => 'governorate', 'target_governorate' => 'الإسكندرية']);
    $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
    $payload['governorate'] = 'البحيرة';

    $this->from(route('registrations.create'))->post(route('registrations.store'), $payload)
        ->assertRedirect()->assertSessionHasErrors('governorate');
});

it('accepts three and five part Arabic names and normalizes repeated spaces', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    foreach (['محمد أحمد علي', 'محمد أحمد علي حسن محمود'] as $name) {
        $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
        $payload['national_id'] = null;
        $payload['full_name'] = $name;
        $payload['phone'] = fake()->unique()->numerify('010########');
        $payload['parent_phone'] = fake()->unique()->numerify('011########');
        $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    }

    $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
    $payload['national_id'] = null;
    $payload['full_name'] = '  محمد   أحمد    علي  ';
    $payload['phone'] = '01212345678';
    $payload['parent_phone'] = '01512345678';
    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    expect(Student::query()->latest('id')->value('full_name'))->toBe('محمد أحمد علي');
});

it('rejects names outside the three to five part and eighty character limits', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    foreach (['محمد', 'محمد أحمد', 'محمد أحمد علي حسن محمود سعيد', str_repeat('محمد ', 17)] as $name) {
        $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
        $payload['national_id'] = null;
        $payload['full_name'] = $name;
        $this->from(route('registrations.create'))->post(route('registrations.store'), $payload)
            ->assertRedirect()->assertSessionHasErrors('full_name');
    }
});

it('accepts all Egyptian student and parent mobile prefixes, including equal numbers', function () {
    foreach (['01012345678', '01112345678', '01212345678', '01512345678'] as $phone) {
        [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
        $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
        $payload['national_id'] = null;
        $payload['phone'] = $phone;
        $payload['parent_phone'] = $phone;
        $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    }
});

it('rejects invalid student and parent mobile values', function () {
    foreach ([['0101234567', '01112345678'], ['01912345678', '01112345678'], ['010123456789', '01112345678'], ['01012345678', 'abc0112345678']] as [$phone, $parentPhone]) {
        [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
        $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
        $payload['national_id'] = null;
        $payload['phone'] = $phone;
        $payload['parent_phone'] = $parentPhone;
        $response = $this->from(route('registrations.create'))->post(route('registrations.store'), $payload)->assertRedirect();
        if (! preg_match('/^(010|011|012|015)\\d{8}$/', $phone)) {
            $response->assertSessionHasErrors('phone');
        }
        if (! preg_match('/^(010|011|012|015)\\d{8}$/', $parentPhone)) {
            $response->assertSessionHasErrors('parent_phone');
        }
    }
});

it('keeps national ID optional when the field is blank', function () {
    [$competition, $branch, $nationalId, $parsed] = nationalIdRegistrationFixture();
    $payload = nationalIdPayload($competition, $branch, $nationalId, $parsed);
    $payload['national_id'] = '';

    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
});
