<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use App\Models\User;

function reusableLevelCompetition(User $owner): Competition
{
    return Competition::create([
        'title' => 'مسابقة المستويات', 'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2),
        'exam_end_date' => now()->addDays(3), 'location' => 'المركز',
        'status' => 'Open for Registration', 'created_by' => $owner->id,
    ]);
}

function reusableLevelPayload(array $overrides = []): array
{
    return array_replace([
        'reusable_level' => 1,
        'name' => 'مستوى مستقل',
        'description' => 'مستوى قابل لإعادة الاستخدام',
        'memorization_amount' => 'خمسة أجزاء',
        'default_min_age' => 8,
        'default_max_age' => 15,
        'default_total_score' => 100,
        'default_passing_score' => 60,
    ], $overrides);
}

test('organizer canonical levels index shows only owned defaults and reusable levels with one sidebar entry', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $other = User::factory()->create(['role' => 'User', 'status' => 'active']);
    CompetitionLevel::create(['name' => 'خمسة أجزاء', 'memorization_amount' => 'خمسة أجزاء', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active']);
    CompetitionLevel::create(['name' => 'مستوى المالك', 'memorization_amount' => 'مخصص', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active']);
    CompetitionLevel::create(['name' => 'مستوى أجنبي', 'memorization_amount' => 'مخصص', 'type' => 'organizer', 'created_by' => $other->id, 'status' => 'active']);

    $response = $this->actingAs($owner)->get(route('competition-branches.index'));

    $response->assertOk()->assertSee('مستويات مسابقاتي')->assertSee('خمسة أجزاء')
        ->assertSee('مستوى المالك')->assertDontSee('مستوى أجنبي')->assertSee('تعديل')->assertSee('حذف')
        ->assertSee('href="'.route('competition-branches.index').'"', false);
    // The responsive layout renders the same single navigation entry once in each
    // sidebar variant (desktop and mobile), without a second levels destination.
    expect(substr_count($response->getContent(), 'aria-label="مستويات مسابقاتي"'))->toBe(2);
});

test('canonical levels table exposes defaults and usage without archive actions', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = reusableLevelCompetition($owner);
    $used = CompetitionLevel::create([
        'name' => 'مستوى مستخدم ظاهر', 'memorization_amount' => 'خمسة أجزاء', 'description' => 'وصف مختصر',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
        'default_min_age' => 8, 'default_max_age' => 15, 'default_total_score' => 100, 'default_passing_score' => 60,
    ]);
    $unused = CompetitionLevel::create([
        'name' => 'مستوى غير مستخدم ظاهر', 'memorization_amount' => 'جزء واحد', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
    ]);
    CompetitionLevelAssignment::create(['competition_id' => $competition->id, 'competition_level_id' => $used->id, 'min_age' => 10, 'max_age' => 17, 'total_score' => 100, 'passing_score' => 65]);

    $response = $this->actingAs($owner)->get(route('competition-branches.index'));
    $response->assertOk()->assertSee('العمر الافتراضي')->assertSee('الدرجات')->assertSee('الاستخدام')
        ->assertSee('8 – 15 سنة')->assertSee('الكلية:')->assertSee('النجاح:')
        ->assertSee('مستخدم في 1 مسابقة')->assertSee('غير مستخدم')
        ->assertSee('وصف مختصر')->assertSee('تعديل')->assertSee('حذف')->assertDontSee('أرشفة')->assertDontSee('استعادة');
    expect(substr_count($response->getContent(), 'hafez-levels-table'))->toBeGreaterThan(0);
});

test('organizer can create, edit, and delete an unused reusable level without an assignment or compatibility branch', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);

    $this->actingAs($owner)->get(route('competition-branches.create'))
        ->assertOk()->assertSee('إنشاء مستوى جديد')->assertSee('اسم المستوى')
        ->assertSee('مقدار الحفظ')->assertSee('الحد الأدنى للعمر')
        ->assertSee('الحد الأقصى للعمر')->assertSee('الدرجة الكلية')->assertSee('درجة النجاح');

    $this->post(route('competition-branches.store'), reusableLevelPayload())
        ->assertRedirect(route('competition-branches.index'));
    $level = CompetitionLevel::where('name', 'مستوى مستقل')->firstOrFail();
    expect($level->only(['type', 'created_by', 'status', 'default_min_age', 'default_max_age', 'default_total_score', 'default_passing_score']))->toMatchArray([
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
        'default_min_age' => 8, 'default_max_age' => 15, 'default_total_score' => '100.00', 'default_passing_score' => '60.00',
    ])->and(CompetitionLevelAssignment::where('competition_level_id', $level->id)->exists())->toBeFalse()
        ->and(CompetitionBranch::where('competition_level_id', $level->id)->exists())->toBeFalse();

    $this->put(route('competition-branches.update', $level), reusableLevelPayload([
        'name' => 'المستوى المعدل', 'reusable_level' => null,
    ]))->assertRedirect(route('competition-branches.index'));
    expect($level->fresh()->name)->toBe('المستوى المعدل');

    $this->delete(route('competition-branches.destroy', $level))->assertRedirect();
    expect(CompetitionLevel::find($level->id))->toBeNull();
});

test('reusable default settings validate as optional complete age and score pairs', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $this->actingAs($owner);

    $this->from(route('competition-branches.create'))->post(route('competition-branches.store'), reusableLevelPayload([
        'default_min_age' => 16, 'default_max_age' => 10,
    ]))->assertRedirect()->assertSessionHasErrors('default_max_age');
    $this->from(route('competition-branches.create'))->post(route('competition-branches.store'), reusableLevelPayload([
        'default_total_score' => 60, 'default_passing_score' => 70,
    ]))->assertRedirect()->assertSessionHasErrors('default_passing_score');
    $this->post(route('competition-branches.store'), reusableLevelPayload([
        'name' => 'مستوى بدون قيم افتراضية',
        'default_min_age' => null, 'default_max_age' => null,
        'default_total_score' => null, 'default_passing_score' => null,
    ]))->assertRedirect(route('competition-branches.index'));

    $level = CompetitionLevel::where('name', 'مستوى بدون قيم افتراضية')->firstOrFail();
    expect($level->default_min_age)->toBeNull()->and($level->default_max_age)->toBeNull()
        ->and($level->default_total_score)->toBeNull()->and($level->default_passing_score)->toBeNull();
});

test('foreign owners cannot manage levels and used organizer levels archive then restore through canonical actions', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $other = User::factory()->create(['role' => 'User', 'status' => 'active']);
    $competition = reusableLevelCompetition($owner);
    $level = CompetitionLevel::create(['name' => 'مستوى مستخدم', 'memorization_amount' => 'عشرة أجزاء', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active']);
    CompetitionLevelAssignment::create(['competition_id' => $competition->id, 'competition_level_id' => $level->id, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'competition_level_id' => $level->id, 'name' => $level->name, 'memorization_amount' => $level->memorization_amount, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    $this->actingAs($other)->get(route('competition-branches.edit', $level))->assertForbidden();
    $this->delete(route('competition-branches.destroy', $level))->assertForbidden();

    $this->actingAs($owner)->delete(route('competition-branches.destroy', $level))->assertRedirect();
    expect($level->fresh()->status)->toBe('archived')
        ->and(CompetitionBranch::where('competition_level_id', $level->id)->exists())->toBeTrue();
    $this->post(route('competition-branches.restore', $level))->assertRedirect();
    expect($level->fresh()->status)->toBe('active');
});

test('legacy competition levels URL redirects to canonical reusable levels page', function () {
    $owner = User::factory()->create(['role' => 'User', 'status' => 'active']);

    $this->actingAs($owner)->get('/competition-levels')
        ->assertRedirect(route('competition-branches.index'));
});
