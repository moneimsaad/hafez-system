<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('competition_levels', 'default_key')) {
            Schema::table('competition_levels', function (Blueprint $table) { $table->string('default_key')->nullable()->after('status'); });
        }
        foreach (config('competition_levels.organizer_defaults', []) as $definition) {
            DB::table('competition_levels')->where('type', 'organizer')->where('name', $definition['name'])->whereNull('default_key')->update(['default_key' => $definition['key']]);
        }
        Schema::table('competition_levels', function (Blueprint $table) { $table->unique(['created_by', 'type', 'default_key'], 'competition_levels_owner_default_key_unique'); });
    }

    public function down(): void
    {
        Schema::table('competition_levels', function (Blueprint $table) { $table->dropUnique('competition_levels_owner_default_key_unique'); });
    }
};
