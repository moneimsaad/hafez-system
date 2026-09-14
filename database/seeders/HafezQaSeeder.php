<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Committee;
use App\Models\CommitteeJudge;
use App\Models\CommitteeStudent;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\CompetitionLevel;
use App\Models\Evaluation;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateGenerationService;
use App\Services\CompetitionLevelAssignmentSyncService;
use App\Services\OrganizerDefaultLevelsService;
use App\Services\EvaluationScoreService;
use App\Services\ResultCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Complete, repeatable local QA fixture for the organizer workflow. */
class HafezQaSeeder extends Seeder
{
    private const MAIN_TITLE = 'مسابقة جمعية رسالة لحفظ القرآن الكريم 2026';
    private const OPEN_TITLE = 'مسابقة جمعية رسالة المفتوحة لحفظ القرآن الكريم';

    private array $names = [
        'أحمد محمد عبد الرحمن','محمود علي حسن','يوسف خالد إبراهيم','عمر أحمد مصطفى','عبد الرحمن محمد السيد','مصطفى محمود علي','محمد ياسر عبد الله','سلمى أحمد حسن','مريم محمد إبراهيم','نورهان خالد محمود',
        'زياد وليد حسن','سيف الدين أحمد علي','حسن إبراهيم محمود','عبد الله سامي عبد العزيز','آية محمد فتحي','ملك أحمد عادل','جنى محمود سيد','حبيبة خالد حسن','ياسين أشرف محمد','حمزة علي إبراهيم',
        'إياد محمد نبيل','كريم وائل مصطفى','فاطمة حسن عبد الله','رقية أحمد محمود','شهد محمد سامي','سارة وليد إبراهيم','آدم خالد السيد','أنس محمود حسن','بلال أحمد عبد الرحمن','حور محمد علي',
        'ليان يوسف محمود','تالا حسن إبراهيم','معاذ سامي أحمد','معاذ وليد عبد الله','علي محمد حسين','زينب خالد مصطفى','رؤى أحمد ياسر','فرح محمود علي','ملكوت محمد حسن','عبد العزيز إبراهيم السيد',
        'إسلام أحمد محمود','عبد الباسط محمد علي','منار خالد إبراهيم','بسملة حسن أحمد','طارق محمود عبد الله','يحيى سامي حسن','رحمة محمد مصطفى','جود أحمد إبراهيم','عبد الملك وليد علي','شيماء خالد حسن',
        'محمد عبد الوهاب السيد','عمر محمود إبراهيم','أحمد سامح علي','محمود ياسر حسن','يوسف أشرف عبد الله','سلمى خالد محمود','مريم أحمد السيد','نور محمد مصطفى','عبد الرحمن علي حسن','مصطفى وليد إبراهيم',
        'سيف محمد عبد العزيز','سارة أحمد محمود','ياسين خالد علي','حسن سامي إبراهيم','آية محمود حسن','ملك محمد عبد الله','زياد أحمد مصطفى','جنى وليد السيد','حبيبة سامح محمود','كريم خالد إبراهيم',
        'فاطمة أحمد علي','رقية محمد حسن','أنس وليد عبد الرحمن','حور محمود السيد','آدم سامي إبراهيم','ليان أحمد حسن','بلال محمد علي','تالا خالد محمود','معاذ أحمد عبد الله','زينب حسن مصطفى',
        'رؤى محمد إبراهيم','فرح أحمد السيد','عبد العزيز محمود حسن','إسلام خالد علي','منار أحمد عبد الرحمن','بسملة محمد محمود','يحيى وليد حسن','رحمة خالد إبراهيم','جود محمد علي','شيماء أحمد حسن',
        'طارق سامي عبد الله','عبد الباسط محمود مصطفى','محمد سامح إبراهيم','سيف الدين وليد حسن','نورهان أحمد علي','مريم خالد السيد','يوسف محمد محمود','أحمد وليد عبد الرحمن','سلمى سامي حسن','عمر خالد مصطفى',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('QA data is local/testing only.');
        }

        $this->qaPassword();

        [$organizer, $judges] = DB::transaction(function (): array {
            $organizer = User::updateOrCreate(['email' => $this->qaEmail()], [
                'name' => 'منعم سعد','organization_name' => 'جميعية رسالة','username' => 'menem_saad','phone' => '01012345678',
                'role' => 'User','status' => 'active','password' => Hash::make($this->qaPassword()),'email_verified_at' => now(),
            ]);
            app(OrganizerDefaultLevelsService::class)->provisionFor($organizer);
            $judges = $this->createJudges();
            $levels = $this->createLevels();
            $main = $this->createCompetition($organizer, self::MAIN_TITLE, 1, false);
            $open = $this->createCompetition($organizer, self::OPEN_TITLE, 2, true);
            $branches = $this->attachLevels($main, $levels, 5);
            $this->attachLevels($open, $levels, 4);
            $registrations = $this->createStudentsAndRegistrations($main, $branches);
            $committees = $this->createCommittees($main, $branches, $judges);
            $this->assignStudentsAndEvaluations($registrations, $committees, $judges, $main);
            return [$organizer, $judges];
        });

        $main = Competition::with('competitionBranches')->where('title', self::MAIN_TITLE)->firstOrFail();
        $calculator = app(ResultCalculationService::class);
        foreach ($main->competitionBranches as $branch) $calculator->generate($main, $branch->id);
        $generator = app(CertificateGenerationService::class);
        foreach ($main->results()->get() as $result) if (! $result->certificates()->exists()) $generator->generate($result);
        $this->command?->info(sprintf('QA ready: students=%d registrations=%d judges=%d committees=%d evaluations=%d results=%d certificates=%d open_url=%s',
            Student::where('email', 'like', 'qa.hafez.student%@example.test')->count(), $main->registrations()->count(), count($judges), $main->committees()->count(),
            $main->evaluations()->count(), $main->results()->count(), $main->certificates()->count(), route('competitions.public-register-canonical', [$organizer->username, 2])));
    }

    private function createJudges(): array
    {
        $defs = [['الشيخ أحمد محمود','qa.judge.ahmed@example.test','01110000001'],['الشيخ خالد عبد الرحمن','qa.judge.khaled@example.test','01110000002'],['الشيخ محمد إبراهيم','qa.judge.mohamed@example.test','01110000003']];
        return collect($defs)->mapWithKeys(function (array $d, int $i): array { return [$i => User::updateOrCreate(['email' => $d[1]], [
            'name' => $d[0],'username' => 'qa_judge_'.($i + 1),'phone' => $d[2],'role' => 'User','status' => 'active','password' => Hash::make($this->qaPassword()),'email_verified_at' => now(),
        ])]; })->all();
    }

    private function createLevels(): array
    {
        $organizer = User::where('email', $this->qaEmail())->firstOrFail();
        return CompetitionLevel::query()->where('created_by', $organizer->id)->where('type', 'organizer')->whereIn('name', collect(config('competition_levels.organizer_defaults'))->pluck('name'))->orderBy('id')->get()->all();
    }

    private function qaEmail(): string
    {
        return (string) env('HAFEZ_QA_EMAIL', 'qa.organizer@example.test');
    }

    private function qaPassword(): string
    {
        $password = (string) env('HAFEZ_QA_PASSWORD', '');

        if ($password === '') {
            throw new \RuntimeException('Set HAFEZ_QA_PASSWORD before running the QA seeder.');
        }

        return $password;
    }

    private function createCompetition(User $owner, string $title, int $number, bool $open): Competition
    {
        $now = now();
        return Competition::updateOrCreate(['created_by' => $owner->id,'title' => $title], [
            'competition_number' => $number,'description' => 'بيانات QA محلية لاختبار دورة المسابقة.','registration_start_date' => $open ? $now->copy()->subDays(2) : $now->copy()->subDays(90),
            'registration_end_date' => $open ? $now->copy()->addDays(30) : $now->copy()->subDays(60),'exam_start_date' => $open ? $now->copy()->addDays(45) : $now->copy()->subDays(30),
            'exam_end_date' => $open ? $now->copy()->addDays(50) : $now->copy()->subDays(25),'location' => 'مقر جمعية رسالة - القاهرة','status' => 'active','publication_scope' => 'nationwide','target_governorate' => null,'rules' => ['multiple_judges' => 'average'],
        ]);
    }

    private function attachLevels(Competition $competition, array $levels, int $count): array
    {
        $sync = app(CompetitionLevelAssignmentSyncService::class);
        return collect(array_slice($levels, 0, $count))->map(function (CompetitionLevel $level) use ($competition, $sync): CompetitionBranch {
            $branch = CompetitionBranch::where('competition_id', $competition->id)->where('competition_level_id', $level->id)->first();
            if (! $branch) $branch = $sync->createBranch(['competition_id' => $competition->id,'min_age' => 8,'max_age' => 18,'total_score' => 100,'passing_score' => 60], $level);
            else { $branch->update(['min_age' => 8,'max_age' => 18,'total_score' => 100,'passing_score' => 60]); $sync->syncAssignmentFromBranch($branch, $level); }
            return $branch->fresh();
        })->all();
    }

    private function createStudentsAndRegistrations(Competition $competition, array $branches): array
    {
        $govs = ['القاهرة','الجيزة','الإسكندرية','القليوبية','المنوفية']; $out = [];
        foreach ($this->names as $i => $name) {
            $n = $i + 1; $birth = Carbon::create(2008 + ($n % 8), (($n - 1) % 12) + 1, (($n - 1) % 25) + 1);
            $student = Student::updateOrCreate(['email' => "qa.hafez.student{$n}@example.test"], ['full_name' => $name,'national_id' => null,'birth_date' => $birth->toDateString(),'gender' => $n % 2 ? 'Male' : 'Female','phone' => sprintf('010%08d', 70000000 + $n),'parent_phone' => sprintf('011%08d', 80000000 + $n),'address' => 'شارع النيل','city' => $govs[$i % 5],'center_name' => 'مركز تحفيظ جمعية رسالة']);
            $branch = $branches[intdiv($i, 20)];
            $out[] = Registration::updateOrCreate(['competition_id' => $competition->id,'student_id' => $student->id], ['registration_number' => 'RS26-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),'branch_id' => $branch->id,'governorate' => $govs[$i % 5],'status' => 'approved','notes' => 'تسجيل QA معتمد.','registered_at' => now()->subDays($n % 20),'approved_at' => now()->subDays($n % 10)])->fresh();
        }
        return $out;
    }

    private function createCommittees(Competition $competition, array $branches, array $judges): array
    {
        return collect($branches)->map(function (CompetitionBranch $branch, int $i) use ($competition, $judges): Committee {
            $committee = Committee::updateOrCreate(['competition_id' => $competition->id,'branch_id' => $branch->id], ['name' => 'لجنة جمعية رسالة - '.$branch->name,'exam_date' => now()->subDays(20),'location' => 'قاعة الاختبارات '.($i + 1)]);
            foreach ($judges as $judge) CommitteeJudge::firstOrCreate(['committee_id' => $committee->id,'judge_id' => $judge->id]);
            return $committee;
        })->all();
    }

    private function assignStudentsAndEvaluations(array $registrations, array $committees, array $judges, Competition $competition): void
    {
        $scoreService = app(EvaluationScoreService::class);
        foreach ($registrations as $i => $registration) {
            $committee = $committees[intdiv($i, 20)];
            CommitteeStudent::firstOrCreate(['committee_id' => $committee->id,'student_id' => $registration->student_id,'registration_id' => $registration->id]);
            $base = $i < 3 ? 94 : ($i < 70 ? 72 + ($i % 18) : 42 + ($i % 16));
            foreach ($judges as $j => $judge) {
                $total = min(100, $base + ($j === 1 && $i % 11 === 0 ? 1 : 0));
                $scores = [['name' => 'الحفظ','score' => round($total * .5, 2),'max_score' => 50],['name' => 'التجويد','score' => round($total * .25, 2),'max_score' => 25],['name' => 'الأداء','score' => round($total - round($total * .5, 2) - round($total * .25, 2), 2),'max_score' => 25]];
                Evaluation::updateOrCreate(['registration_id' => $registration->id,'judge_id' => $judge->id], ['competition_id' => $competition->id,'branch_id' => $committee->branch_id,'student_id' => $registration->student_id,'memorization_score' => $scores[0]['score'],'tajweed_score' => $scores[1]['score'],'performance_score' => $scores[2]['score'],'discipline_score' => 0,'scores' => $scores,'status' => 'submitted','notes' => 'تقييم QA مكتمل.'] + $scoreService->calculate(collect($scores)->pluck('score')->all(), 100));
            }
        }
    }
}
