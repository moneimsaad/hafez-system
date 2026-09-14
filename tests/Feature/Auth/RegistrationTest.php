<?php

use App\Mail\OtpCodeMail;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('organizers remain pending until their registration OTP is verified', function () {
    Mail::fake();
    $response = $this->get('/register');

    $response->assertOk();

    $response = $this->post('/register', [
        'name' => 'Registered User',
        'organization_name' => 'Registered Organization',
        'username' => 'registered_org',
        'email' => 'registered@example.com',
        'phone' => '01000000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('otp.verify.form'));
    $this->assertGuest();
    Mail::assertQueued(OtpCodeMail::class);
    $this->assertDatabaseHas('users', [
        'email' => 'registered@example.com',
        'role' => 'User',
        'organization_name' => 'Registered Organization',
        'username' => 'registered_org',
        'status' => 'inactive',
        'email_verified_at' => null,
    ]);

    $mail = null;
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $sent) use (&$mail): bool {
        $mail = $sent;

        return true;
    });
    $this->post('/otp/verify', ['code' => $mail->code])->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    expect(User::where('email', 'registered@example.com')->firstOrFail()->email_verified_at)->not->toBeNull();
});

test('public registration cannot create a Platform Admin', function () {
    Mail::fake();
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '01000000001',
        'password' => 'password',
        'organization_name' => 'Test Organization',
        'username' => 'test_org',
        'password_confirmation' => 'password',
        'role' => 'Platform Admin',
    ]);

    $response->assertRedirect(route('otp.verify.form'));
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com', 'role' => 'Platform Admin']);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'User', 'status' => 'inactive']);
});

test('abandoned unverified registrations can continue without replacing account data', function () {
    Mail::fake();

    $firstResponse = $this->post('/register', [
        'name' => 'Original Organizer',
        'organization_name' => 'Original Organization',
        'username' => 'original_org',
        'email' => 'recoverable@example.com',
        'phone' => '01000000002',
        'password' => 'OriginalPassword!2026',
        'password_confirmation' => 'OriginalPassword!2026',
    ]);

    $firstResponse->assertRedirect(route('otp.verify.form'));
    $user = User::query()->where('email', 'recoverable@example.com')->firstOrFail();
    $firstChallenge = OtpChallenge::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
    $originalPasswordHash = $user->password;

    $secondResponse = $this->post('/register', [
        'name' => 'Replacement Organizer',
        'organization_name' => 'Replacement Organization',
        'username' => 'original_org',
        'email' => 'recoverable@example.com',
        'phone' => '01000000003',
        'password' => 'ReplacementPassword!2026',
        'password_confirmation' => 'ReplacementPassword!2026',
    ]);

    $secondResponse->assertRedirect(route('otp.verify.form'));
    expect(User::query()->where('email', 'recoverable@example.com')->count())->toBe(1);
    $user->refresh();
    expect($user->name)->toBe('Original Organizer')
        ->and($user->organization_name)->toBe('Original Organization')
        ->and($user->phone)->toBe('01000000002')
        ->and($user->password)->toBe($originalPasswordHash)
        ->and(Hash::check('OriginalPassword!2026', $user->password))->toBeTrue();

    $firstChallenge->refresh();
    expect($firstChallenge->invalidated_at)->not->toBeNull();
    $secondChallenge = OtpChallenge::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
    expect($secondChallenge->id)->not->toBe($firstChallenge->id)
        ->and($secondChallenge->verified_at)->toBeNull()
        ->and($secondChallenge->invalidated_at)->toBeNull();
    Mail::assertQueued(OtpCodeMail::class, 2);
});

test('active verified accounts remain protected from duplicate registration', function () {
    Mail::fake();
    $user = User::factory()->create([
        'email' => 'active-existing@example.com',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $response = $this->post('/register', [
        'name' => 'Duplicate Organizer',
        'organization_name' => 'Duplicate Organization',
        'username' => 'duplicate_org',
        'email' => 'active-existing@example.com',
        'phone' => '01000000004',
        'password' => 'DuplicatePassword!2026',
        'password_confirmation' => 'DuplicatePassword!2026',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::query()->where('email', 'active-existing@example.com')->count())->toBe(1);
    expect(OtpChallenge::query()->where('user_id', $user->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('public registration cannot reuse an inactive unverified non-organizer account', function () {
    Mail::fake();
    $user = User::factory()->create([
        'email' => 'inactive-admin@example.com',
        'role' => 'Platform Admin',
        'status' => 'inactive',
        'email_verified_at' => null,
    ]);

    $response = $this->post('/register', [
        'name' => 'Unauthorized Organizer',
        'organization_name' => 'Unauthorized Organization',
        'username' => 'unauthorized_org',
        'email' => 'inactive-admin@example.com',
        'phone' => '01000000007',
        'password' => 'UnauthorizedPassword!2026',
        'password_confirmation' => 'UnauthorizedPassword!2026',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::query()->where('email', 'inactive-admin@example.com')->count())->toBe(1);
    expect(User::query()->where('email', 'inactive-admin@example.com')->value('role'))->toBe('Platform Admin');
    expect(OtpChallenge::query()->where('user_id', $user->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('an old registration OTP cannot verify a newly issued challenge', function () {
    Mail::fake();

    $this->post('/register', [
        'name' => 'OTP Recovery Organizer',
        'organization_name' => 'OTP Recovery Organization',
        'username' => 'otp_recovery_org',
        'email' => 'otp-recovery@example.com',
        'phone' => '01000000005',
        'password' => 'OtpRecoveryPassword!2026',
        'password_confirmation' => 'OtpRecoveryPassword!2026',
    ])->assertRedirect(route('otp.verify.form'));

    $firstMail = Mail::queued(OtpCodeMail::class)->first();
    $this->post('/register', [
        'name' => 'OTP Recovery Organizer Again',
        'organization_name' => 'OTP Recovery Organization Again',
        'username' => 'otp_recovery_org',
        'email' => 'otp-recovery@example.com',
        'phone' => '01000000006',
        'password' => 'OtpRecoveryPassword!2026',
        'password_confirmation' => 'OtpRecoveryPassword!2026',
    ])->assertRedirect(route('otp.verify.form'));

    $this->post('/otp/verify', ['code' => $firstMail->code])->assertSessionHasErrors('code');
    expect(User::query()->where('email', 'otp-recovery@example.com')->value('email_verified_at'))->toBeNull();
});
