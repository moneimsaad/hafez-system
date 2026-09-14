<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\OrganizerDefaultLevelsService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $purpose = $request->session()->get('otp_purpose');
        $challenge = $this->challengeFromSession($request);

        if ($purpose === OtpService::REGISTRATION && ! $challenge) {
            return redirect()->route('register');
        }

        if (! in_array($purpose, [OtpService::REGISTRATION, OtpService::PASSWORD_RESET], true)) {
            return redirect()->route('login');
        }

        return view('auth.otp-verify', [
            'purpose' => $purpose,
            'email' => $request->session()->get('otp_email'),
            'hasChallenge' => $challenge !== null,
            'resendAvailableAt' => (int) $request->session()->get('otp_resend_available_at', 0),
        ]);
    }

    public function store(Request $request, OtpService $otp, OrganizerDefaultLevelsService $defaults): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:4']]);
        $challenge = $this->challengeFromSession($request);

        if (! $challenge || ! $otp->verify($challenge, (string) $request->input('code'))) {
            return back()->withErrors(['code' => $this->verificationErrorMessage($challenge)]);
        }

        $purpose = $request->session()->get('otp_purpose');
        $request->session()->forget(['otp_challenge_id', 'otp_purpose', 'otp_email', 'otp_resend_available_at']);

        if ($purpose === OtpService::REGISTRATION) {
            $user = DB::transaction(function () use ($challenge): User {
                $user = $challenge->user()->lockForUpdate()->firstOrFail();
                $user->forceFill([
                    'email_verified_at' => now(),
                    'status' => 'active',
                ])->save();

                return $user;
            });

            $defaults->provisionFor($user);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        $resetToken = Str::random(40);
        $request->session()->put([
            'password_reset_user_id' => $challenge->user_id,
            'password_reset_token' => $resetToken,
        ]);

        return redirect()->route('password.reset', ['token' => $resetToken]);
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $challenge = $this->challengeFromSession($request);
        $availableAt = (int) $request->session()->get('otp_resend_available_at', 0);

        if ($availableAt > now()->timestamp) {
            return redirect()->route('otp.verify.form')->withErrors([
                'resend' => 'يرجى الانتظار '.($availableAt - now()->timestamp).' ثانية قبل طلب رمز جديد.',
            ]);
        }

        if ($challenge) {
            $newChallenge = $otp->issue($challenge->user, $challenge->purpose);
            $request->session()->put([
                'otp_challenge_id' => $newChallenge->id,
                'otp_resend_available_at' => now()->addSeconds(config('otp.resend_cooldown_seconds'))->timestamp,
            ]);
        }

        return redirect()->route('otp.verify.form')->with(
            'status',
            'إذا كان الطلب صالحاً، فقد تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني.'
        );
    }

    private function challengeFromSession(Request $request): ?OtpChallenge
    {
        $id = $request->session()->get('otp_challenge_id');
        $purpose = $request->session()->get('otp_purpose');

        if (! $id || ! in_array($purpose, [OtpService::REGISTRATION, OtpService::PASSWORD_RESET], true)) {
            return null;
        }

        return OtpChallenge::query()
            ->whereKey($id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('invalidated_at')
            ->first();
    }

    private function verificationErrorMessage(?OtpChallenge $challenge): string
    {
        $challenge = $challenge?->fresh();

        if ($challenge?->expires_at?->isPast()) {
            return 'انتهت صلاحية الرمز. اطلب رمزاً جديداً للمتابعة.';
        }

        if ($challenge && $challenge->attempts >= config('otp.max_attempts')) {
            return 'تم الوصول إلى الحد المسموح للمحاولات. اطلب رمزاً جديداً للمتابعة.';
        }

        return 'رمز التحقق غير صحيح. تأكد من الأرقام الأربعة ثم حاول مجدداً.';
    }
}
