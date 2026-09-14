<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizerRegistrationRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OrganizerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.organizer-register');
    }

    public function store(StoreOrganizerRegistrationRequest $request, OtpService $otp): RedirectResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'User')
            ->first();

        if (! $user) {
            $user = DB::transaction(fn () => User::create([
                ...$request->safe()->only(['name', 'organization_name', 'username', 'email', 'phone']),
                'password' => Hash::make($request->validated('password')),
                'role' => 'User',
                'status' => 'inactive',
                'email_verified_at' => null,
            ]));
        }

        $challenge = $otp->issue($user, OtpService::REGISTRATION);
        $request->session()->put([
            'otp_challenge_id' => $challenge->id,
            'otp_purpose' => OtpService::REGISTRATION,
            'otp_email' => $user->email,
            'otp_resend_available_at' => now()->addSeconds(config('otp.resend_cooldown_seconds'))->timestamp,
        ]);

        return redirect()->route('otp.verify.form');
    }
}
