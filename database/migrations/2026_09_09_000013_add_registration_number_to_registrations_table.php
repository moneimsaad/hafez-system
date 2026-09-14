<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('registration_number', 10)->nullable()->after('id');
        });

        DB::table('registrations')->orderBy('id')->get(['id', 'registered_at'])->each(function (object $registration): void {
            $year = date('Y', strtotime($registration->registered_at ?: 'now'));
            do {
                $number = $year.'-'.random_int(10000, 99999);
            } while (DB::table('registrations')->where('registration_number', $number)->exists());

            DB::table('registrations')->where('id', $registration->id)->update([
                'registration_number' => $number,
            ]);
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->unique('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropUnique('registrations_registration_number_unique');
            $table->dropColumn('registration_number');
        });
    }
};
