<?php

use Illuminate\Support\Facades\Schema;

test('student photo column removal migration is reversible', function () {
    $migration = require base_path('database/migrations/2026_09_13_000001_remove_photo_from_students_table.php');

    expect(Schema::hasColumn('students', 'photo'))->toBeFalse();

    $migration->down();
    expect(Schema::hasColumn('students', 'photo'))->toBeTrue();

    $migration->up();
    expect(Schema::hasColumn('students', 'photo'))->toBeFalse();
});
