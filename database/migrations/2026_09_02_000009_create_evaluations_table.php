<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions');
            $table->foreignId('branch_id')->constrained('competition_branches');
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('registration_id')->constrained('registrations');
            $table->foreignId('judge_id')->constrained('users');
            $table->decimal('memorization_score', 8, 2);
            $table->decimal('tajweed_score', 8, 2);
            $table->decimal('performance_score', 8, 2);
            $table->decimal('discipline_score', 8, 2);
            $table->decimal('total_score', 8, 2);
            $table->decimal('percentage', 8, 2);
            $table->text('notes')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
