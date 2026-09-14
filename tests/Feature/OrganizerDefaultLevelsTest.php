<?php

use App\Mail\OtpCodeMail;
use App\Models\CompetitionLevel;
use App\Models\User;
use App\Services\OrganizerDefaultLevelsService;
use Illuminate\Support\Facades\Mail;

it('provisions six independent reusable defaults for each organizer and remains idempotent', function () {
    Mail::fake();
    $this->post(route('register'), [
        'name' => 'Organizer One', 'organization_name' => 'Org One', 'username' => 'org_one',
        'email' => 'org1@example.test', 'phone' => '01012345678', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('otp.verify.form'));
    $mail = null;
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $sent) use (&$mail): bool {
        $mail = $sent;

        return true;
    });
    $this->post(route('otp.verify'), ['code' => $mail->code])->assertRedirect(route('dashboard'));
    $one = User::where('email', 'org1@example.test')->firstOrFail();
    expect($one->competitionLevels()->where('type', 'organizer')->count())->toBe(6);
    $this->actingAs($one)->get(route('competition-branches.index'))
        ->assertOk()
        ->assertSee('القرآن الكريم كاملاً')->assertSee('نصف القرآن')->assertSee('عشرة أجزاء')
        ->assertSee('خمسة أجزاء')->assertSee('جزء عم')->assertSee('ربع القرآن');
    $defaults = $one->competitionLevels()->where('type', 'organizer')->get()->keyBy('default_key');
    expect($defaults['full_quran']->memorization_amount)->toBe('القرآن كاملاً')->and($defaults['full_quran']->default_min_age)->toBe(10)->and($defaults['full_quran']->default_max_age)->toBe(25)->and((int) $defaults['full_quran']->default_total_score)->toBe(100)->and((int) $defaults['full_quran']->default_passing_score)->toBe(60);
    expect($defaults['quarter_quran']->memorization_amount)->toBe('7 أجزاء ونصف (ربع القرآن)')->and($defaults['quarter_quran']->default_min_age)->toBe(8)->and($defaults['quarter_quran']->default_max_age)->toBe(18);
    app(OrganizerDefaultLevelsService::class)->provisionFor($one);
    expect($one->competitionLevels()->where('type', 'organizer')->count())->toBe(6);

    $two = User::factory()->create(['email' => 'org2@example.test', 'organization_name' => 'Org Two']);
    app(OrganizerDefaultLevelsService::class)->provisionFor($two);
    expect($two->competitionLevels()->count())->toBe(6);
    expect(CompetitionLevel::where('name', 'خمسة أجزاء')->where('created_by', $one->id)->value('id'))
        ->not->toBe(CompetitionLevel::where('name', 'خمسة أجزاء')->where('created_by', $two->id)->value('id'));
});

it('keeps provisioning idempotent after a rename and does not recreate an intentional deletion', function () {
    $owner = User::factory()->create(['organization_name' => 'Org']);
    $service = app(OrganizerDefaultLevelsService::class);
    $service->provisionFor($owner);
    $level = $owner->competitionLevels()->where('default_key', 'five_parts')->firstOrFail();
    $level->update(['name' => 'المستوى المتوسط']);
    $service->provisionFor($owner);
    expect($owner->competitionLevels()->where('default_key', 'five_parts')->count())->toBe(1);
    $level->delete();
    expect($owner->competitionLevels()->where('default_key', 'five_parts')->count())->toBe(0);
    expect($owner->competitionLevels()->count())->toBe(5);
});
