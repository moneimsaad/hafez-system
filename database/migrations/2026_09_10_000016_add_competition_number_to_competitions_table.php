<?php

use App\Models\Competition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedInteger('competition_number')->nullable()->after('id');
            $table->unique(['created_by', 'competition_number']);
        });

        // Existing records remain valid; assign deterministic per-owner numbers.
        Competition::query()->orderBy('created_by')->orderBy('id')->get()->groupBy('created_by')->each(function ($competitions) {
            $number = 1;
            foreach ($competitions as $competition) {
                if ($competition->competition_number === null) {
                    $competition->newQuery()->whereKey($competition->getKey())->update(['competition_number' => $number]);
                }
                $number++;
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropUnique(['created_by', 'competition_number']);
            $table->dropColumn('competition_number');
        });
    }
};
