<?php

use App\Models\Committee;
use App\Models\CommitteeStudent;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;

function bulkEvaluationFixture(int $students = 55): array
{
    $owner = User::factory()->create(['role' => 'User']);
    $judge = User::factory()->create(['role' => 'User']);
    $other = User::factory()->create(['role' => 'User']);
    $competition = Competition::create([
        'title' => 'مسابقة الإدخال الجماعي', 'created_by' => $owner->id,
        'competition_number' => 1, 'status' => 'active', 'publication_scope' => 'unlisted',
        'location' => 'المقر الرئيسي',
        'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3),
    ]);
    $branch = CompetitionBranch::create([
        'competition_id' => $competition->id, 'name' => 'المستوى الأساسي',
        'memorization_amount' => 1, 'min_age' => 7, 'max_age' => 18,
        'total_score' => 100, 'passing_score' => 60,
    ]);
    $committee = Committee::create([
        'competition_id' => $competition->id, 'branch_id' => $branch->id,
        'name' => 'لجنة الإدخال', 'exam_date' => now()->addDays(2), 'location' => 'المقر',
    ]);
    $committee->committeeJudges()->create(['judge_id' => $judge->id]);
    for ($i = 0; $i < $students; $i++) {
        $student = Student::create(['full_name' => "طالب اختبار {$i} ثلاثي", 'birth_date' => '2010-01-01', 'gender' => 'male', 'phone' => '010'.str_pad((string) ($i + 10000000), 8, '0', STR_PAD_LEFT), 'parent_phone' => '011'.str_pad((string) ($i + 10000000), 8, '0', STR_PAD_LEFT), 'address' => 'عنوان اختباري', 'city' => 'القاهرة', 'center_name' => 'مركز الاختبار']);
        $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
        CommitteeStudent::create(['committee_id' => $committee->id, 'student_id' => $student->id, 'registration_id' => $registration->id]);
    }
    return compact('owner', 'judge', 'other', 'competition', 'branch', 'committee');
}

test('bulk evaluation entry is available to owner and assigned judge but not unrelated users', function () {
    $fixture = bulkEvaluationFixture(1);
    $admin = User::factory()->create(['role' => 'Platform Admin']);
    $this->actingAs($admin)->get(route('committees.evaluations.bulk', $fixture['committee']))->assertOk();
    $this->actingAs($fixture['owner'])->get(route('committees.evaluations.bulk', $fixture['committee']))->assertOk();
    $this->actingAs($fixture['judge'])->get(route('committees.evaluations.bulk', $fixture['committee']))->assertOk();
    $this->actingAs($fixture['other'])->get(route('committees.evaluations.bulk', $fixture['committee']))->assertForbidden();
});

test('bulk evaluation entry paginates fifty rows and preserves filters', function () {
    $fixture = bulkEvaluationFixture();
    $response = $this->actingAs($fixture['judge'])->get(route('committees.evaluations.bulk', [$fixture['committee'], 'search' => 'طالب اختبار', 'page' => 2]));
    $response->assertOk()
        ->assertSee('rows[51][registration_id]', false)
        ->assertSee('hafez-pagination', false)
        ->assertSee('عرض 51 إلى 55 من أصل 55 نتيجة')
        ->assertSee('aria-label="التنقل بين الصفحات"', false);
    expect($response->viewData('rows')->perPage())->toBe(50)
        ->and($response->viewData('rows')->total())->toBe(55)
        ->and($response->viewData('rows')->count())->toBe(5);
});

test('bulk evaluation saves complete rows and updates the same evaluator without duplicates', function () {
    $fixture = bulkEvaluationFixture(2);
    $registrations = Registration::query()->pluck('id');
    $payload = ['rows' => $registrations->mapWithKeys(fn ($id) => [$id => ['registration_id' => $id, 'scores' => [['score' => 40], ['score' => 20], ['score' => 20]]]])->all()];
    $this->actingAs($fixture['judge'])->post(route('committees.evaluations.bulk.store', $fixture['committee']), $payload)->assertRedirect();
    expect(Evaluation::where('judge_id', $fixture['judge']->id)->count())->toBe(2);
    $this->actingAs($fixture['judge'])->post(route('committees.evaluations.bulk.store', $fixture['committee']), $payload['rows'] ? $payload : [])->assertRedirect();
    expect(Evaluation::where('judge_id', $fixture['judge']->id)->count())->toBe(2);
});

test('bulk evaluation maps reordered registration ids to the exact students and updates only the selected row', function () {
    $fixture = bulkEvaluationFixture(3);
    $registrations = Registration::query()->where('competition_id', $fixture['competition']->id)->orderByDesc('id')->get();

    $payload = ['rows' => [
        $registrations[0]->id => ['registration_id' => $registrations[0]->id, 'scores' => [['score' => 33], ['score' => 20], ['score' => 20]]],
        $registrations[2]->id => ['registration_id' => $registrations[2]->id, 'scores' => [['score' => 11], ['score' => 20], ['score' => 20]]],
        $registrations[1]->id => ['registration_id' => $registrations[1]->id, 'scores' => [['score' => 22], ['score' => 20], ['score' => 20]]],
    ]];

    $this->actingAs($fixture['judge'])->post(route('committees.evaluations.bulk.store', $fixture['committee']), $payload)->assertRedirect();

    $scores = Evaluation::where('judge_id', $fixture['judge']->id)->pluck('memorization_score', 'registration_id');
    expect((float) $scores[$registrations[0]->id])->toBe(33.0)
        ->and((float) $scores[$registrations[1]->id])->toBe(22.0)
        ->and((float) $scores[$registrations[2]->id])->toBe(11.0);

    $update = ['rows' => [
        $registrations[1]->id => ['registration_id' => $registrations[1]->id, 'scores' => [['score' => 44], ['score' => 20], ['score' => 20]]],
    ]];
    $this->actingAs($fixture['judge'])->post(route('committees.evaluations.bulk.store', $fixture['committee']), $update)->assertRedirect();

    expect((float) Evaluation::where('judge_id', $fixture['judge']->id)->where('registration_id', $registrations[0]->id)->value('memorization_score'))->toBe(33.0)
        ->and((float) Evaluation::where('judge_id', $fixture['judge']->id)->where('registration_id', $registrations[1]->id)->value('memorization_score'))->toBe(44.0)
        ->and((float) Evaluation::where('judge_id', $fixture['judge']->id)->where('registration_id', $registrations[2]->id)->value('memorization_score'))->toBe(11.0)
        ->and(Evaluation::where('judge_id', $fixture['judge']->id)->count())->toBe(3);
});

test('bulk evaluation rejects partially completed rows atomically', function () {
    $fixture = bulkEvaluationFixture(2);
    $registrations = Registration::query()->pluck('id');
    $response = $this->actingAs($fixture['judge'])->from(route('committees.evaluations.bulk', $fixture['committee']))
        ->post(route('committees.evaluations.bulk.store', $fixture['committee']), ['rows' => [
            ['registration_id' => $registrations[0], 'scores' => [['score' => 40], ['score' => 20], ['score' => 20]]],
            ['registration_id' => $registrations[1], 'scores' => [['score' => 40], ['score' => 20], ['score' => '']]],
        ]]);
    $response->assertRedirect()->assertSessionHasErrors();
    expect(Evaluation::count())->toBe(0);
});

test('bulk evaluation attaches Arabic maximum error to the submitted registration row', function () {
    $fixture = bulkEvaluationFixture(1);
    $registration = Registration::query()->where('competition_id', $fixture['competition']->id)->first();

    $response = $this->actingAs($fixture['judge'])->from(route('committees.evaluations.bulk', $fixture['committee']))
        ->post(route('committees.evaluations.bulk.store', $fixture['committee']), ['rows' => [
            $registration->id => ['registration_id' => $registration->id, 'scores' => [['score' => 60], ['score' => 20], ['score' => 20]]],
        ]]);

    $response->assertRedirect()
        ->assertSessionHasErrors(["rows.{$registration->id}.scores.0.score" => 'لا يمكن أن تتجاوز درجة الحفظ 50.']);
});

test('autosave creates and updates one evaluation by stable registration id', function () {
    $fixture = bulkEvaluationFixture(2);
    $registration = Registration::query()->where('competition_id', $fixture['competition']->id)->first();
    $url = route('committees.evaluations.autosave', [$fixture['committee'], $registration]);

    $this->actingAs($fixture['judge'])->patchJson($url, [
        'registration_id' => $registration->id,
        'scores' => [['score' => 40], ['score' => 20], ['score' => 20]],
    ])->assertOk()->assertJsonPath('message', 'تم الحفظ');
    expect(Evaluation::where('judge_id', $fixture['judge']->id)->count())->toBe(1)
        ->and((float) Evaluation::where('registration_id', $registration->id)->value('memorization_score'))->toBe(40.0);

    $this->actingAs($fixture['judge'])->patchJson($url, [
        'registration_id' => $registration->id,
        'scores' => [['score' => 45], ['score' => 20], ['score' => 20]],
    ])->assertOk();
    expect(Evaluation::where('judge_id', $fixture['judge']->id)->count())->toBe(1)
        ->and((float) Evaluation::where('registration_id', $registration->id)->value('memorization_score'))->toBe(45.0);
});

test('autosave enforces evaluator access and score validation', function () {
    $fixture = bulkEvaluationFixture(1);
    $registration = Registration::query()->where('competition_id', $fixture['competition']->id)->first();
    $url = route('committees.evaluations.autosave', [$fixture['committee'], $registration]);

    $this->actingAs($fixture['other'])->patchJson($url, ['registration_id' => $registration->id, 'scores' => [['score' => 40], ['score' => 20], ['score' => 20]]])->assertForbidden();
    $this->actingAs($fixture['judge'])->patchJson($url, ['registration_id' => $registration->id, 'scores' => [['score' => 60], ['score' => 20], ['score' => 20]]])
        ->assertStatus(422)->assertJsonValidationErrors('scores.0.score');
    expect(Evaluation::count())->toBe(0);
});

test('autosave rejects every score above its configured maximum and negative values without writing', function () {
    $fixture = bulkEvaluationFixture(1);
    $registration = Registration::query()->where('competition_id', $fixture['competition']->id)->first();
    $url = route('committees.evaluations.autosave', [$fixture['committee'], $registration]);
    foreach ([[60, 20, 20], [40, 26, 20], [40, 20, 26], [-1, 20, 20]] as $scores) {
        $response = $this->actingAs($fixture['judge'])->patchJson($url, ['registration_id' => $registration->id, 'scores' => array_map(fn ($score) => ['score' => $score], $scores)]);
        $response->assertStatus(422)->assertJsonStructure(['errors']);
    }
    expect(Evaluation::count())->toBe(0);
});

test('autosave accepts inclusive score boundaries and invalid edits preserve stored values', function () {
    $fixture = bulkEvaluationFixture(1);
    $registration = Registration::query()->where('competition_id', $fixture['competition']->id)->first();
    $url = route('committees.evaluations.autosave', [$fixture['committee'], $registration]);
    $this->actingAs($fixture['judge'])->patchJson($url, ['registration_id' => $registration->id, 'scores' => [['score' => 50], ['score' => 25], ['score' => 25]]])->assertOk();
    $this->actingAs($fixture['judge'])->patchJson($url, ['registration_id' => $registration->id, 'scores' => [['score' => 450], ['score' => 25], ['score' => 25]]])->assertStatus(422);
    expect(Evaluation::count())->toBe(1)->and((float) Evaluation::first()->memorization_score)->toBe(50.0);
});
