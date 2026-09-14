<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">إدارة المسابقات</p>
                <h1 class="h3 mb-0">مستويات مسابقاتي</h1>
            </div>
            <a href="{{ route('competition-branches.create') }}" class="btn btn-success">إنشاء مستوى جديد</a>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro hafez-levels-intro mb-4">مستوياتك القابلة لإعادة الاستخدام في مسابقاتك القادمة.</div>
        <x-ui.alert />
        <x-ui.search-filter placeholder="البحث في مستويات المسابقة" />
        @if($levels->count())<div class="d-none d-md-block"><x-ui.data-table class="hafez-levels-table" :headers="['المستوى','مقدار الحفظ','العمر الافتراضي','الدرجات','الاستخدام','الحالة','الإجراءات']">
            @foreach($levels as $level)
                <tr>
                    <td class="hafez-levels-table__identity"><a href="{{ route('competition-branches.edit', $level) }}" class="hafez-levels-table__name">{{ $level->name }}</a><span class="hafez-levels-table__description" title="{{ $level->description }}">{{ $level->description ?: 'بدون وصف' }}</span></td>
                    <td class="text-nowrap">{{ $level->memorization_amount }}</td>
                    <td class="text-nowrap"><span class="hafez-levels-table__label">العمر</span>{{ $level->default_min_age !== null && $level->default_max_age !== null ? $level->default_min_age.' – '.$level->default_max_age.' سنة' : ($level->default_min_age !== null ? 'من '.$level->default_min_age.' سنوات' : ($level->default_max_age !== null ? 'حتى '.$level->default_max_age.' سنة' : 'غير محدد')) }}</td>
                    <td class="hafez-levels-table__scores text-nowrap">@if($level->default_total_score !== null && $level->default_passing_score !== null)<span>الكلية: <strong dir="ltr">{{ rtrim(rtrim(number_format((float) $level->default_total_score, 2, '.', ''), '0'), '.') }}</strong></span><span>النجاح: <strong dir="ltr">{{ rtrim(rtrim(number_format((float) $level->default_passing_score, 2, '.', ''), '0'), '.') }}</strong></span>@else<span>غير محدد</span>@endif</td>
                    <td class="text-nowrap">{{ $level->assignments_count === 0 ? 'غير مستخدم' : 'مستخدم في '.$level->assignments_count.' '.($level->assignments_count === 1 ? 'مسابقة' : 'مسابقات') }}</td>
                    <td><x-ui.status-badge :status="$level->status" /></td>
                    <td><x-ui.action-buttons><a href="{{ route('competition-branches.edit', $level) }}" class="btn btn-sm btn-outline-secondary">تعديل</a><form method="POST" action="{{ route('competition-branches.destroy', $level) }}" data-confirm="{{ $level->assignments_count > 0 ? 'هذا المستوى مستخدم في مسابقات سابقة. سيتم حذفه من مستوياتك المتاحة مع الاحتفاظ بالبيانات التاريخية للمسابقات.' : 'هل أنت متأكد من حذف هذا المستوى؟' }}" onsubmit="return window.confirm(this.dataset.confirm)">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف</button></form></x-ui.action-buttons></td>
                </tr>
            @endforeach
        </x-ui.data-table></div>
        <div class="level-mobile-list d-md-none">
            @foreach($levels as $level)
                <article class="level-mobile-card"><div class="d-flex justify-content-between align-items-start gap-2"><div><h2 class="h6 mb-1">{{ $level->name }}</h2><p class="small text-muted mb-0">{{ $level->description ?: 'بدون وصف' }}</p></div><x-ui.status-badge :status="$level->status" /></div><dl class="level-mobile-card__details"><div><dt>مقدار الحفظ</dt><dd>{{ $level->memorization_amount }}</dd></div><div><dt>العمر الافتراضي</dt><dd>{{ $level->default_min_age !== null && $level->default_max_age !== null ? $level->default_min_age.' – '.$level->default_max_age.' سنة' : ($level->default_min_age !== null ? 'من '.$level->default_min_age.' سنوات' : ($level->default_max_age !== null ? 'حتى '.$level->default_max_age.' سنة' : 'غير محدد')) }}</dd></div><div><dt>الدرجات</dt><dd>@if($level->default_total_score !== null && $level->default_passing_score !== null)الكلية: {{ $level->default_total_score }} · النجاح: {{ $level->default_passing_score }}@else غير محدد @endif</dd></div><div><dt>الاستخدام</dt><dd>{{ $level->assignments_count === 0 ? 'غير مستخدم' : 'مستخدم في '.$level->assignments_count.' '.($level->assignments_count === 1 ? 'مسابقة' : 'مسابقات') }}</dd></div></dl><div class="d-grid gap-2"><a href="{{ route('competition-branches.edit', $level) }}" class="btn btn-outline-secondary">تعديل</a><form method="POST" action="{{ route('competition-branches.destroy', $level) }}" data-confirm="{{ $level->assignments_count > 0 ? 'هذا المستوى مستخدم في مسابقات سابقة. سيتم حذفه من مستوياتك المتاحة مع الاحتفاظ بالبيانات التاريخية للمسابقات.' : 'هل أنت متأكد من حذف هذا المستوى؟' }}" onsubmit="return window.confirm(this.dataset.confirm)">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100">حذف</button></form></div></article>
            @endforeach
        </div>
        <x-ui.pagination :paginator="$levels" />
        @else<x-ui.empty-state message="لا توجد مستويات حتى الآن" :action="route('competition-branches.create')" action-label="إضافة أول مستوى" />
        @endif
    </div>
</x-app-layout>
