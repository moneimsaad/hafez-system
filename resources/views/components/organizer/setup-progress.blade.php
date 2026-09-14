@php
    $setupUser = auth()->user();
    $competitionCount = $setupUser?->role === 'User' ? $setupUser->competitions()->count() : 0;
    $firstCompetition = $competitionCount ? $setupUser->competitions()->withCount('competitionBranches')->latest()->first() : null;
    $hasLevels = (bool) ($firstCompetition?->competition_branches_count);
    $hasPublicLink = (bool) ($setupUser?->username && $firstCompetition?->competition_number);
    $completed = collect([$setupUser?->name && $setupUser?->email, $setupUser?->username, $competitionCount > 0, $hasLevels, $hasPublicLink])->filter()->count();
@endphp
@if($setupUser?->role === 'User')
<div class="card hafez-card hafez-setup-card border-0 mb-4" aria-labelledby="organizer-setup-title">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><p class="hafez-page-kicker mb-1">ملف المنظم</p><h2 id="organizer-setup-title" class="h5 mb-0">إعداد حساب المنظم</h2></div><span class="hafez-setup-card__count">{{ $completed }}/5 خطوات مكتملة</span></div>
        <div class="progress hafez-setup-card__progress mb-4" role="progressbar" aria-label="تقدم إعداد المنظم" aria-valuenow="{{ $completed * 20 }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: {{ $completed * 20 }}%"></div></div>
        <div class="row g-2 hafez-setup-card__steps">
            @foreach([
                ['label'=>'بيانات الحساب','done'=>(bool)($setupUser->name && $setupUser->email),'url'=>route('profile.edit')],
                ['label'=>'الهوية العامة للمنظم','done'=>(bool)$setupUser->username,'url'=>route('profile.edit').'#username'],
                ['label'=>'إنشاء أول مسابقة','done'=>$competitionCount > 0,'url'=>route('competitions.create')],
                ['label'=>'إضافة مستويات المسابقة','done'=>$hasLevels,'url'=>route('competition-branches.create')],
                ['label'=>'مشاركة رابط التسجيل','done'=>$hasPublicLink,'url'=>$firstCompetition ? route('competitions.show', $firstCompetition) : route('competitions.index')],
            ] as $step)
                <div class="col-12 col-md"><a href="{{ $step['url'] }}" class="hafez-setup-card__step {{ $step['done'] ? 'is-complete' : '' }}"><span aria-hidden="true" class="hafez-setup-card__step-icon">{{ $step['done'] ? '✓' : '○' }}</span><span>{{ $step['label'] }}</span></a></div>
            @endforeach
        </div>
    </div>
</div>
@endif
