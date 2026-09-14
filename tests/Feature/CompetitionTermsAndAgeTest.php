<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

function termsAgeCompetition(array $overrides = []): array
{
    $owner = User::factory()->create();
    $competition = Competition::create(array_merge([
        'title' => 'مسابقة الشروط والأعمار',
        'competition_number' => 1,
        'description' => null,
        'additional_terms' => null,
        'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2),
        'exam_end_date' => now()->addDays(3),
        'location' => 'القاهرة',
        'status' => 'Active',
        'created_by' => $owner->id,
        'publication_scope' => 'unlisted',
    ], $overrides));
    return [$owner, $competition];
}

it('persists optional competition terms and renders them escaped publicly', function () {
    $this->withoutVite();
    [$owner, $competition] = termsAgeCompetition(['additional_terms' => "يرجى الحضور مبكراً.\n<script>alert('x')</script>"]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'جزء عم', 'memorization_amount' => 'جزء', 'min_age' => null, 'max_age' => null, 'total_score' => 100, 'passing_score' => 50]);

    $this->get(route('competitions.public-register-canonical', [$owner->username, 1]))
        ->assertOk()
        ->assertSee('يرجى الحضور مبكراً.')
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert', false);
});

it('organizer can create and update optional registration terms within the allowed length', function () {
    $owner = User::factory()->create();
    $payload = [
        'title' => 'مسابقة شروط التسجيل',
        'additional_terms' => 'الحضور قبل موعد الاختبار بنصف ساعة.',
        'registration_start_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'registration_end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'exam_start_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'exam_end_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
        'location' => 'القاهرة',
        'publication_scope' => 'unlisted',
    ];

    $this->actingAs($owner)->post(route('competitions.store'), $payload)->assertRedirect();
    $competition = Competition::where('title', $payload['title'])->firstOrFail();
    expect($competition->additional_terms)->toBe($payload['additional_terms']);

    $this->actingAs($owner)->put(route('competitions.update', $competition), [...$payload, 'additional_terms' => 'يرجى إحضار بطاقة إثبات الشخصية.'])
        ->assertRedirect(route('competitions.show', $competition));
    expect($competition->fresh()->additional_terms)->toBe('يرجى إحضار بطاقة إثبات الشخصية.');

    $this->actingAs($owner)->post(route('competitions.store'), [...$payload, 'title' => 'مسابقة نص طويل', 'additional_terms' => str_repeat('أ', 5001)])
        ->assertSessionHasErrors('additional_terms');
});

it('renders assignment-specific level conditions before public registration', function () {
    $this->withoutVite();
    [$owner, $competition] = termsAgeCompetition(['additional_terms' => 'الحضور قبل موعد الاختبار بنصف ساعة.']);
    $branch = CompetitionBranch::create([
        'competition_id' => $competition->id,
        'name' => 'خمسة أجزاء',
        'description' => 'مستوى مخصص لحفظ خمسة أجزاء من القرآن الكريم.',
        'memorization_amount' => '5 أجزاء',
        'min_age' => 10,
        'max_age' => 17,
        'total_score' => 100,
        'passing_score' => 60,
    ]);

    $this->get(route('competitions.public-register-canonical', [$owner->username, 1]))
        ->assertOk()
        ->assertSee('شروط وتعليمات المسابقة')
        ->assertSee('الحضور قبل موعد الاختبار بنصف ساعة.')
        ->assertSee('خمسة أجزاء')
        ->assertSee('5 أجزاء')
        ->assertSee('من 10 إلى 17 سنة')
        ->assertSee('مستوى مخصص لحفظ خمسة أجزاء من القرآن الكريم.');
});

it('formats one-sided and absent level age conditions on public registration', function () {
    $this->withoutVite();
    [$owner, $competition] = termsAgeCompetition();
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'حد أدنى', 'memorization_amount' => 'جزء', 'min_age' => 8, 'max_age' => null, 'total_score' => 100, 'passing_score' => 50]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'حد أقصى', 'memorization_amount' => 'جزءان', 'min_age' => null, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'مفتوح', 'memorization_amount' => 'ثلاثة أجزاء', 'min_age' => null, 'max_age' => null, 'total_score' => 100, 'passing_score' => 50]);

    $this->get(route('competitions.public-register-canonical', [$owner->username, 1]))
        ->assertOk()
        ->assertSee('من 8 سنوات فأكثر')
        ->assertSee('حتى 15 سنة')
        ->assertSee('لا يوجد شرط عمر محدد');
});

it('accepts a student at an exact configured age and rejects an ineligible age', function () {
    [$owner, $competition] = termsAgeCompetition();
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'خمسة أجزاء', 'memorization_amount' => '5 أجزاء', 'min_age' => 10, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);
    $base = ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => 'محمد أحمد علي', 'gender' => 'Male', 'phone' => '01012345678', 'parent_phone' => '01112345678', 'governorate' => 'القاهرة'];

    $this->from(route('registrations.create'))->post(route('registrations.store'), $base + ['birth_date' => Carbon::today()->subYears(10)->toDateString()])->assertRedirect(route('registrations.success'));
    $this->from(route('registrations.create'))->post(route('registrations.store'), $base + ['birth_date' => Carbon::today()->subYears(9)->toDateString()])->assertSessionHasErrors('birth_date');
});

it('allows levels with null age bounds without imposing an age restriction', function () {
    [$owner, $competition] = termsAgeCompetition();
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'مفتوح', 'memorization_amount' => 'متاح', 'min_age' => null, 'max_age' => null, 'total_score' => 100, 'passing_score' => 50]);
    $this->post(route('registrations.store'), ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => 'علي حسن محمود', 'birth_date' => '1990-01-01', 'gender' => 'Male', 'phone' => '01212345678', 'parent_phone' => '01512345678'])->assertRedirect(route('registrations.success'));
});

it('rejects one-sided age rules during level creation', function () {
    [$owner, $competition] = termsAgeCompetition();
    $payload = ['competition_id' => $competition->id, 'name' => 'مستوى غير مكتمل', 'memorization_amount' => 'جزء', 'total_score' => 100, 'passing_score' => 50];

    $this->actingAs($owner)->post(route('competition-branches.store'), $payload + ['min_age' => 16])->assertSessionHasErrors('max_age');
    $this->actingAs($owner)->post(route('competition-branches.store'), $payload + ['max_age' => 10])->assertSessionHasErrors('min_age');
});

it('uses the exam date for completed-age eligibility and accepts the exact maximum', function () {
    [, $competition] = termsAgeCompetition();
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'عمر الاختبار', 'memorization_amount' => 'جزء', 'min_age' => 15, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);
    $birthDate = Carbon::today()->subYears(15);
    $payload = ['competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => 'حسن أحمد علي', 'birth_date' => $birthDate->toDateString(), 'gender' => 'Male', 'phone' => '01212345678', 'parent_phone' => '01512345678'];

    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
});

it('enforces the complete 11 to 15 age boundary using the exam date', function () {
    [, $competition] = termsAgeCompetition(['exam_start_date' => Carbon::create(2026, 10, 12)]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'حدود العمر', 'memorization_amount' => 'جزء', 'min_age' => 11, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);

    $base = fn (string $name, string $phone, string $birthDate) => [
        'competition_id' => $competition->id, 'branch_id' => $branch->id, 'full_name' => $name,
        'birth_date' => $birthDate, 'gender' => 'Male', 'phone' => $phone, 'parent_phone' => '011'.substr($phone, 3),
    ];

    $this->post(route('registrations.store'), $base('طالب عمر عشرة', '01011111111', '2015-10-13'))->assertSessionHasErrors('birth_date');
    $this->post(route('registrations.store'), $base('طالب عمر أحد عشر', '01011111112', '2015-10-12'))->assertRedirect(route('registrations.success'));
    $this->post(route('registrations.store'), $base('طالب عمر خمسة عشر', '01011111113', '2011-10-12'))->assertRedirect(route('registrations.success'));
    $this->post(route('registrations.store'), $base('طالب عمر ستة عشر', '01011111114', '2010-10-12'))->assertSessionHasErrors('birth_date');
});

it('rejects a crafted age-ineligible branch id on the server', function () {
    [, $competition] = termsAgeCompetition(['exam_start_date' => Carbon::create(2026, 10, 12)]);
    $eligibleBranch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى المناسب', 'memorization_amount' => 'جزء', 'min_age' => 11, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);
    $ineligibleBranch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى غير المناسب', 'memorization_amount' => 'جزء', 'min_age' => 16, 'max_age' => 20, 'total_score' => 100, 'passing_score' => 50]);
    $payload = ['competition_id' => $competition->id, 'branch_id' => $eligibleBranch->id, 'full_name' => 'طالب طلب معدل', 'birth_date' => '2015-10-12', 'gender' => 'Male', 'phone' => '01022222222', 'parent_phone' => '01122222222'];

    $this->post(route('registrations.store'), $payload)->assertRedirect(route('registrations.success'));
    $this->post(route('registrations.store'), [...$payload, 'branch_id' => $ineligibleBranch->id, 'phone' => '01022222223', 'parent_phone' => '01122222223'])
        ->assertSessionHasErrors('birth_date');
});

it('rejects reversed age bounds and permits a legacy branch to be edited', function () {
    [$owner, $competition] = termsAgeCompetition();
    $payload = ['competition_id' => $competition->id, 'name' => 'نطاق معكوس', 'memorization_amount' => 'جزء', 'total_score' => 100, 'passing_score' => 50];
    $this->actingAs($owner)->post(route('competition-branches.store'), $payload + ['min_age' => 15, 'max_age' => 10])
        ->assertSessionHasErrors('max_age');

    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'مستوى تاريخي', 'memorization_amount' => 'جزء', 'min_age' => 10, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50]);
    $this->actingAs($owner)->get(route('competition-branches.legacy.edit', $branch))->assertOk()->assertSee('الحد الأدنى للعمر');
    $this->actingAs($owner)->put(route('competition-branches.legacy.update', $branch), [
        'competition_id' => $competition->id, 'name' => $branch->name, 'memorization_amount' => $branch->memorization_amount,
        'min_age' => 11, 'max_age' => 15, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect(route('competition-branches.show', $branch));
    expect($branch->fresh()->min_age)->toBe(11);
});
