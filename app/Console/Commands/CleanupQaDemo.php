<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupQaDemo extends Command
{
    protected $signature = 'qa:cleanup-demo {--force : Skip confirmation}';
    protected $description = 'Remove only the isolated local/testing QA dataset.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('Refusing to remove QA data outside local/testing environment.');
            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Remove QA Demo competitions, records, levels, and QA users only?')) {
            return self::SUCCESS;
        }
        DB::transaction(function (): void {
            $competitionIds = Competition::where('title', 'like', 'QA Demo -%')->pluck('id');
            $committeeIds = Committee::whereIn('competition_id', $competitionIds)->pluck('id');
            DB::table('committee_students')->whereIn('committee_id', $committeeIds)->delete();
            DB::table('committee_judges')->whereIn('committee_id', $committeeIds)->delete();
            DB::table('committee_manual_judges')->whereIn('committee_id', $committeeIds)->delete();
            Committee::whereIn('id', $committeeIds)->delete();
            $branchIds = DB::table('competition_branches')->whereIn('competition_id', $competitionIds)->pluck('id');
            DB::table('evaluations')->whereIn('branch_id', $branchIds)->delete();
            DB::table('results')->whereIn('branch_id', $branchIds)->delete();
            DB::table('certificates')->whereIn('branch_id', $branchIds)->delete();
            Registration::whereIn('competition_id', $competitionIds)->delete();
            DB::table('competition_level_assignments')->whereIn('competition_id', $competitionIds)->delete();
            DB::table('competition_branches')->whereIn('competition_id', $competitionIds)->delete();
            Competition::whereIn('id', $competitionIds)->delete();
            $qaUserIds = User::whereIn('username', ['qa_cairo', 'qa_alex'])->pluck('id');
            CompetitionLevel::whereIn('created_by', $qaUserIds)->delete();
            $studentIds = Student::where('full_name', 'like', 'QA طالب %')->pluck('id');
            Student::whereIn('id', $studentIds)->delete();
            User::whereIn('username', ['qa_admin', 'qa_cairo', 'qa_alex', 'qa_judge_1', 'qa_judge_2', 'qa_judge_3', 'qa_judge_4'])->delete();
        });
        $this->info('QA demo data removed.');
        return self::SUCCESS;
    }
}
