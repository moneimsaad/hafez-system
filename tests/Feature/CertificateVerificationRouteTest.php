<?php

use App\Models\Certificate;
use App\Models\Competition;
use App\Models\CompetitionBranch;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;

test('guest can open the public certificate verification form', function () {
    $this->get('/certificates/verify')
        ->assertOk()
        ->assertSee('التحقق من الشهادة')
        ->assertSee('أدخل رقم الشهادة')
        ->assertDontSee('لم يتم العثور على الشهادة')
        ->assertDontSee('غير نشط');
});

test('invalid certificate lookup renders the intended public not-found state', function () {
    $this->get('/certificates/verify?certificate_number=missing-number')
        ->assertOk()
        ->assertSee('لم يتم العثور على الشهادة')
        ->assertDontSee('غير نشط')
        ->assertSee('value="missing-number"', false);
});

test('certificate verification named routes generate correctly', function () {
    expect(route('certificates.verify.form'))->toEndWith('/certificates/verify');
    expect(route('certificates.verify', '2026-12345'))->toEndWith('/certificates/verify/2026-12345');
    expect(route('certificates.verify.preview', '2026-12345'))->toEndWith('/certificates/verify/2026-12345/view');
});

test('guests can verify a certificate score and open its safe public preview', function () {
    $owner = User::factory()->create([
        'role' => 'User',
        'name' => 'منظم الشهادة',
        'organization_name' => 'جمعية القرآن للاختبارات',
        'email' => 'private-owner@example.test',
    ]);
    $competition = Competition::create(['title' => 'مسابقة الاختبار العام', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 31]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'حفظ خمسة أجزاء', 'memorization_amount' => '5 أجزاء', 'min_age' => 8, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $student = Student::create(['full_name' => 'طالب التحقق العام', 'birth_date' => '2012-01-01', 'gender' => 'Male', 'phone' => '01000000221', 'parent_phone' => '01000000222', 'address' => 'عنوان خاص لا يعرض', 'city' => 'مدينة خاصة', 'center_name' => 'مركز خاص']);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $result = Result::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'final_score' => 93.5, 'percentage' => 93.5, 'rank' => 1, 'result_status' => 'successful', 'is_winner' => true]);
    $certificate = Certificate::create(['student_id' => $student->id, 'competition_id' => $competition->id, 'branch_id' => $branch->id, 'result_id' => $result->id, 'certificate_type' => 'شهادة تقدير', 'certificate_number' => 'PUBLIC-SAFE-93', 'qr_code' => 'qr', 'file_path' => 'certificates/public-safe.pdf', 'issued_at' => now()]);

    $this->get(route('certificates.verify', $certificate->certificate_number))
        ->assertOk()
        ->assertSee('93.50')
        ->assertSee('درجة')
        ->assertSee('الأداء المحقق')
        ->assertSee('93.5%')
        ->assertSee($student->full_name)
        ->assertDontSee('لاختبار تنسيق الشهادة')
        ->assertSee(route('certificates.verify.preview', $certificate->certificate_number), false);

    $this->get(route('certificates.verify.preview', $certificate->certificate_number))
        ->assertOk()
        ->assertSee('شهادة تقدير')
        ->assertSee('جمعية القرآن للاختبارات')
        ->assertSee($student->full_name)
        ->assertSee($competition->title)
        ->assertSee($branch->name)
        ->assertSee($certificate->certificate_number)
        ->assertSee('data:image', false)
        ->assertDontSee($student->phone)
        ->assertDontSee($student->parent_phone)
        ->assertDontSee($student->address)
        ->assertDontSee($owner->email)
        ->assertDontSee('لوحة التحكم');

    $this->get(route('certificates.verify.preview', 'MISSING-CERTIFICATE'))
        ->assertNotFound();

    $this->get(route('certificates.show', $certificate))
        ->assertRedirect(route('login'));
});

test('authenticated certificate show route presents the issuing organization and recognition details', function () {
    $owner = User::factory()->create(['role' => 'User', 'organization_name' => 'مسجد الفتح']);
    $competition = Competition::create(['title' => 'Certificate Competition', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 1]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'Level', 'memorization_amount' => '5 juz', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);
    $student = Student::create(['full_name' => 'Student', 'birth_date' => '2012-01-01', 'gender' => 'Male', 'phone' => '01000000000', 'parent_phone' => '01000000001', 'address' => 'Address', 'city' => 'City', 'center_name' => 'Center']);
    $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
    $result = Result::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'final_score' => 90, 'percentage' => 90, 'rank' => 1, 'result_status' => 'successful', 'is_winner' => true]);
    $certificate = Certificate::create(['student_id' => $student->id, 'competition_id' => $competition->id, 'branch_id' => $branch->id, 'result_id' => $result->id, 'certificate_type' => 'Winner', 'certificate_number' => '2026-54321', 'qr_code' => 'qr', 'file_path' => 'certificates/test.pdf', 'issued_at' => now()]);

    $this->actingAs($owner)->get(route('certificates.show', $certificate))
        ->assertOk()
        ->assertSee('مسجد الفتح')
        ->assertSee('Hafez System')
            ->assertSee('وقد تحققت نسبة <strong dir="ltr">90%</strong>', false)
        ->assertSee($certificate->certificate_number)
        ->assertSee('data:image', false)
        ->assertSee('طباعة')
        ->assertDontSee('فتح الملف')
        ->assertDontSee('تشهد منصة Hafez System بأن الطالب/ـة')
        ->assertDontSee('certificate-sheet__mark', false);
    $this->get(route('certificates.verify', $certificate->certificate_number))
        ->assertOk()
        ->assertSee('شهادة صحيحة')
        ->assertSee($certificate->certificate_number)
        ->assertSee('الأداء المحقق')
        ->assertSee('90%')
        ->assertDontSee('90.00%');
});

test('certificate achievement recognition is only rendered above seventy five percent', function () {
    $owner = User::factory()->create(['role' => 'User', 'organization_name' => 'جمعية النور']);
    $competition = Competition::create(['title' => 'مسابقة النور', 'registration_start_date' => now()->subDay(), 'registration_end_date' => now()->addDay(), 'exam_start_date' => now()->addDays(2), 'exam_end_date' => now()->addDays(3), 'location' => 'Center', 'status' => 'Open for Registration', 'created_by' => $owner->id, 'competition_number' => 2]);
    $branch = CompetitionBranch::create(['competition_id' => $competition->id, 'name' => 'المستوى الخامس', 'memorization_amount' => 'حفظ القرآن الكريم كاملاً', 'min_age' => 5, 'max_age' => 18, 'total_score' => 100, 'passing_score' => 50]);

    foreach ([75, 74] as $percentage) {
        $student = Student::create(['full_name' => "Student {$percentage}", 'birth_date' => '2012-01-01', 'gender' => 'Male', 'phone' => "010000000{$percentage}", 'parent_phone' => "011000000{$percentage}", 'address' => 'Address', 'city' => 'City', 'center_name' => 'Center']);
        $registration = Registration::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'status' => 'approved', 'registered_at' => now()]);
        $result = Result::create(['competition_id' => $competition->id, 'branch_id' => $branch->id, 'student_id' => $student->id, 'registration_id' => $registration->id, 'final_score' => $percentage, 'percentage' => $percentage, 'rank' => 1, 'result_status' => 'successful']);
        $certificate = Certificate::create(['student_id' => $student->id, 'competition_id' => $competition->id, 'branch_id' => $branch->id, 'result_id' => $result->id, 'certificate_type' => 'شهادة تقدير', 'certificate_number' => "CERT-{$percentage}", 'qr_code' => 'qr', 'file_path' => "certificates/{$percentage}.pdf", 'issued_at' => now()]);

        $this->actingAs($owner)->get(route('certificates.show', $certificate))
            ->assertOk()
            ->assertDontSee('وقد تحققت نسبة');
    }
});

test('the PDF certificate template receives the same issuer and presentation content', function () {
    $html = view('certificate.pdf', [
        'certificateNumber' => 'CERT-PDF-1',
        'student' => (object) ['full_name' => 'اسم طالب طويل لاختبار سلامة عرض الشهادة'],
        'competition' => (object) ['title' => 'مسابقة الأوقاف لحفظ القرآن الكريم'],
        'branch' => (object) ['name' => 'المستوى الخامس', 'memorization_amount' => 'حفظ القرآن الكريم كاملاً', 'description' => null],
        'percentage' => 88,
        'qrDataUri' => 'data:image/png;base64,test',
        'showQr' => true,
        'organizationName' => 'مسجد الفتح',
        'issuedAt' => now(),
    ])->render();

    expect($html)->toContain(\App\Support\ArabicPdfText::visual('مسجد الفتح'))
        ->and($html)->toContain(\App\Support\ArabicPdfText::visual('وقد تحققت نسبة 88%، تقديراً للأداء المتميز في المسابقة.'))
        ->and($html)->toContain('Hafez System')
        ->and($html)->toContain('CERT-PDF-1')
        ->and($html)->not->toContain('تشهد منصة Hafez System بأن الطالب/ـة')
        ->and($html)->not->toContain('class="mark"');
});
