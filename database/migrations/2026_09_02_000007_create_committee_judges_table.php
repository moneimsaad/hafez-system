<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees');
            $table->foreignId('judge_id')->constrained('users');
            $table->unique(['committee_id', 'judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_judges');
    }
};
