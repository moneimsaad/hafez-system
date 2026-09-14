<?php

use App\Http\Controllers\CompetitionBranchController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlatformSettingsController;
use Illuminate\Support\Facades\Route;




Route::get('/', function (\App\Services\CompetitionStatusService $statusService) {
    $competitions = \App\Models\Competition::query()->with(['creator', 'competitionBranches'])
        ->whereIn('publication_scope', ['governorate', 'nationwide'])
        ->whereIn('status', ['Registration Open', 'Open for Registration', 'active'])
        ->whereHas('competitionBranches')->whereNotNull('registration_start_date')->whereNotNull('registration_end_date')
        ->get()->filter(fn ($competition) => $statusService->isRegistrationOpen($competition))->values();
    return view('welcome', compact('competitions'));
});

Route::get('/competitions/{username}/{competition_number}/register', [RegistrationController::class, 'createForOwnerCompetition'])
    ->where('username', '[a-z0-9_-]+')->whereNumber('competition_number')->name('competitions.public-register-canonical');

// Public certificate verification must be registered before the authenticated
// /certificates/{certificate} resource route to avoid dynamic-route capture.
Route::get('/certificates/verify', [CertificateController::class, 'verify'])->name('certificates.verify.form');
Route::get('/certificates/verify/{certificateNumber}/view', [CertificateController::class, 'publicPreview'])
    ->name('certificates.verify.preview');
Route::get('/certificates/verify/{certificateNumber}', [CertificateController::class, 'verify'])->name('certificates.verify');

// Public student registration: students do not create accounts or authenticate.
Route::get('/registrations/create', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/registrations', [RegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('registrations.store');
Route::get('/registration-status', [RegistrationController::class, 'status'])->name('registrations.status');
Route::post('/registration-status', [RegistrationController::class, 'searchStatus'])->middleware('throttle:registration-status')->name('registrations.status.search');
Route::get('/registration-success', [RegistrationController::class, 'success'])->name('registrations.success');
Route::get('/competitions/{competition}/register', [RegistrationController::class, 'createForCompetition'])->name('competitions.public-register');

// Public routes area: public registration and certificate verification routes
// will be added only with their approved feature implementations.
Route::prefix('public')->group(function () {
    // Reserved for approved public routes.
});

// Authenticated routes area: feature routes are added in their approved batches.
Route::middleware('auth')->group(function () {
    // Reserved for authenticated foundation routes.
});

// Platform Admin protected area: no feature routes are added in this batch.
Route::middleware(['auth', 'active', 'role:Platform Admin'])
    ->prefix('platform-admin')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::get('settings', [PlatformSettingsController::class, 'edit'])->name('platform-admin.settings.edit');
        Route::put('settings', [PlatformSettingsController::class, 'update'])->name('platform-admin.settings.update');
    });

// User protected area: no business-module routes are added in this batch.
Route::middleware(['auth', 'role:User'])
    ->prefix('user')
    ->group(function () {
        // Reserved for approved User routes.
    });

// Competition management is available to both approved authenticated roles.
Route::middleware(['auth', 'active', 'role:Platform Admin,User'])
    ->group(function () {
        Route::get('competitions/{competition}/levels/add', [CompetitionBranchController::class, 'createExistingLevel'])
            ->name('competitions.levels.add-existing');
        Route::post('competitions/{competition}/levels', [CompetitionBranchController::class, 'storeExistingLevel'])
            ->name('competitions.levels.store-existing');
        Route::post('competitions/{competition}/status', [CompetitionController::class, 'transition'])
            ->name('competitions.status.update');
    Route::resource('competitions', CompetitionController::class);
    Route::get('competition-levels', fn () => redirect()->route('competition-branches.index'))->name('competition-levels.index');
    // Legacy compatibility branches have no reusable-level identity. Keep their
    // settings editable through a distinct route so they are not bound as a
    // CompetitionLevel by the reusable-level routes below.
    Route::get('competition-branches/legacy/{competitionBranch}/edit', [CompetitionBranchController::class, 'edit'])
        ->name('competition-branches.legacy.edit');
    Route::put('competition-branches/legacy/{competitionBranch}', [CompetitionBranchController::class, 'update'])
        ->name('competition-branches.legacy.update');
    Route::get('competition-branches/{competitionLevel}/edit', [CompetitionBranchController::class, 'editReusable'])->name('competition-branches.edit');
    Route::put('competition-branches/{competitionLevel}', [CompetitionBranchController::class, 'updateReusable'])->name('competition-branches.update');
    Route::delete('competition-branches/{competitionLevel}', [CompetitionBranchController::class, 'destroyReusable'])->name('competition-branches.destroy');
    Route::post('competition-branches/{competitionLevel}/restore', [CompetitionBranchController::class, 'restoreReusable'])->name('competition-branches.restore');
    Route::resource('competition-branches', CompetitionBranchController::class)->except(['edit','update','destroy']);
        Route::resource('committees', CommitteeController::class);
        Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::get('registrations/{registration}', [RegistrationController::class, 'show'])->name('registrations.show');
        Route::post('registrations/{registration}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
        Route::post('registrations/{registration}/reject', [RegistrationController::class, 'reject'])->name('registrations.reject');
        Route::post('registrations/bulk-review', [RegistrationController::class, 'bulkReview'])->name('registrations.bulk-review');
        Route::post('committees/{committee}/judges', [CommitteeController::class, 'assignJudges'])->name('committees.assign-judges');
        Route::post('committees/{committee}/manual-judges', [CommitteeController::class, 'storeManualJudge'])->name('committees.manual-judges.store');
        Route::delete('committees/{committee}/manual-judges/{manualJudge}', [CommitteeController::class, 'destroyManualJudge'])->name('committees.manual-judges.destroy');
        Route::post('committees/{committee}/students', [CommitteeController::class, 'assignStudents'])->name('committees.assign-students');
        Route::resource('evaluations', EvaluationController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('committees/{committee}/evaluations/entry', [EvaluationController::class, 'bulk'])->name('committees.evaluations.bulk');
        Route::post('committees/{committee}/evaluations/entry', [EvaluationController::class, 'bulkStore'])->name('committees.evaluations.bulk.store');
        Route::patch('committees/{committee}/evaluations/entry/{registration}', [EvaluationController::class, 'autosave'])->name('committees.evaluations.autosave');
        Route::get('results', [ResultController::class, 'index'])->name('results.index');
          Route::post('results/generate', [ResultController::class, 'generate'])->name('results.generate');
          Route::post('results/certificates/bulk', [ResultController::class, 'issueCertificates'])->name('results.certificates.bulk');
          Route::post('results/{result}/certificate', [ResultController::class, 'issueCertificate'])->name('results.certificate');
        Route::get('results/{result}', [ResultController::class, 'show'])->name('results.show');
        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    });

Route::middleware(['auth', 'active', 'verified', 'role:Platform Admin,User'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
