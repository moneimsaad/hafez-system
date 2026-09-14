<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class OtpCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 60, 300];

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'registration'
                ? 'رمز تفعيل حسابك في '.config('app.name')
                : 'رمز إعادة تعيين كلمة المرور في '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-code',
            text: 'emails.otp-code-text',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'expiresMinutes' => config('otp.expires_minutes'),
                'brandName' => config('app.name', 'Hafez System'),
            ],
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('OTP email delivery failed.', [
            'purpose' => $this->purpose,
            'exception' => class_basename($exception),
        ]);
    }
}
