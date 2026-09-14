<?php

namespace App\Services;

use App\Mail\OtpCodeMail;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const REGISTRATION = 'registration';

    public const PASSWORD_RESET = 'password_reset';

    public function issue(User $user, string $purpose): OtpChallenge
    {
        abort_unless(in_array($purpose, [self::REGISTRATION, self::PASSWORD_RESET], true), 500);

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $challenge = DB::transaction(function () use ($user, $purpose, $code): OtpChallenge {
            OtpChallenge::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            return OtpChallenge::create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(config('otp.expires_minutes')),
            ]);
        });

        Mail::to($user->email)
            ->queue((new OtpCodeMail($code, $purpose))->afterCommit());

        return $challenge;
    }

    public function verify(OtpChallenge $challenge, string $code): bool
    {
        return DB::transaction(function () use ($challenge, $code): bool {
            $challenge = OtpChallenge::query()->lockForUpdate()->find($challenge->id);

            if (! $challenge
                || $challenge->verified_at !== null
                || $challenge->invalidated_at !== null
                || $challenge->expires_at->isPast()
                || $challenge->attempts >= config('otp.max_attempts')) {
                return false;
            }

            $challenge->increment('attempts');

            if (! Hash::check($code, $challenge->code_hash)) {
                return false;
            }

            $challenge->forceFill(['verified_at' => now()])->save();

            return true;
        });
    }
}
