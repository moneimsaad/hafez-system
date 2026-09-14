<?php

use App\Mail\OtpCodeMail;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('password reset screen can be rendered', function () {
    $this->get('/forgot-password')->assertOk();
});

test('password reset uses a four digit OTP and a generic response', function () {
    Mail::fake();

    $user = User::factory()->create();
    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect(route('otp.verify.form'))
        ->assertSessionHas('status');
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $mail) use ($user): bool {
        return $mail->hasTo($user->email)
            && $mail->purpose === 'password_reset'
            && preg_match('/^\d{4}$/', $mail->code) === 1;
    });
});

test('password reset does not reveal whether an email exists', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'known@example.test']);
    $existing = $this->post('/forgot-password', ['email' => $user->email]);
    $existingPage = $this->get('/otp/verify');
    $unknown = $this->post('/forgot-password', ['email' => 'unknown@example.test']);
    $unknownPage = $this->get('/otp/verify');

    expect($existing->status())->toBe($unknown->status())
        ->and($existing->getTargetUrl())->toBe($unknown->getTargetUrl());
    $unknownPage->assertOk()->assertDontSee($user->email);
    $existingPage->assertOk()->assertDontSee($user->email);
    Mail::assertQueued(OtpCodeMail::class);
});

test('password can be reset only after a valid OTP and the OTP cannot be reused', function () {
    Mail::fake();
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->post('/forgot-password', ['email' => $user->email]);
    $mail = null;
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $sent) use (&$mail): bool {
        $mail = $sent;

        return true;
    });

    $this->post('/otp/verify', ['code' => $mail->code])
        ->assertRedirect();
    $resetToken = session('password_reset_token');
    expect($resetToken)->toBeString()->not->toBeEmpty();

    $this->get('/reset-password/'.$resetToken)->assertOk();
    $this->post('/reset-password', [
        'token' => $resetToken,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    expect(OtpChallenge::query()->where('user_id', $user->id)->first()->verified_at)->not->toBeNull();

    $this->post('/otp/verify', ['code' => $mail->code])->assertSessionHasErrors('code');
});

test('wrong, expired, and repeated OTP attempts are rejected', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);
    $challenge = OtpChallenge::query()->latest('id')->firstOrFail();

    $this->post('/otp/verify', ['code' => '0000'])->assertSessionHasErrors('code');
    expect($challenge->fresh()->attempts)->toBe(1);

    $challenge->forceFill(['expires_at' => now()->subMinute()])->save();
    $this->post('/otp/verify', ['code' => '0000'])->assertSessionHasErrors('code');

    $this->post('/forgot-password', ['email' => $user->email]);
    Mail::assertQueued(OtpCodeMail::class, 2);
    $newMail = null;
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $sent) use (&$newMail): bool {
        $newMail = $sent;

        return true;
    });
    foreach (range(1, config('otp.max_attempts')) as $attempt) {
        $this->post('/otp/verify', ['code' => '1111'])->assertSessionHasErrors('code');
    }
    expect($newMail->code)->not->toBe('1111');
});

test('new OTP requests invalidate older challenges and password reset requests are rate limited per email', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);
    $first = OtpChallenge::query()->latest('id')->firstOrFail();
    $this->post('/forgot-password', ['email' => $user->email]);
    $second = OtpChallenge::query()->latest('id')->firstOrFail();

    expect($first->fresh()->invalidated_at)->not->toBeNull()
        ->and($second->fresh()->invalidated_at)->toBeNull();

    foreach (range(1, config('otp.request_limit')) as $attempt) {
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect();
    }

    $this->post('/forgot-password', ['email' => 'unknown@example.test'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('email');
});

test('password reset traffic cannot exhaust organizer registration requests', function () {
    Mail::fake();

    foreach (range(1, config('otp.request_limit')) as $attempt) {
        $this->post('/forgot-password', ['email' => 'same-address@example.test'])->assertRedirect();
    }

    $this->post('/register', [
        'name' => 'Independent Organizer',
        'organization_name' => 'Independent Organization',
        'username' => 'independent_org',
        'email' => 'independent@example.test',
        'phone' => '01000000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('otp.verify.form'));
});
