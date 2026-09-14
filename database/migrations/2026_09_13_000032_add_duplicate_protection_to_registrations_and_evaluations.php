<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('registrations')
            ->select(['competition_id', 'student_id'])
            ->groupBy(['competition_id', 'student_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Cannot add registration uniqueness: duplicate competition/student records exist.');
        }

        if (DB::table('evaluations')
            ->select(['registration_id', 'judge_id'])
            ->groupBy(['registration_id', 'judge_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Cannot add evaluation uniqueness: duplicate registration/judge records exist.');
        }

        Schema::table('registrations', function (Blueprint $table): void {
            $table->unique(['competition_id', 'student_id'], 'registrations_competition_student_unique');
        });

        Schema::table('evaluations', function (Blueprint $table): void {
            $table->unique(['registration_id', 'judge_id'], 'evaluations_registration_judge_unique');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table): void {
            $table->dropUnique('evaluations_registration_judge_unique');
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropUnique('registrations_competition_student_unique');
        });
    }
};
