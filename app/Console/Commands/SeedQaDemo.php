<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedQaDemo extends Command
{
    protected $signature = 'hafez:seed-qa-demo';
    protected $description = 'Seed the repeatable Hafez local QA dataset (never production).';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('Refusing to seed QA data outside local/testing environment.');
            return self::FAILURE;
        }
        $this->call('db:seed', ['--class' => 'HafezQaSeeder', '--force' => true]);
        return self::SUCCESS;
    }
}
