<?php

use App\Mail\OtpCodeMail;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('organizer registration normalizes username and activates after OTP verification', function () {
    Mail::fake();
    $response = $this->post(route('organizer.register.store'), [
        'name' => 'Organizer', 'organization_name' => 'Al Firdose', 'username' => 'AlFirdose',
        'email' => 'organizer@example.com', 'phone' => '01000000000',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('otp.verify.form'));
    $user = User::where('email', 'organizer@example.com')->firstOrFail();
    $this->assertGuest();
    $mail = null;
    Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $sent) use (&$mail): bool {
        $mail = $sent;

        return true;
    });
    $this->post(route('otp.verify'), ['code' => $mail->code])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user->fresh());
    $this->get(route('dashboard'))->assertOk()->assertSee('إنشاء أول مسابقة');

    $this->assertDatabaseHas('users', [
        'username' => 'alfirdose', 'role' => 'User', 'status' => 'active',
        'organization_name' => 'Al Firdose',
    ]);

    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->get(route('dashboard'))->assertOk();
});

test('organizer usernames must be unique and URL safe', function () {
    User::factory()->create(['username' => 'existing-org']);
    $payload = [
        'name' => 'Organizer', 'organization_name' => 'Org', 'username' => 'existing-org',
        'email' => 'new@example.com', 'phone' => '01000000000', 'password' => 'password', 'password_confirmation' => 'password',
    ];
    $this->post(route('organizer.register.store'), $payload)->assertSessionHasErrors('username');
    $payload['username'] = 'with-hyphen';
    $payload['email'] = 'new3@example.com';
    $this->post(route('organizer.register.store'), $payload)->assertSessionHasErrors('username');
    $payload['username'] = 'اسم';
    $payload['email'] = 'new2@example.com';
    $this->post(route('organizer.register.store'), $payload)->assertSessionHasErrors('username');
});

test('profile update cannot change immutable email', function () {
    $user = User::factory()->create(['email' => 'fixed@example.com', 'username' => null]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'organization_name' => 'Updated Organization',
        'username' => 'new_identity',
        'phone' => $user->phone,
        'email' => 'attacker@example.com',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email)->toBe('fixed@example.com')
        ->and($user->fresh()->username)->toBe('new_identity');
});

test('inactive organizer cannot login until activated', function () {
    $user = User::factory()->create(['email' => 'pending@example.com', 'password' => Hash::make('password'), 'status' => 'inactive']);
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $user->update(['status' => 'active']);
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
});

test('platform admin created active users can access the dashboard immediately', function () {
    $admin = User::factory()->create(['role' => 'Platform Admin', 'status' => 'active']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Managed User', 'email' => 'managed@example.com', 'phone' => '01000000000',
        'password' => 'password', 'password_confirmation' => 'password',
        'role' => 'User', 'status' => 'active',
    ])->assertRedirect();

    $user = User::where('email', 'managed@example.com')->firstOrFail();
    expect($user->email_verified_at)->not->toBeNull();
    $this->post(route('logout'));
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->get(route('dashboard'))->assertOk();
});

test('platform admin activation verifies organizer and unlocks dashboard', function () {
    $admin = User::factory()->create(['role' => 'Platform Admin', 'status' => 'active']);
    $organizer = User::factory()->create([
        'role' => 'User', 'status' => 'inactive', 'email_verified_at' => null,
        'email' => 'awaiting-activation@example.com', 'password' => Hash::make('password'),
    ]);

    $this->actingAs($admin)->put(route('users.update', $organizer), [
        'name' => $organizer->name, 'email' => $organizer->email, 'phone' => $organizer->phone,
        'role' => 'User', 'status' => 'active', 'password' => '', 'password_confirmation' => '',
    ])->assertRedirect();
    $organizer->refresh();
    expect($organizer->email_verified_at)->not->toBeNull();
    $this->actingAs($organizer)->get(route('dashboard'))->assertOk()->assertSee('إنشاء أول مسابقة');
});

test('competition numbers are sequential per owner and resolve canonical public URL', function () {
    $owner = User::factory()->create(['username' => 'owner-one']);
    $payload = [
        'title' => 'Public Competition', 'registration_start_date' => now()->subDay()->toDateTimeString(),
        'registration_end_date' => now()->addDay()->toDateTimeString(), 'exam_start_date' => now()->addDays(2)->toDateTimeString(),
        'exam_end_date' => now()->addDays(3)->toDateTimeString(), 'location' => 'Center', 'status' => 'Open for Registration',
    ];
    $this->actingAs($owner)->post(route('competitions.store'), $payload)->assertRedirect();
    $payload['title'] = 'Public Competition 2';
    $this->actingAs($owner)->post(route('competitions.store'), $payload)->assertRedirect();
    $competition = Competition::firstOrFail();
    workflowBranch($competition);
    expect($competition->competition_number)->toBe(1);
    expect(Competition::query()->where('created_by', $owner->id)->orderBy('competition_number')->pluck('competition_number')->all())->toBe([1, 2]);
    // Newly created competitions remain private until explicitly published.
    $this->get(route('competitions.public-register-canonical', ['owner-one', 1]))->assertNotFound();
    $this->get(route('competitions.public-register-canonical', ['wrong-owner', 1]))->assertNotFound();
});

test('canonical public URLs keep complete competitions visible after registration closes', function () {
    $owner = User::factory()->create(['username' => 'canonical-owner', 'status' => 'active']);
    $competition = workflowCompetition($owner);
    $competition->update(['competition_number' => 1]);
    workflowBranch($competition);

    $this->get(route('competitions.public-register-canonical', ['canonical-owner', 1]))->assertOk();

    $competition->update(['status' => 'Closed']);
    $this->get(route('competitions.public-register-canonical', ['canonical-owner', 1]))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('مغلق');

    $competition->update([
        'status' => 'Open for Registration',
        'registration_start_date' => now()->subDays(3),
        'registration_end_date' => now()->subDay(),
    ]);
    $this->get(route('competitions.public-register-canonical', ['canonical-owner', 1]))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('انتهى التسجيل');
});

test('competition numbering is isolated per owner and numeric URLs remain a fallback without username', function () {
    $ownerA = User::factory()->create(['username' => null]);
    $ownerB = User::factory()->create(['username' => null]);
    $payload = [
        'title' => 'Legacy Competition', 'registration_start_date' => now()->subDay()->toDateTimeString(),
        'registration_end_date' => now()->addDay()->toDateTimeString(), 'exam_start_date' => now()->addDays(2)->toDateTimeString(),
        'exam_end_date' => now()->addDays(3)->toDateTimeString(), 'location' => 'Center', 'status' => 'Open for Registration',
    ];
    $this->actingAs($ownerA)->post(route('competitions.store'), $payload)->assertRedirect();
    $this->actingAs($ownerB)->post(route('competitions.store'), $payload)->assertRedirect();
    $competitionA = Competition::query()->where('created_by', $ownerA->id)->firstOrFail();
    $competitionB = Competition::query()->where('created_by', $ownerB->id)->firstOrFail();
    workflowBranch($competitionA);
    expect($competitionA->competition_number)->toBe(1)
        ->and($competitionB->competition_number)->toBe(1);
    $this->get(route('competitions.public-register', $competitionA))->assertNotFound();
});

test('legacy numeric registration URLs keep complete closed competitions visible', function () {
    $owner = User::factory()->create(['username' => null]);
    $competition = workflowCompetition($owner);
    $competition->update(['status' => 'Closed']);
    workflowBranch($competition);

    $this->get(route('competitions.public-register', $competition))
        ->assertOk()
        ->assertSee('تم إغلاق التسجيل')
        ->assertSee('مغلق');
});

test('competition details guides setup and preserves branch context safely', function () {
    $owner = User::factory()->create(['username' => 'setup-owner']);
    $competition = Competition::create([
        'title' => 'Setup Competition', 'registration_start_date' => now()->addDay(),
        'registration_end_date' => now()->addDays(2), 'exam_start_date' => now()->addDays(3),
        'exam_end_date' => now()->addDays(4), 'location' => 'Center', 'status' => 'Draft',
        'created_by' => $owner->id, 'competition_number' => 1,
    ]);
    $this->actingAs($owner)->get(route('competitions.show', $competition))
        ->assertOk()->assertSee('أضف مستويات المسابقة للبدء')->assertSee('competition_id='.$competition->id, false);

    $other = User::factory()->create();
    $foreign = Competition::create([
        'title' => 'Foreign', 'registration_start_date' => now(), 'registration_end_date' => now()->addDay(),
        'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3),
        'location' => 'Center', 'status' => 'Draft', 'created_by' => $other->id, 'competition_number' => 1,
    ]);
    $this->actingAs($owner)->get(route('competition-branches.create', ['competition_id' => $foreign->id]))
        ->assertOk()->assertDontSee('value="'.$foreign->id.'" selected', false);
});
