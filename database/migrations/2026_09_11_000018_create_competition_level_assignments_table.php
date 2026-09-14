<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_level_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->foreignId('competition_level_id')->constrained('competition_levels')->restrictOnDelete();
            $table->unsignedInteger('min_age');
            $table->unsignedInteger('max_age');
            $table->decimal('total_score', 8, 2);
            $table->decimal('passing_score', 8, 2);
            $table->timestamps();

            $table->unique(['competition_id', 'competition_level_id'], 'cla_competition_level_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_level_assignments');
    }
};
