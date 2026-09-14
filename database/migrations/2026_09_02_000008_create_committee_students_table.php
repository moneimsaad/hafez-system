<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees');
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('registration_id')->constrained('registrations');
            $table->unique(
                ['committee_id', 'student_id', 'registration_id'],
                'committee_students_assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_students');
    }
};
