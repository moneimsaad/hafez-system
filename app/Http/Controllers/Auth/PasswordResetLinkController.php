<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset OTP request.
     */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        $request->session()->forget([
            'otp_challenge_id', 'otp_purpose', 'otp_email',
            'password_reset_user_id', 'password_reset_token',
        ]);

        if ($user) {
            $challenge = $otp->issue($user, OtpService::PASSWORD_RESET);
            $request->session()->put([
                'otp_challenge_id' => $challenge->id,
                'otp_purpose' => OtpService::PASSWORD_RESET,
                'otp_email' => $user->email,
                'otp_resend_available_at' => now()->addSeconds(config('otp.resend_cooldown_seconds'))->timestamp,
            ]);
        } else {
            $request->session()->put('otp_purpose', OtpService::PASSWORD_RESET);
        }

        return redirect()->route('otp.verify.form')->with(
            'status',
            'إذا كان البريد الإلكتروني مسجلاً، فسيصلك رمز تحقق صالح لفترة محدودة.'
        );
    }
}
