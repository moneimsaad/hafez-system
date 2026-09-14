@php
    $nextUser = auth()->user();
    $nextCompetitions = $nextUser?->role === 'User' ? $nextUser->competitions()->withCount('competitionBranches')->latest()->get() : collect();
    $nextCompetition = $nextCompetitions->first();
    $needsLevels = $nextCompetitions->first(fn ($item) => ! $item->competition_branches_count);
    $readyCompetition = $nextCompetitions->first(fn ($item) => $item->competition_branches_count && $nextUser->username && $item->competition_number);
    $nextTitle = 'ابدأ بإعداد حسابك'; $nextMessage = 'أكمل بيانات الهوية العامة حتى تتمكن من مشاركة روابط مسابقاتك.'; $nextUrl = route('profile.edit').'#username'; $nextLabel = 'إعداد الحساب'; $nextPublicUrl = null;
    if ($nextUser?->username) {
        if (! $nextCompetition) { $nextTitle = 'أنشئ أول مسابقة لك'; $nextMessage = 'ابدأ بإنشاء مسابقة قرآن كريم ثم أضف مستوياتها.'; $nextUrl = route('competitions.create'); $nextLabel = 'إنشاء مسابقة'; }
        elseif ($needsLevels) { $nextTitle = 'أكمل إعداد المسابقة'; $nextMessage = 'أضف مستويات المسابقة حتى يستطيع المتسابقون اختيار المستوى المناسب.'; $nextUrl = route('competition-branches.create', ['competition_id' => $needsLevels->id]); $nextLabel = 'إضافة مستوى'; }
        elseif ($readyCompetition) { $nextTitle = 'مسابقتك جاهزة'; $nextMessage = 'شارك رابط التسجيل مع المتسابقين.'; $nextPublicUrl = route('competitions.public-register-canonical', [$nextUser->username, $readyCompetition->competition_number]); $nextUrl = $nextPublicUrl; $nextLabel = 'فتح الرابط'; }
    }
@endphp
@if($nextUser?->role === 'User')
<section class="hafez-next-action mb-4"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h2 class="h6 mb-1">{{ $nextTitle }}</h2><p class="text-muted small mb-0">{{ $nextMessage }}</p></div><div class="d-flex flex-wrap gap-2"><a href="{{ $nextUrl }}" class="btn btn-success btn-sm">{{ $nextLabel }}</a>@if($nextPublicUrl)<button type="button" class="btn btn-outline-success btn-sm" data-copy-next-url="{{ $nextPublicUrl }}">نسخ الرابط</button>@endif</div></div>@if($nextCompetitions->count())<div class="hafez-next-action__list mt-3">@foreach($nextCompetitions as $item)<div class="hafez-next-action__competition"><span class="fw-semibold text-truncate">{{ $item->title }}</span><x-ui.status-badge :status="app(\App\Services\CompetitionStatusService::class)->displayStatus($item)" /></div>@endforeach</div>@endif</section>
@if($nextPublicUrl)<script>document.querySelector('[data-copy-next-url]')?.addEventListener('click',async function(){try{await navigator.clipboard.writeText(this.dataset.copyNextUrl)}catch(_){ }this.textContent='تم نسخ الرابط'});</script>@endif
@endif
