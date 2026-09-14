<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">إدارة المنصة</p>
                <h1 class="h3 mb-0">مسابقاتي</h1>
            </div>
            <a href="{{ route('competitions.create') }}" class="btn btn-success">إنشاء مسابقة</a>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        @if(auth()->user()->role === 'User' && !auth()->user()->username)
            @php
                $legacyCount = auth()->user()->competitions()->count();
            @endphp
            <div class="alert alert-warning border-0 d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="status">
                <div><strong class="d-block">أكمل إعداد حسابك للحصول على روابط تسجيل احترافية لمسابقاتك</strong><span class="small">إضافة اسم مستخدم ستسمح بإنشاء روابط عامة مثل <span dir="ltr">/competitions/username/1/register</span>. @if($legacyCount) لديك {{ $legacyCount }} مسابقات تستخدم الروابط القديمة.@endif</span></div>
                <a href="{{ route('profile.edit') }}#username" class="btn btn-warning text-nowrap">تحديث إعدادات الحساب</a>
            </div>
        @endif
        <div class="hafez-dashboard-intro mb-4">أنشئ مسابقاتك وتابع مواعيد التسجيل والاختبارات من مكان واحد.</div>
        <x-ui.alert />
        <x-ui.search-filter placeholder="البحث في مسابقاتي" />
        @if($competitions->count())
        <div class="d-none d-md-block"><x-ui.data-table :headers="['المسابقة', 'الحالة', 'المستويات', 'الجاهزية', 'التسجيل', 'الاختبارات', 'الإجراءات']">
            @foreach($competitions as $competition)
                @php
                    $publicUrl = $competition->creator?->username && $competition->competition_number
                        ? route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number])
                        : route('competitions.public-register', $competition);
                @endphp
                <tr>
                <td class="fw-semibold">
                    <div>{{ $competition->title }}</div>
                </td>
                <td>
                    <x-ui.status-badge :status="$competition->display_status" />
                    <div class="small text-muted mt-1">{{ $competition->publication_scope === 'nationwide' ? 'جميع المحافظات' : ($competition->publication_scope === 'governorate' ? 'محافظة '.$competition->target_governorate : 'خاص بالرابط') }}</div>
                </td>
                <td>{{ $competition->competition_branches_count }}</td>
                <td><span class="badge text-bg-{{ $competition->registration_ready ? 'success' : 'secondary' }}">{{ $competition->registration_ready ? 'جاهزة' : 'تحتاج إعداد' }}</span>@if(!$competition->competition_branches_count)<div class="small text-muted mt-1">لا توجد مستويات</div>@elseif(!$competition->creator?->username)<div class="small text-muted mt-1">الرابط العام غير مكتمل</div>@elseif(!$competition->registration_ready)<div class="small text-muted mt-1">التسجيل غير مضبوط</div>@endif</td>
                <td>{{ $competition->registration_start_date?->format('Y-m-d H:i') }}<br>
                    <span class="text-muted small">إلى {{ $competition->registration_end_date?->format('Y-m-d H:i') }}</span>
                </td>
                <td>{{ $competition->exam_start_date?->format('Y-m-d H:i') }}<br>
                    <span class="text-muted small">إلى {{ $competition->exam_end_date?->format('Y-m-d H:i') }}</span>
                </td>
                <td>
                    <x-ui.action-buttons>
                        <a href="{{ route('competitions.show', $competition) }}" class="btn btn-sm btn-outline-success">إدارة المسابقة</a>
                        <a href="{{ route('competitions.edit', $competition) }}" class="btn btn-sm btn-outline-secondary">تعديل</a>
                        @if($competition->registration_ready && app(\App\Services\CompetitionStatusService::class)->isRegistrationOpen($competition))
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">فتح التسجيل العام</a>
                        @else
                            <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">التسجيل مغلق</span>
                        @endif
                    </x-ui.action-buttons>
                </td>
            </tr>
            @endforeach
        </x-ui.data-table></div>
        <div class="competition-mobile-list d-md-none">
            @foreach($competitions as $competition)
                @php($publicUrl = $competition->creator?->username && $competition->competition_number ? route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number]) : route('competitions.public-register', $competition))
                <article class="competition-mobile-card">
                    <div class="d-flex justify-content-between align-items-start gap-2"><h2 class="h6 mb-0">{{ $competition->title }}</h2><x-ui.status-badge :status="$competition->display_status" /></div>
                    <p class="small text-muted mt-2 mb-3">{{ $competition->publication_scope === 'nationwide' ? 'جميع المحافظات' : ($competition->publication_scope === 'governorate' ? 'محافظة '.$competition->target_governorate : 'خاص بالرابط') }}</p>
                    <dl class="competition-mobile-card__details"><div><dt>المستويات</dt><dd>{{ $competition->competition_branches_count }}</dd></div><div><dt>الجاهزية</dt><dd>{{ $competition->registration_ready ? 'جاهزة' : 'تحتاج إعداد' }}</dd></div><div><dt>التسجيل</dt><dd>{{ $competition->registration_start_date?->format('Y-m-d H:i') }} — {{ $competition->registration_end_date?->format('Y-m-d H:i') }}</dd></div><div><dt>الاختبارات</dt><dd>{{ $competition->exam_start_date?->format('Y-m-d H:i') }} — {{ $competition->exam_end_date?->format('Y-m-d H:i') }}</dd></div></dl>
                    <div class="d-grid gap-2"><a href="{{ route('competitions.show', $competition) }}" class="btn btn-outline-success">إدارة المسابقة</a><a href="{{ route('competitions.edit', $competition) }}" class="btn btn-outline-secondary">تعديل</a>@if($competition->registration_ready && app(\App\Services\CompetitionStatusService::class)->isRegistrationOpen($competition))<a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-success">فتح التسجيل العام</a>@else<span class="btn btn-outline-secondary disabled" aria-disabled="true">التسجيل مغلق</span>@endif</div>
                </article>
            @endforeach
        </div>
        <x-ui.pagination :paginator="$competitions" />
        @else
        <x-ui.empty-state message="لا توجد مسابقات حتى الآن" :action="route('competitions.create')" action-label="إنشاء أول مسابقة" />
        @endif
    </div>
</x-app-layout>
