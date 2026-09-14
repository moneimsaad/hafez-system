<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('national_id')->nullable();
            $table->date('birth_date');
            $table->string('gender');
            $table->string('phone');
            $table->string('parent_phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('center_name');
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
