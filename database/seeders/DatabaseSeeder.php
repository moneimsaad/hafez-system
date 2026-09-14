<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The initial Platform Admin is created explicitly with AdminUserSeeder
        // after HAFEZ_ADMIN_* values are configured; never seed predictable
        // credentials through the generic production seeder.
    }
}
