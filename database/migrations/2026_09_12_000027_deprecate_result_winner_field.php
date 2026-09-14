<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve historical values while allowing new results to omit the
        // retired winner concept entirely.
        Schema::table('results', function (Blueprint $table): void {
            $table->boolean('is_winner')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Do not restore a mandatory field for a deprecated concept.
    }
};
