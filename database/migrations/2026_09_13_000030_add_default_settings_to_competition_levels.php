<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_levels', function (Blueprint $table): void {
            $table->unsignedInteger('default_min_age')->nullable()->after('memorization_amount');
            $table->unsignedInteger('default_max_age')->nullable()->after('default_min_age');
            $table->decimal('default_total_score', 8, 2)->nullable()->after('default_max_age');
            $table->decimal('default_passing_score', 8, 2)->nullable()->after('default_total_score');
        });
    }

    public function down(): void
    {
        Schema::table('competition_levels', function (Blueprint $table): void {
            $table->dropColumn([
                'default_min_age',
                'default_max_age',
                'default_total_score',
                'default_passing_score',
            ]);
        });
    }
};
