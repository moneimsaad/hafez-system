<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions');
            $table->foreignId('branch_id')->constrained('competition_branches');
            $table->foreignId('student_id')->constrained('students');
            $table->string('status');
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('registered_at');
            $table->dateTime('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
