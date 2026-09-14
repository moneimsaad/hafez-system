<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('competition_id')->constrained('competitions');
            $table->foreignId('branch_id')->constrained('competition_branches');
            $table->foreignId('result_id')->constrained('results');
            $table->string('certificate_type');
            $table->string('certificate_number')->unique();
            $table->text('qr_code');
            $table->string('file_path');
            $table->dateTime('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
