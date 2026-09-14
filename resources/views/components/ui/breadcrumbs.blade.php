@php
    $items = [
        ['match' => 'dashboard', 'label' => 'لوحة التحكم', 'route' => 'dashboard'],
        ['match' => 'competitions.*', 'label' => auth()->user()?->role === 'User' ? 'مسابقاتي' : 'المسابقات', 'route' => 'competitions.index'],
        ['match' => 'competition-branches.*', 'label' => auth()->user()?->role === 'User' ? 'مستويات مسابقاتي' : 'مستويات المسابقة', 'route' => 'competition-branches.index'],
        ['match' => 'registrations.*', 'label' => 'تسجيلات الطلاب', 'route' => 'registrations.index'],
        ['match' => 'committees.*', 'label' => 'اللجان', 'route' => 'committees.index'],
        ['match' => 'evaluations.*', 'label' => 'التقييمات', 'route' => 'evaluations.index'],
        ['match' => 'results.*', 'label' => 'النتائج', 'route' => 'results.index'],
        ['match' => 'certificates.*', 'label' => 'الشهادات', 'route' => 'certificates.index'],
        ['match' => 'reports.*', 'label' => 'التقارير', 'route' => 'reports.index'],
        ['match' => 'users.*', 'label' => 'المستخدمون', 'route' => 'users.index'],
        ['match' => 'platform-admin.settings.*', 'label' => 'إعدادات المنصة', 'route' => 'platform-admin.settings.edit'],
        ['match' => 'profile.*', 'label' => 'الملف الشخصي', 'route' => 'profile.edit'],
    ];
    $current = collect($items)->first(fn ($item) => request()->routeIs($item['match']));
    $suffix = request()->routeIs('*.create') ? 'إنشاء' : (request()->routeIs('*.edit') ? 'تعديل' : (request()->routeIs('*.show') ? 'تفاصيل' : null));
@endphp

@if($current)
    <nav class="hafez-breadcrumbs" aria-label="مسار الصفحة">
        <ol class="breadcrumb mb-0">
            @if($current['route'] !== 'dashboard' && Route::has('dashboard'))
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">لوحة التحكم</a></li>
            @endif
            @if($suffix && $current['route'] !== 'dashboard')
                <li class="breadcrumb-item"><a href="{{ route($current['route']) }}">{{ $current['label'] }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $suffix }}</li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $current['label'] }}</li>
            @endif
        </ol>
    </nav>
@endif
