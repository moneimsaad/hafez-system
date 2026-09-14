<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('competition_branches', function (Blueprint $table): void {
            $table->unsignedInteger('min_age')->nullable()->change();
            $table->unsignedInteger('max_age')->nullable()->change();
        });

        Schema::table('competitions', function (Blueprint $table): void {
            $table->text('additional_terms')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('additional_terms'));
        // Existing nullable age values are intentionally retained on rollback
        // because making them required could destroy valid legacy data.
    }
};
