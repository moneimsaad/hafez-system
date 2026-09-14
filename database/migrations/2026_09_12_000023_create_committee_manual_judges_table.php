<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_manual_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->index('committee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_manual_judges');
    }
};
