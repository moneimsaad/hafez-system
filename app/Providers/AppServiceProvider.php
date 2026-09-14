<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.hafez');

        RateLimiter::for('registration-request', function (Request $request) {
            return Limit::perMinutes(
                config('otp.request_decay_minutes'),
                config('otp.registration_request_limit'),
            )->by('registration|'.strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn (Request $request) => $this->rateLimitRedirect($request, 'email'));
        });

        RateLimiter::for('password-reset-request', function (Request $request) {
            $decay = config('otp.request_decay_minutes');
            $emailKey = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinutes($decay, config('otp.request_limit'))
                    ->by('password-reset-email|'.$emailKey.'|'.$request->ip())
                    ->response(fn (Request $request) => $this->rateLimitRedirect($request, 'email')),
                Limit::perMinutes($decay, config('otp.password_reset_ip_limit'))
                    ->by('password-reset-ip|'.$request->ip())
                    ->response(fn (Request $request) => $this->rateLimitRedirect($request, 'email')),
            ];
        });

        RateLimiter::for('otp-resend', function (Request $request) {
            return Limit::perMinutes(
                config('otp.request_decay_minutes'),
                config('otp.resend_limit'),
            )->by('otp-resend|'.$request->session()->get('otp_challenge_id', 'guest').'|'.$request->ip())
                ->response(fn () => redirect()->route('otp.verify.form')->withErrors([
                    'resend' => 'تم الوصول إلى الحد المسموح لإعادة الإرسال. يرجى المحاولة لاحقاً.',
                ]));
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(10)
                ->by('otp-verify|'.$request->session()->get('otp_challenge_id', 'guest').'|'.$request->ip())
                ->response(fn () => redirect()->route('otp.verify.form')->withErrors([
                    'code' => 'تم إيقاف المحاولات مؤقتاً. يرجى الانتظار دقيقة ثم المحاولة مجدداً.',
                ]));
        });

        RateLimiter::for('registration-status', function (Request $request) {
            $number = strtolower(trim((string) $request->input('registration_number')));

            return [
                Limit::perMinute(5)
                    ->by('registration-status-ip|'.$request->ip())
                    ->response(fn () => redirect()->route('registrations.status')->withErrors([
                        'registration_number' => 'تم الوصول إلى الحد المسموح من المحاولات. يرجى المحاولة لاحقًا.',
                    ])),
                Limit::perHour(30)
                    ->by('registration-status-hour|'.$request->ip())
                    ->response(fn () => redirect()->route('registrations.status')->withErrors([
                        'registration_number' => 'تم الوصول إلى الحد المسموح من المحاولات. يرجى المحاولة لاحقًا.',
                    ])),
                Limit::perMinutes(15, 3)
                    ->by('registration-status-number|'.$number.'|'.$request->ip())
                    ->response(fn () => redirect()->route('registrations.status')->withErrors([
                        'registration_number' => 'تم الوصول إلى الحد المسموح من المحاولات. يرجى المحاولة لاحقًا.',
                    ])),
            ];
        });
    }

    private function rateLimitRedirect(Request $request, string $field)
    {
        $destination = $request->routeIs('password.email')
            ? route('password.request')
            : route('register');

        return redirect()->to($destination)
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors([$field => 'تم إجراء محاولات كثيرة. يرجى الانتظار قليلاً ثم المحاولة مجدداً.']);
    }
}
