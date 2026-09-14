<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_branches', function (Blueprint $table) {
            $table->foreignId('competition_level_id')->nullable()->after('competition_id')->constrained('competition_levels')->nullOnDelete();
            $table->index('competition_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('competition_branches', function (Blueprint $table) {
            $table->dropForeign(['competition_level_id']);
            $table->dropIndex(['competition_level_id']);
            $table->dropColumn('competition_level_id');
        });
    }
};
