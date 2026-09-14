<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions');
            $table->foreignId('branch_id')->constrained('competition_branches');
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('registration_id')->constrained('registrations');
            $table->decimal('final_score', 8, 2);
            $table->decimal('percentage', 8, 2);
            $table->unsignedInteger('rank')->nullable();
            $table->string('result_status');
            $table->boolean('is_winner');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->unique('registration_id', 'results_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
