@php
    $workspaceLinks = [
        ['label' => 'لوحة التحكم', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'dashboard'],
        ['label' => auth()->user()?->role === 'User' ? 'مسابقاتي' : 'المسابقات', 'route' => 'competitions.index', 'match' => 'competitions.*', 'icon' => 'competition'],
        ['label' => auth()->user()?->role === 'User' ? 'مستويات مسابقاتي' : 'مستويات المسابقة', 'route' => 'competition-branches.index', 'match' => 'competition-branches.*', 'icon' => 'layers'],
        ['label' => 'التسجيلات', 'route' => 'registrations.index', 'match' => 'registrations.*', 'icon' => 'registrations'],
    ];
    $managementLinks = [
        ['label' => 'اللجان', 'route' => 'committees.index', 'match' => 'committees.*', 'icon' => 'committees'],
        ['label' => 'التقييمات', 'route' => 'evaluations.index', 'match' => 'evaluations.*', 'icon' => 'evaluation'],
        ['label' => 'النتائج', 'route' => 'results.index', 'match' => 'results.*', 'icon' => 'results'],
        ['label' => 'الشهادات', 'route' => 'certificates.index', 'match' => 'certificates.*', 'icon' => 'certificate'],
    ];
@endphp
<nav class="hafez-sidebar-nav" aria-label="التنقل الرئيسي">
    <div class="hafez-sidebar-group">
        <div class="hafez-sidebar-section">مساحة العمل</div>
        @foreach($workspaceLinks as $link)
            @if(Route::has($link['route']))<a class="hafez-nav-link {{ request()->routeIs($link['match']) ? 'active' : '' }}" href="{{ route($link['route']) }}" aria-label="{{ $link['label'] }}" @if(request()->routeIs($link['match'])) aria-current="page" @endif><x-ui.sidebar-icon :name="$link['icon']" /><span>{{ $link['label'] }}</span></a>@endif
        @endforeach
    </div>
    <div class="hafez-sidebar-group">
        <div class="hafez-sidebar-section">إدارة المسابقة</div>
        @foreach($managementLinks as $link)
            @if(Route::has($link['route']))<a class="hafez-nav-link {{ request()->routeIs($link['match']) ? 'active' : '' }}" href="{{ route($link['route']) }}" aria-label="{{ $link['label'] }}" @if(request()->routeIs($link['match'])) aria-current="page" @endif><x-ui.sidebar-icon :name="$link['icon']" /><span>{{ $link['label'] }}</span></a>@endif
        @endforeach
    </div>
    @if(Route::has('reports.index'))
        <div class="hafez-sidebar-group">
            <div class="hafez-sidebar-section">التقارير</div>
            <a class="hafez-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif><x-ui.sidebar-icon name="report" /><span>التقارير</span></a>
        </div>
    @endif
    @if(auth()->user()?->role === 'Platform Admin' && Route::has('users.index'))
        <div class="hafez-sidebar-group">
        <div class="hafez-sidebar-section">الإدارة</div>
        <a class="hafez-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" aria-label="المستخدمون" @if(request()->routeIs('users.*')) aria-current="page" @endif><x-ui.sidebar-icon name="users" /><span>المستخدمون</span></a>
        @if(Route::has('platform-admin.settings.edit'))
            <a class="hafez-nav-link {{ request()->routeIs('platform-admin.settings.*') ? 'active' : '' }}" href="{{ route('platform-admin.settings.edit') }}" aria-label="إعدادات المنصة" @if(request()->routeIs('platform-admin.settings.*')) aria-current="page" @endif><x-ui.sidebar-icon name="settings" /><span>إعدادات المنصة</span></a>
        @endif
        </div>
    @endif
    @if(auth()->user()?->role === 'User' && Route::has('profile.edit'))
        <div class="hafez-sidebar-group hafez-sidebar-group--account">
            <div class="hafez-sidebar-section">الحساب</div>
            <a class="hafez-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" aria-label="إعدادات الحساب" @if(request()->routeIs('profile.*')) aria-current="page" @endif><x-ui.sidebar-icon name="settings" /><span>إعدادات الحساب</span></a>
        </div>
    @endif
</nav>
