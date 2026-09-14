<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy approval timestamps now represent automatic result finalization.
        DB::table('results')->whereNull('approved_at')->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        // Finalization is intentionally not rolled back to an obsolete pending state.
    }
};
