<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) env('HAFEZ_ADMIN_NAME', ''));
        $email = trim((string) env('HAFEZ_ADMIN_EMAIL', ''));
        $phone = trim((string) env('HAFEZ_ADMIN_PHONE', ''));
        $password = (string) env('HAFEZ_ADMIN_PASSWORD', '');

        if ($name === '' || $email === '' || $phone === '' || trim($password) === '') {
            throw new \InvalidArgumentException('Required HAFEZ_ADMIN_* environment variables are missing.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('HAFEZ_ADMIN_EMAIL must be a valid email address.');
        }

        if (! preg_match('/^(010|011|012|015)\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('HAFEZ_ADMIN_PHONE must be a valid Egyptian mobile number.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make($password),
                'role' => 'Platform Admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
