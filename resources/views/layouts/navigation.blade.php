<header class="hafez-topbar">
    <div class="container-fluid d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <button class="btn hafez-mobile-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="فتح القائمة">القائمة</button>
            <a class="d-flex align-items-center gap-2 hafez-topbar-brand" href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}">
                <span>{{ app(\App\Services\PlatformSettingsService::class)->get('platform.name', 'Hafez System') }}<small>إدارة مسابقات حفظ القرآن</small></span>
            </a>
        </div>
        @auth
        <div class="hafez-user-chip">
            <div class="d-none d-sm-block text-end">
                <a class="hafez-profile-link d-block" href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a>
                <small class="text-muted">{{ auth()->user()->role === 'Platform Admin' ? 'مدير المنصة' : 'منظم المسابقات' }}</small>
            </div>
            <span class="hafez-user-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary" type="submit">تسجيل الخروج</button>
            </form>
        </div>
        @endauth
    </div>
</header>
<div class="offcanvas offcanvas-start hafez-mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-label="القائمة">
    <div class="offcanvas-header hafez-mobile-sidebar__header">
        <h5 class="offcanvas-title">التنقل</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button>
    </div>
    <div class="offcanvas-body hafez-mobile-sidebar__body">@include('layouts.sidebar')</div>
</div>
