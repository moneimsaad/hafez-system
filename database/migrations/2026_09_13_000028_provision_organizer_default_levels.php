<?php

use App\Services\OrganizerDefaultLevelsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('competition_levels', 'default_key')) return;
        app(OrganizerDefaultLevelsService::class)->provisionForExistingOrganizers();
    }

    public function down(): void
    {
        // Organizer-owned levels are user data; rollback must not delete them.
    }
};
