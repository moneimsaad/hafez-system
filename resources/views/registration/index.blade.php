<x-app-layout>
<x-slot name="header">
<div>
<p class="small text-success fw-bold mb-1">إدارة المشاركين</p>
<h1 class="h3 mb-0">تسجيلات الطلاب</h1>
</div>
</x-slot>
<div class="container-fluid py-4 px-3 px-lg-4">
<div class="hafez-dashboard-intro mb-4">راجع طلبات التسجيل المرتبطة بمسابقاتك وتابع حالتها.</div>
<x-ui.alert />
<div class="row g-3 mb-4">
@foreach([['label'=>'إجمالي التسجيلات','key'=>'total','class'=>'success'],['label'=>'بانتظار المراجعة','key'=>'pending','class'=>'warning'],['label'=>'تم القبول','key'=>'approved','class'=>'success'],['label'=>'تم الرفض','key'=>'rejected','class'=>'danger']] as $card)
<div class="col-6 col-xl-3"><div class="card hafez-stat-card border-0 h-100"><div class="card-body"><p class="small text-muted mb-2">{{ $card['label'] }}</p><p class="h3 mb-0">{{ $summary[$card['key']] }}</p></div></div></div>
@endforeach
</div>
<x-ui.search-filter placeholder="البحث بالاسم أو رقم التسجيل أو الهاتف">
<div class="col-12 col-md-3">
<label class="form-label" for="competition_id">المسابقة</label>
<select id="competition_id" name="competition_id" class="form-select">
<option value="">كل المسابقات</option>
@foreach($competitions as $competition)
<option value="{{ $competition->id }}" @selected((string) request('competition_id') === (string) $competition->id)>{{ $competition->title }}</option>
@endforeach
</select>
</div>
<div class="col-12 col-md-3">
<label class="form-label" for="branch_id">المستوى</label>
<select id="branch_id" name="branch_id" class="form-select">
<option value="">كل المستويات</option>
@foreach($branches as $branch)
<option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }} — {{ $branch->competition?->title }}</option>
@endforeach
</select>
</div>
<div class="col-12 col-md-3">
<label class="form-label" for="status">الحالة</label>
<select id="status" name="status" class="form-select">
<option value="">كل الحالات</option>
<option value="pending" @selected(request('status') === 'pending')>معلق</option>
<option value="approved" @selected(request('status') === 'approved')>مقبول</option>
<option value="rejected" @selected(request('status') === 'rejected')>مرفوض</option>
</select>
</div>
<div class="col-12 col-md-3"><label class="form-label" for="date_from">من تاريخ التسجيل</label><input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}"></div>
<div class="col-12 col-md-3"><label class="form-label" for="date_to">إلى تاريخ التسجيل</label><input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}"></div>
</x-ui.search-filter>
@if(request()->hasAny(['search', 'competition_id', 'branch_id', 'status', 'date_from', 'date_to']))
<div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="الفلاتر النشطة">
<span class="small text-muted">الفلاتر النشطة:</span>
@if(request('search'))<span class="badge text-bg-light border">البحث: {{ request('search') }}</span>@endif
@if(request('competition_id'))<span class="badge text-bg-light border">المسابقة محددة</span>@endif
@if(request('branch_id'))<span class="badge text-bg-light border">المستوى محدد</span>@endif
@if(request('status'))<span class="badge text-bg-light border">الحالة: {{ request('status') }}</span>@endif
@if(request('date_from'))<span class="badge text-bg-light border">من: {{ request('date_from') }}</span>@endif
@if(request('date_to'))<span class="badge text-bg-light border">إلى: {{ request('date_to') }}</span>@endif
</div>
@endif
@if($registrations->count())
<div id="bulk-action-bar" class="card hafez-card border-0 mb-3 d-none"><div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2"><span><strong data-selection-count>0</strong> طلبات محددة</span><div class="d-flex gap-2"><button type="button" class="btn btn-success btn-sm" data-bulk-action="approve">قبول المحدد</button><button type="button" class="btn btn-outline-danger btn-sm" data-bulk-action="reject">رفض المحدد</button></div></div></div>
<div class="d-none d-md-block"><div class="table-responsive hafez-table-wrap" role="region" aria-label="جدول التسجيلات"><table class="table table-hover align-middle mb-0"><thead><tr><th scope="col"><input class="form-check-input" type="checkbox" id="select-all-registrations" data-select-all-registrations aria-label="تحديد كل الطلبات القابلة للمراجعة في هذه الصفحة"></th><th scope="col">رقم التسجيل</th><th scope="col">الطالب</th><th scope="col">بيانات التواصل</th><th scope="col">المسابقة</th><th scope="col">المستوى</th><th scope="col">الحالة</th><th scope="col">تاريخ التسجيل</th><th scope="col">الإجراءات</th></tr></thead><tbody>
@foreach($registrations as $registration)<tr class="registration-row" data-registration-url="{{ route('registrations.show', $registration) }}">
<td><input class="form-check-input registration-select" type="checkbox" name="registration_ids[]" value="{{ $registration->id }}" form="bulk-review-form" aria-label="تحديد طلب {{ $registration->registration_number }}" @disabled($registration->status !== 'pending')></td>
<td class="fw-semibold">{{ $registration->registration_number }}</td>
<td class="fw-semibold">{{ $registration->student?->full_name }}</td>
<td>{{ $registration->student?->phone }}@if($registration->student?->parent_phone)<br>
<span class="small text-muted">ولي الأمر: {{ $registration->student->parent_phone }}</span>
@endif</td>
<td>{{ $registration->competition?->title }}</td>
<td>{{ $registration->competitionBranch?->name }}</td>
<td>
<x-ui.status-badge :status="$registration->status" />
</td>
<td>{{ $registration->registered_at?->format('Y-m-d H:i') }}</td>
<td>
@if($registration->status === 'pending')
<form method="POST" action="{{ route('registrations.approve', $registration) }}" class="d-inline" onsubmit="return confirm('هل تريد قبول طلب التسجيل؟')">@csrf<button class="btn btn-sm btn-success" type="submit">قبول</button></form>
<button class="btn btn-sm btn-outline-danger" type="button" data-single-reject-url="{{ route('registrations.reject', $registration) }}" data-registration-label="{{ $registration->registration_number }}" data-bs-toggle="modal" data-bs-target="#reject-registration-modal">رفض</button>
@endif
</td>
</tr>
@endforeach</tbody></table></div></div>
<div class="registration-mobile-list d-md-none"><label class="registration-mobile-select-all"><input class="form-check-input" type="checkbox" data-select-all-registrations> تحديد كل الطلبات القابلة للمراجعة في هذه الصفحة</label>@foreach($registrations as $registration)<article class="registration-mobile-card registration-row" data-registration-url="{{ route('registrations.show', $registration) }}"><div class="registration-mobile-card__header"><div class="registration-mobile-card__identity">@if($registration->status === 'pending')<label class="registration-mobile-card__check" title="تحديد الطلب"><input class="form-check-input registration-select" type="checkbox" name="registration_ids[]" value="{{ $registration->id }}" form="bulk-review-form" aria-label="تحديد طلب {{ $registration->registration_number }}"></label>@endif<h2 class="h6 mb-0" title="{{ $registration->student?->full_name }}">{{ $registration->student?->full_name }}</h2></div><x-ui.status-badge :status="$registration->status" /></div><div class="registration-mobile-card__number">رقم التسجيل: <span dir="ltr">{{ $registration->registration_number }}</span></div><dl><div><dt>المسابقة</dt><dd title="{{ $registration->competition?->title }}">{{ $registration->competition?->title }}</dd></div><div><dt>المستوى</dt><dd title="{{ $registration->competitionBranch?->name }}">{{ $registration->competitionBranch?->name }}</dd></div><div><dt>الهاتف</dt><dd dir="ltr">{{ $registration->student?->phone }}</dd></div><div><dt>التاريخ</dt><dd dir="ltr">{{ $registration->registered_at?->format('Y-m-d') }}</dd></div></dl><div class="registration-mobile-card__actions">@if($registration->status === 'pending')<form method="POST" action="{{ route('registrations.approve', $registration) }}">@csrf<button class="btn btn-sm btn-success">قبول</button></form><button class="btn btn-sm btn-outline-danger" type="button" data-single-reject-url="{{ route('registrations.reject', $registration) }}" data-registration-label="{{ $registration->registration_number }}" data-bs-toggle="modal" data-bs-target="#reject-registration-modal">رفض</button>@endif<a class="btn btn-sm btn-outline-secondary" href="{{ route('registrations.show', $registration) }}">عرض التفاصيل</a></div></article>@endforeach</div>
<x-ui.pagination :paginator="$registrations" />
@else
    @if(request()->hasAny(['search', 'competition_id', 'branch_id', 'status', 'date_from', 'date_to']))
        <x-ui.empty-state message="لا توجد نتائج مطابقة للفلاتر الحالية" :action="url()->current()" action-label="مسح الفلاتر" />
    @else
        <x-ui.empty-state message="لم يتم تسجيل أي طلاب حتى الآن" :action="route('competitions.index')" action-label="عرض مسابقاتي ومشاركة الرابط" />
    @endif
@endif</div>
<form id="bulk-review-form" method="POST" action="{{ route('registrations.bulk-review', request()->only(['search', 'competition_id', 'branch_id', 'status', 'date_from', 'date_to', 'page'])) }}">@csrf<input type="hidden" name="action" id="bulk-action"></form>
<div class="modal fade" id="reject-registration-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" id="reject-registration-form" class="modal-content">@csrf<div class="modal-header"><h2 class="modal-title fs-5">رفض طلب التسجيل</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body"><p class="text-muted small" id="single-reject-description"></p><label class="form-label" for="single-rejection-reason">سبب الرفض *</label><textarea id="single-rejection-reason" name="rejection_reason" class="form-control" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-danger">تأكيد الرفض</button></div></form></div></div>
<div class="modal fade" id="bulk-reject-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5">رفض الطلبات المحددة</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body"><p class="text-muted">سيتم تسجيل السبب نفسه لكل طلب محدد.</p><label class="form-label" for="bulk-rejection-reason">سبب الرفض *</label><textarea id="bulk-rejection-reason" class="form-control" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button><button type="button" class="btn btn-danger" id="confirm-bulk-reject">رفض الطلبات</button></div></div></div></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const c=[...document.querySelectorAll('.registration-select')],a=[...document.querySelectorAll('[data-select-all-registrations]')],b=document.getElementById('bulk-action-bar'),n=document.querySelector('[data-selection-count]'),f=document.getElementById('bulk-review-form'),r=()=>{const s=c.filter(x=>x.checked);b?.classList.toggle('d-none',!s.length);if(n)n.textContent=s.length;a.forEach(x=>x.checked=c.length>0&&s.length===c.length)};a.forEach(x=>x.addEventListener('change',()=>{c.forEach(y=>y.checked=x.checked);r()}));c.forEach(x=>x.addEventListener('change',r));document.querySelectorAll('.registration-row').forEach(x=>x.addEventListener('click',e=>{if(e.target.closest('a,button,input,select,textarea,label,form'))return;window.location=x.dataset.registrationUrl}));document.querySelectorAll('[data-single-reject-url]').forEach(x=>x.addEventListener('click',()=>{document.getElementById('reject-registration-form').action=x.dataset.singleRejectUrl;document.getElementById('single-reject-description').textContent=`طلب التسجيل رقم ${x.dataset.registrationLabel}`}));document.querySelectorAll('[data-bulk-action]').forEach(x=>x.addEventListener('click',()=>{const s=c.filter(y=>y.checked);if(!s.length)return;if(x.dataset.bulkAction==='approve'){if(confirm(`هل تريد قبول ${s.length} طلبات تسجيل محددة؟`)){document.getElementById('bulk-action').value='approve';f.submit()}}else bootstrap.Modal.getOrCreateInstance(document.getElementById('bulk-reject-modal')).show()}));document.getElementById('confirm-bulk-reject')?.addEventListener('click',()=>{const x=document.getElementById('bulk-rejection-reason');if(!x.reportValidity())return;let h=f.querySelector('[name="rejection_reason"]');if(!h){h=document.createElement('input');h.type='hidden';h.name='rejection_reason';f.append(h)}h.value=x.value;document.getElementById('bulk-action').value='reject';f.submit()});r()});</script><style>.registration-row{cursor:pointer}.registration-row:hover>td{background-color:rgba(25,135,84,.06)}</style>
</x-app-layout>
