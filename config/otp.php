<?php

return [
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 10),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'request_limit' => (int) env('OTP_REQUEST_LIMIT', 3),
    'request_decay_minutes' => (int) env('OTP_REQUEST_DECAY_MINUTES', 10),
    'registration_request_limit' => (int) env('OTP_REGISTRATION_REQUEST_LIMIT', 5),
    'password_reset_ip_limit' => (int) env('OTP_PASSWORD_RESET_IP_LIMIT', 10),
    'resend_limit' => (int) env('OTP_RESEND_LIMIT', 3),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
];
