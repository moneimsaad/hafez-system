<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('memorization_amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('system');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->unique(['created_by', 'type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_levels');
    }
};
