<?php

use App\Services\OrganizerDefaultLevelsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The default value columns are created by migration 000030. Keeping
        // provisioning here makes fresh installs and existing databases follow
        // the same safe order.
        app(OrganizerDefaultLevelsService::class)->provisionForExistingOrganizers();
    }

    public function down(): void
    {
        // Organizer-owned levels are user data and must survive rollback.
    }
};
