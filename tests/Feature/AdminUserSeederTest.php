<?php

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

uses(RefreshDatabase::class);

function adminSeederEnv(array $values): array
{
    $keys = ['HAFEZ_ADMIN_NAME', 'HAFEZ_ADMIN_EMAIL', 'HAFEZ_ADMIN_PHONE', 'HAFEZ_ADMIN_PASSWORD'];
    $previous = [];
    foreach ($keys as $key) {
        $previous[$key] = getenv($key);
        putenv($key . '=' . ($values[$key] ?? ''));
    }
    return $previous;
}

function restoreAdminSeederEnv(array $previous): void
{
    foreach ($previous as $key => $value) {
        putenv($key . ($value === false ? '' : '=' . $value));
    }
}

it('creates an explicit active verified platform admin', function () {
    $previous = adminSeederEnv([
        'HAFEZ_ADMIN_NAME' => 'QA Platform Admin',
        'HAFEZ_ADMIN_EMAIL' => 'qa-admin@example.test',
        'HAFEZ_ADMIN_PHONE' => '01012345678',
        'HAFEZ_ADMIN_PASSWORD' => 'qa-secret-password',
    ]);

    try {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('email', 'qa-admin@example.test')->firstOrFail();

        expect($admin->role)->toBe('Platform Admin')
            ->and($admin->status)->toBe('active')
            ->and($admin->email_verified_at)->not->toBeNull()
            ->and(Hash::check('qa-secret-password', $admin->password))->toBeTrue();
    } finally {
        restoreAdminSeederEnv($previous);
    }
});
it('is idempotent when seeded repeatedly with the same email', function () {
    $previous = adminSeederEnv([
        'HAFEZ_ADMIN_NAME' => 'QA Platform Admin',
        'HAFEZ_ADMIN_EMAIL' => 'qa-admin@example.test',
        'HAFEZ_ADMIN_PHONE' => '01012345678',
        'HAFEZ_ADMIN_PASSWORD' => 'qa-secret-password',
    ]);

    try {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);
        expect(User::where('email', 'qa-admin@example.test')->count())->toBe(1);
    } finally {
        restoreAdminSeederEnv($previous);
    }
});

it('fails safely when required admin credentials are missing', function () {
    $previous = adminSeederEnv([]);

    try {
        expect(fn () => $this->seed(AdminUserSeeder::class))
            ->toThrow(InvalidArgumentException::class, 'Required HAFEZ_ADMIN_* environment variables are missing.');
        expect(User::count())->toBe(0);
    } finally {
        restoreAdminSeederEnv($previous);
    }
});
