<?php

use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\CompetitionLevelAssignment;
use App\Models\User;

function attachableCompetition(User $owner): Competition
{
    return Competition::create([
        'title' => 'Competition for reusable levels', 'location' => 'Center', 'status' => 'Draft',
        'created_by' => $owner->id, 'registration_start_date' => now()->addDay(),
        'registration_end_date' => now()->addDays(3), 'exam_start_date' => now()->addDays(4),
        'exam_end_date' => now()->addDays(5),
    ]);
}

it('shows only active system and own organizer levels on the dedicated add page', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $other = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($owner);
    $system = CompetitionLevel::create(['name' => 'System Level', 'memorization_amount' => '5 أجزاء', 'type' => 'system', 'status' => 'active']);
    $own = CompetitionLevel::create(['name' => 'My Level', 'memorization_amount' => '10 أجزاء', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active', 'default_min_age' => 8, 'default_max_age' => 15, 'default_total_score' => 100, 'default_passing_score' => 60]);
    CompetitionLevel::create(['name' => 'Other Level', 'memorization_amount' => '20 جزءاً', 'type' => 'organizer', 'created_by' => $other->id, 'status' => 'active']);
    CompetitionLevel::create(['name' => 'Archived Level', 'memorization_amount' => 'جزء عم', 'type' => 'system', 'status' => 'archived']);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertSee('System Level')->assertSee('My Level')
        ->assertSee('data-min-age="8"', false)->assertSee('data-max-age="15"', false)
        ->assertSee('data-total-score="100.00"', false)->assertSee('data-passing-score="60.00"', false)
        ->assertDontSee('Other Level')->assertDontSee('Archived Level');

    expect(route('competitions.levels.add-existing', $competition))->toEndWith("/competitions/{$competition->id}/levels/add");
});

it('prevents opening or attaching a level to another organizers competition', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $other = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($other);
    $level = CompetitionLevel::create(['name' => 'System Level', 'memorization_amount' => '5 أجزاء', 'type' => 'system', 'status' => 'active']);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))->assertForbidden();
    $this->post(route('competitions.levels.store-existing', $competition), ['competition_id' => $competition->id, 'competition_level_id' => $level->id, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50])->assertForbidden();
});

it('rejects invalid assignment settings without creating an assignment or compatibility branch', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($owner);
    $level = CompetitionLevel::create(['name' => 'مستوى تحقق التعيين', 'memorization_amount' => 'ثلاثة أجزاء', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active']);

    $this->actingAs($owner)->from(route('competitions.levels.add-existing', $competition))->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'min_age' => 15, 'max_age' => 10, 'total_score' => 100, 'passing_score' => 120,
    ])->assertRedirect()->assertSessionHasErrors(['max_age', 'passing_score']);

    expect(CompetitionLevelAssignment::where('competition_id', $competition->id)->where('competition_level_id', $level->id)->exists())->toBeFalse()
        ->and(CompetitionBranch::where('competition_id', $competition->id)->where('competition_level_id', $level->id)->exists())->toBeFalse();
});

it('attaches an existing level through the sync layer without changing its identity', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($owner);
    $level = CompetitionLevel::create(['name' => 'Reusable Level', 'description' => 'Identity description', 'memorization_amount' => '10 أجزاء', 'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active']);

    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'min_age' => 9, 'max_age' => 17, 'total_score' => 120, 'passing_score' => 70,
    ])->assertRedirect(route('competition-branches.index', ['competition_id' => $competition->id]));

    $assignment = CompetitionLevelAssignment::query()->where('competition_id', $competition->id)->where('competition_level_id', $level->id)->firstOrFail();
    $branch = CompetitionBranch::query()->where('competition_id', $competition->id)->where('competition_level_id', $level->id)->firstOrFail();
    expect($assignment->only(['min_age', 'max_age', 'total_score', 'passing_score']))->toMatchArray(['min_age' => 9, 'max_age' => 17, 'total_score' => '120.00', 'passing_score' => '70.00']);
    expect($branch->only(['name', 'memorization_amount', 'min_age', 'max_age', 'total_score', 'passing_score']))->toMatchArray(['name' => 'Reusable Level', 'memorization_amount' => '10 أجزاء', 'min_age' => 9, 'max_age' => 17, 'total_score' => '120.00', 'passing_score' => '70.00']);
    expect($level->fresh()->only(['name', 'memorization_amount', 'created_by', 'type']))->toMatchArray(['name' => 'Reusable Level', 'memorization_amount' => '10 أجزاء', 'created_by' => $owner->id, 'type' => 'organizer']);
});

it('uses level defaults only as editable prefills and preserves existing assignment values after default edits', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى بقيم افتراضية', 'memorization_amount' => 'ثلاثة أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
        'default_min_age' => 8, 'default_max_age' => 15,
        'default_total_score' => 100, 'default_passing_score' => 60,
    ]);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))
        ->assertOk()->assertSee('data-min-age="8"', false)->assertSee('data-max-age="15"', false)
        ->assertSee('data-total-score="100.00"', false)->assertSee('data-passing-score="60.00"', false);
    $this->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'min_age' => 9, 'max_age' => 16, 'total_score' => 100, 'passing_score' => 65,
    ])->assertRedirect();

    $assignment = CompetitionLevelAssignment::where('competition_id', $competition->id)->where('competition_level_id', $level->id)->firstOrFail();
    expect($assignment->only(['min_age', 'max_age', 'total_score', 'passing_score']))->toMatchArray([
        'min_age' => 9, 'max_age' => 16, 'total_score' => '100.00', 'passing_score' => '65.00',
    ]);

    $this->put(route('competition-branches.update', $level), [
        'name' => $level->name, 'memorization_amount' => $level->memorization_amount, 'description' => null,
        'default_min_age' => 8, 'default_max_age' => 15, 'default_total_score' => 100, 'default_passing_score' => 70,
    ])->assertRedirect();
    expect($level->fresh()->default_passing_score)->toBe('70.00')
        ->and($assignment->fresh()->passing_score)->toBe('65.00');
});

it('uses edited defaults for a future assignment without changing the first competition', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $competitionA = attachableCompetition($owner);
    $competitionB = attachableCompetition($owner);
    $level = CompetitionLevel::create([
        'name' => 'مستوى إعدادات مستقبلية', 'memorization_amount' => 'ثلاثة أجزاء',
        'type' => 'organizer', 'created_by' => $owner->id, 'status' => 'active',
        'default_min_age' => 8, 'default_max_age' => 15, 'default_total_score' => 100, 'default_passing_score' => 60,
    ]);
    $this->actingAs($owner)->post(route('competitions.levels.store-existing', $competitionA), [
        'competition_id' => $competitionA->id, 'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 17, 'total_score' => 100, 'passing_score' => 65,
    ])->assertRedirect();

    $this->put(route('competition-branches.update', $level), [
        'name' => $level->name, 'memorization_amount' => $level->memorization_amount, 'description' => null,
        'default_min_age' => 12, 'default_max_age' => 18, 'default_total_score' => 120, 'default_passing_score' => 75,
    ])->assertRedirect();
    $this->get(route('competitions.levels.add-existing', $competitionB))
        ->assertSee('data-min-age="12"', false)->assertSee('data-max-age="18"', false)
        ->assertSee('data-total-score="120.00"', false)->assertSee('data-passing-score="75.00"', false);
    $this->post(route('competitions.levels.store-existing', $competitionB), [
        'competition_id' => $competitionB->id, 'competition_level_id' => $level->id,
        'min_age' => 12, 'max_age' => 18, 'total_score' => 120, 'passing_score' => 75,
    ])->assertRedirect();

    expect(CompetitionLevelAssignment::where('competition_id', $competitionA->id)->where('competition_level_id', $level->id)->firstOrFail()
        ->only(['min_age', 'max_age', 'total_score', 'passing_score']))->toMatchArray(['min_age' => 10, 'max_age' => 17, 'total_score' => '100.00', 'passing_score' => '65.00'])
        ->and(CompetitionLevelAssignment::where('competition_id', $competitionB->id)->where('competition_level_id', $level->id)->firstOrFail()
            ->only(['min_age', 'max_age', 'total_score', 'passing_score']))->toMatchArray(['min_age' => 12, 'max_age' => 18, 'total_score' => '120.00', 'passing_score' => '75.00']);
});

it('rejects attaching an already assigned level and points competition CTAs to the dedicated flow', function () {
    $owner = User::factory()->create(['role' => 'User']);
    $competition = attachableCompetition($owner);
    $level = CompetitionLevel::create(['name' => 'Assigned Level', 'memorization_amount' => '5 أجزاء', 'type' => 'system', 'status' => 'active']);
    CompetitionLevelAssignment::create(['competition_id' => $competition->id, 'competition_level_id' => $level->id, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    CompetitionBranch::create(['competition_id' => $competition->id, 'competition_level_id' => $level->id, 'name' => $level->name, 'memorization_amount' => $level->memorization_amount, 'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    $this->actingAs($owner)->get(route('competitions.levels.add-existing', $competition))->assertDontSee('Assigned Level');
    $this->from(route('competitions.levels.add-existing', $competition))->post(route('competitions.levels.store-existing', $competition), [
        'competition_id' => $competition->id, 'competition_level_id' => $level->id,
        'min_age' => 10, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50,
    ])->assertRedirect()->assertSessionHasErrors('competition_level_id');
    $this->get(route('competitions.show', $competition))->assertSee(route('competitions.levels.add-existing', $competition), false);
});
