<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('memorization_amount');
            $table->unsignedInteger('min_age');
            $table->unsignedInteger('max_age');
            $table->decimal('total_score', 8, 2);
            $table->decimal('passing_score', 8, 2);
            $table->unsignedInteger('winners_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_branches');
    }
};
