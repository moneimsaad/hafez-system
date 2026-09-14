<x-app-layout>
    <x-slot name="header">
        <div class="hafez-dashboard-welcome">
            <div class="hafez-dashboard-welcome__identity">
                <h1 class="h3 mb-0">مرحبًا، {{ auth()->user()->name }}</h1>
                @if(auth()->user()->role === 'User' && (auth()->user()->organization_name || auth()->user()->username))
                    <p class="small text-muted mb-0 mt-2">
                        {{ auth()->user()->organization_name }}{{ auth()->user()->organization_name && auth()->user()->username ? ' · ' : '' }}{{ auth()->user()->username ? '@'.auth()->user()->username : '' }} · منظم المسابقات
                    </p>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="container-fluid hafez-dashboard-page py-4 px-3 px-lg-4">
        @if(auth()->user()->role === 'User')
            @php
                $organizerCompetitions = auth()->user()->competitions()->withCount('competitionBranches')->get();
                $statusService = app(\App\Services\CompetitionStatusService::class);
                $organizerStatuses = $organizerCompetitions->mapWithKeys(fn ($item) => [$item->id => $statusService->displayStatus($item)]);
            @endphp
            <div class="row g-3 mb-4 hafez-dashboard-primary-stats"><div class="col-6 col-xl-3"><x-dashboard.stat-card label="إجمالي المسابقات" :value="$organizerCompetitions->count()" icon="competition" /></div><div class="col-6 col-xl-3"><x-dashboard.stat-card label="مسابقات تحتاج إعداد" :value="$organizerStatuses->filter(fn($status) => $status === 'غير جاهزة')->count()" icon="settings" /></div><div class="col-6 col-xl-3"><x-dashboard.stat-card label="مسابقات مفتوحة" :value="$organizerStatuses->filter(fn($status) => $status === 'مفتوح للتسجيل')->count()" icon="registrations" /></div><div class="col-6 col-xl-3"><x-dashboard.stat-card label="تسجيلات مفتوحة" :value="$organizerCompetitions->filter(fn($item) => $statusService->isRegistrationOpen($item))->count()" icon="registrations" /></div></div>
        @endif
        @php
        $cards = auth()->user()->role === 'Platform Admin'
        ? [['key'=>'users','label'=>'إجمالي المستخدمين','icon'=>'users'],['key'=>'competitions','label'=>'إجمالي المسابقات','icon'=>'competition'],['key'=>'students','label'=>'إجمالي الطلاب','icon'=>'students'],['key'=>'registrations','label'=>'إجمالي التسجيلات','icon'=>'registrations'],['key'=>'results','label'=>'إجمالي النتائج','icon'=>'results'],['key'=>'certificates','label'=>'إجمالي الشهادات','icon'=>'certificate']]
        : [['key'=>'branches','label'=>'مستوياتي','icon'=>'layers'],['key'=>'students','label'=>'طلابي','icon'=>'registrations'],['key'=>'registrations','label'=>'تسجيلاتي','icon'=>'registrations'],['key'=>'results','label'=>'نتائجي','icon'=>'results'],['key'=>'certificates','label'=>'شهاداتي','icon'=>'certificate']];
        @endphp
        <section class="hafez-secondary-stats mb-4" aria-label="ملخص البيانات">
                @foreach($cards as $card)
                    @php($overviewRoute = match($card['key']) { 'competitions' => 'competitions.index', 'branches' => 'competition-branches.index', 'students', 'registrations' => 'registrations.index', 'results' => 'results.index', 'certificates' => 'certificates.index', default => null })
                    @if($overviewRoute)<a class="hafez-secondary-stat" href="{{ route($overviewRoute) }}">@else<div class="hafez-secondary-stat">@endif
                        <x-ui.sidebar-icon :name="$card['icon']" class="hafez-secondary-stat__icon" />
                        <span>{{ $card['label'] }}</span><strong>{{ $statistics[$card['key']] ?? '—' }}</strong>
                    @if($overviewRoute)</a>@else</div>@endif
                @endforeach
        </section>
        @if(auth()->user()->role === 'User' && ($statistics['competitions'] ?? 0) === 0)
            <div class="alert alert-success border-0 d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="status">
                <div>
                    <h2 class="h5 mb-1">ابدأ أول مسابقة لك</h2>
                    <p class="mb-2">اتبع الخطوات التالية لإعداد المسابقة واستقبال التسجيلات.</p>
                    <ol class="small mb-0 ps-3">
                        <li>إنشاء المسابقة</li>
                        <li>إضافة مستويات المسابقة</li>
                        <li>مشاركة رابط التسجيل</li>
                    </ol>
                </div>
                <a href="{{ route('competitions.create') }}" class="btn btn-success text-nowrap">إنشاء أول مسابقة</a>
            </div>
        @endif
        <section class="hafez-quick-access" aria-labelledby="quick-access-title">
            <h2 id="quick-access-title" class="h5 mb-3">الوصول السريع</h2>
            <x-dashboard.action-buttons />
        </section>
    </div>
</x-app-layout>
