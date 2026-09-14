<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use App\Models\Committee;
use App\Models\CommitteeJudge;
use App\Models\CommitteeStudent;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\CompetitionScoringRulesService;
use App\Services\ResultCalculationService;

function scoringCompetitionPayload(array $overrides = []): array
{
    return array_replace([
        'title' => 'مسابقة معايير التقييم',
        'registration_start_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'registration_end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'exam_start_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'exam_end_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
        'location' => 'المركز الرئيسي',
        'publication_scope' => 'unlisted',
        'custom_scoring' => '1',
        'scoring_criteria' => app(CompetitionScoringRulesService::class)->defaultCriteria(),
    ], $overrides);
}

test('official scoring defaults are applied when a competition has no custom criteria', function () {
    $criteria = app(CompetitionScoringRulesService::class)->effectiveCriteria(null);

    expect($criteria)->toBe([
        ['name' => 'الحفظ', 'max_score' => 50],
        ['name' => 'التجويد', 'max_score' => 25],
        ['name' => 'الأداء', 'max_score' => 25],
    ])->and(app(CompetitionScoringRulesService::class)->effectiveMaximumScore(null))->toBe(100.0);
});

test('organizer can create a competition with the editable official scoring template', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->post(route('competitions.store'), scoringCompetitionPayload())
        ->assertRedirect();

    $competition = Competition::firstOrFail();
    expect($competition->rules['scoring_criteria'])->toBe([
        ['name' => 'الحفظ', 'max_score' => 50],
        ['name' => 'التجويد', 'max_score' => 25],
        ['name' => 'الأداء', 'max_score' => 25],
    ]);
});

test('organizer can add, modify, and remove scoring criteria while updating a competition', function () {
    $owner = User::factory()->create();
    $competition = Competition::create(array_merge(scoringCompetitionPayload(), [
        'created_by' => $owner->id,
        'competition_number' => 1,
        'status' => 'Draft',
        'rules' => ['scoring_criteria' => [
            ['name' => 'الحفظ', 'max_score' => 50],
            ['name' => 'التجويد', 'max_score' => 30],
            ['name' => 'الأداء', 'max_score' => 20],
        ]],
    ]));

    $this->actingAs($owner)->put(route('competitions.update', $competition), scoringCompetitionPayload([
        'title' => $competition->title,
        'scoring_criteria' => [
            ['name' => 'الحفظ المتقن', 'max_score' => 60],
            ['name' => 'التجويد', 'max_score' => 25],
            ['name' => 'الانضباط', 'max_score' => 15],
        ],
    ]))->assertRedirect(route('competitions.show', $competition));

    expect($competition->fresh()->rules['scoring_criteria'])->toBe([
        ['name' => 'الحفظ المتقن', 'max_score' => 60],
        ['name' => 'التجويد', 'max_score' => 25],
        ['name' => 'الانضباط', 'max_score' => 15],
    ]);
});

test('custom scoring criteria require non-empty unique names and positive numeric scores', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->post(route('competitions.store'), scoringCompetitionPayload([
        'scoring_criteria' => [
            ['name' => '', 'max_score' => 0],
            ['name' => 'الحفظ', 'max_score' => 'abc'],
            ['name' => 'الحفظ', 'max_score' => 20],
        ],
    ]))->assertSessionHasErrors([
        'scoring_criteria.0.name',
        'scoring_criteria.0.max_score',
        'scoring_criteria.1.max_score',
        'scoring_criteria.2.name',
    ]);
});

test('public competition registration shows its saved scoring criteria before application', function () {
    $owner = User::factory()->create(['username' => 'scoring-owner']);
    $competition = Competition::create(array_merge(scoringCompetitionPayload([
        'registration_start_date' => now()->subDay()->format('Y-m-d H:i:s'),
        'registration_end_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'exam_start_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'exam_end_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'scoring_criteria' => [
            ['name' => 'الحفظ', 'max_score' => 50],
            ['name' => 'التجويد', 'max_score' => 30],
            ['name' => 'الأداء', 'max_score' => 20],
        ],
    ]), [
        'created_by' => $owner->id,
        'competition_number' => 1,
        'status' => 'Open for Registration',
        'rules' => ['scoring_criteria' => [
            ['name' => 'الحفظ', 'max_score' => 50],
            ['name' => 'التجويد', 'max_score' => 30],
            ['name' => 'الأداء', 'max_score' => 20],
        ]],
    ]));
    CompetitionBranch::create([
        'competition_id' => $competition->id,
        'name' => 'المستوى الأول',
        'memorization_amount' => 'خمسة أجزاء',
        'min_age' => 8,
        'max_age' => 18,
        'total_score' => 100,
        'passing_score' => 50,
    ]);

    $this->get(route('competitions.public-register-canonical', [$owner->username, $competition->competition_number]))
        ->assertOk()
        ->assertSee('معايير التقييم')
        ->assertSee('الحفظ')
        ->assertSee('50')
        ->assertSee('درجة')
        ->assertSee('إجمالي الدرجات:');
});

function scoringEvaluationFixture(array $rules = [], int $totalScore = 100): array
{
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $judge = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = Competition::create(array_merge(scoringCompetitionPayload(), [
        'created_by' => $owner->id,
        'competition_number' => 1,
        'status' => 'Open for Registration',
        'rules' => $rules,
    ]));
    $branch = CompetitionBranch::create([
        'competition_id' => $competition->id, 'name' => 'مستوى التقييم',
        'memorization_amount' => 'خمسة أجزاء', 'min_age' => 8, 'max_age' => 18,
        'total_score' => $totalScore, 'passing_score' => 50,
    ]);
    $student = Student::create([
        'full_name' => 'طالب التقييم', 'birth_date' => now()->subYears(14)->toDateString(),
        'gender' => 'Male', 'phone' => '01012345678', 'parent_phone' => '01112345678',
        'address' => 'العنوان', 'city' => 'Cairo', 'center_name' => 'المركز',
    ]);
    $registration = Registration::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now(),
    ]);
    $committee = Committee::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'name' => 'لجنة التقييم', 'exam_date' => now(), 'location' => 'القاعة',
    ]);
    CommitteeJudge::create(['committee_id' => $committee->id, 'judge_id' => $judge->id]);
    CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);

    return compact('competition', 'branch', 'student', 'registration', 'judge');
}

test('judge submission uses default criteria, validates their maximums, and saves a score snapshot', function () {
    ['competition' => $competition, 'branch' => $branch, 'student' => $student, 'registration' => $registration, 'judge' => $judge] = scoringEvaluationFixture();
    $payload = [
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'judge_id' => $judge->id,
        'scores' => [['score' => 45], ['score' => 20], ['score' => 22]],
    ];

    $this->actingAs($judge)->post(route('evaluations.store'), $payload)->assertRedirect();

    $evaluation = Evaluation::firstOrFail();
    expect($evaluation->scores)->toBe([
        ['name' => 'الحفظ', 'score' => 45, 'max_score' => 50],
        ['name' => 'التجويد', 'score' => 20, 'max_score' => 25],
        ['name' => 'الأداء', 'score' => 22, 'max_score' => 25],
    ])->and((float) $evaluation->total_score)->toBe(87.0)
        ->and((float) $evaluation->percentage)->toBe(87.0);

    $invalid = $payload;
    $invalid['scores'][0]['score'] = 51;
    $invalid['registration_id'] = $registration->id;
    $this->actingAs($judge)->post(route('evaluations.store'), $invalid)
        ->assertSessionHasErrors('scores.0.score');
});

test('custom criteria drive judge validation and result percentages', function () {
    $rules = ['scoring_criteria' => [
        ['name' => 'الإتقان', 'max_score' => 70],
        ['name' => 'الأداء الصوتي', 'max_score' => 30],
    ]];
    ['competition' => $competition, 'branch' => $branch, 'student' => $student, 'registration' => $registration, 'judge' => $judge] = scoringEvaluationFixture($rules);
    $payload = [
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'judge_id' => $judge->id,
        'scores' => [['score' => 60], ['score' => 25]],
    ];

    $this->actingAs($judge)->post(route('evaluations.store'), $payload)->assertRedirect();
    $evaluation = Evaluation::firstOrFail();
    expect($evaluation->scoreItems()[0]['name'])->toBe('الإتقان')
        ->and((float) $evaluation->total_score)->toBe(85.0)
        ->and((float) $evaluation->percentage)->toBe(85.0);

    app(ResultCalculationService::class)->generate($competition, $branch->id);
    expect((float) $registration->fresh()->result->percentage)->toBe(85.0);
});

test('scoring structure cannot change after an evaluation exists', function () {
    ['competition' => $competition, 'branch' => $branch, 'student' => $student, 'registration' => $registration, 'judge' => $judge] = scoringEvaluationFixture();
    Evaluation::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id,
        'registration_id' => $registration->id, 'judge_id' => $judge->id,
        'memorization_score' => 45, 'tajweed_score' => 20, 'performance_score' => 20, 'discipline_score' => 0,
        'total_score' => 85, 'percentage' => 85, 'status' => 'submitted',
    ]);

    $this->actingAs($competition->creator)->put(route('competitions.update', $competition), scoringCompetitionPayload([
        'title' => $competition->title,
        'scoring_criteria' => [['name' => 'معيار جديد', 'max_score' => 100]],
    ]))->assertSessionHasErrors('scoring_criteria');
});

test('assigned total score scales evaluation criteria and result percentages', function () {
    ['competition' => $competition, 'branch' => $branch, 'student' => $student, 'registration' => $registration, 'judge' => $judge] = scoringEvaluationFixture([], 120);

    $payload = [
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'student_id' => $student->id, 'registration_id' => $registration->id,
        'judge_id' => $judge->id,
        'scores' => [['score' => 60], ['score' => 30], ['score' => 30]],
    ];

    $this->actingAs($judge)->post(route('evaluations.store'), $payload)->assertRedirect();
    $evaluation = Evaluation::firstOrFail();
    expect($evaluation->scoreItems())->toMatchArray([
        ['name' => 'الحفظ', 'score' => 60, 'max_score' => 60],
        ['name' => 'التجويد', 'score' => 30, 'max_score' => 30],
        ['name' => 'الأداء', 'score' => 30, 'max_score' => 30],
    ])->and((float) $evaluation->percentage)->toBe(100.0);

    app(ResultCalculationService::class)->generate($competition, $branch->id);
    expect((float) $registration->fresh()->result->percentage)->toBe(100.0);
});

test('result success boundary uses the assigned compatibility branch passing score', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = Competition::create(array_merge(scoringCompetitionPayload(), [
        'created_by' => $owner->id, 'competition_number' => 1, 'status' => 'Open for Registration', 'rules' => null,
    ]));
    $level = CompetitionLevel::create([
        'name' => 'مستوى نتيجة التعيين', 'memorization_amount' => 'ثلاثة أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
        'default_total_score' => 100, 'default_passing_score' => 60,
    ]);
    $assignment = CompetitionLevelAssignment::create([
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 17, 'total_score' => 100, 'passing_score' => 65,
    ]);
    $branch = CompetitionBranch::create([
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'name' => $level->name, 'memorization_amount' => $level->memorization_amount,
        'min_age' => 10, 'max_age' => 17, 'total_score' => 100, 'passing_score' => 65,
    ]);

    foreach ([64, 65, 80] as $index => $score) {
        $student = Student::create([
            'full_name' => "طالب نتيجة {$index}", 'birth_date' => now()->subYears(14)->toDateString(),
            'gender' => 'Male', 'phone' => '010'.str_pad((string) (30000000 + $index), 8, '0', STR_PAD_LEFT),
            'parent_phone' => '011'.str_pad((string) (30000000 + $index), 8, '0', STR_PAD_LEFT),
            'address' => 'العنوان', 'city' => 'Cairo', 'center_name' => 'المركز',
        ]);
        $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
        Evaluation::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'judge_id' => $owner->id, 'memorization_score' => $score, 'tajweed_score' => 0, 'performance_score' => 0, 'discipline_score' => 0, 'total_score' => $score, 'percentage' => $score, 'status' => 'submitted']);
    }

    app(ResultCalculationService::class)->generate($competition, $branch->id);
    $results = $competition->results()->orderBy('final_score')->get();
    expect((float) $assignment->passing_score)->toBe(65.0)
        ->and((float) $level->default_passing_score)->toBe(60.0)
        ->and($results->pluck('result_status')->all())->toBe(['failed', 'successful', 'successful'])
        ->and($results->pluck('is_winner')->all())->toBe([false, true, true]);
});
