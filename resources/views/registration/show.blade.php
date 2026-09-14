<x-app-layout>
<x-slot name="header">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
<div>
<p class="small text-success fw-bold mb-1">تفاصيل التسجيل</p>
<h1 class="h3 mb-0">{{ $registration->student?->full_name }}</h1>
</div>
<a href="{{ route('registrations.index') }}" class="btn btn-outline-secondary">العودة</a>
</div>
</x-slot>
<div class="container-fluid py-4 px-3 px-lg-4">
<x-ui.alert />
<div class="row g-4">
<div class="col-12 col-lg-5">
<div class="card hafez-card border-0 h-100">
<div class="card-body p-4">
<div class="d-flex align-items-center gap-3 mb-4">
                    <span class="fw-bold text-success">{{ mb_substr($registration->student?->full_name ?? 'ط', 0, 1) }}</span>
<div>
<h2 class="h5 mb-1">ملف الطالب</h2>
<p class="text-muted mb-0">بيانات التسجيل الأساسية</p>
</div>
</div>
<dl class="mb-0">
<dt>الاسم</dt>
<dd>{{ $registration->student?->full_name }}</dd>
<dt>العمر</dt>
<dd>{{ $registration->student?->birth_date?->age }} سنة</dd>
<dt>الهاتف</dt>
<dd>{{ $registration->student?->phone }}</dd>
<dt>هاتف ولي الأمر</dt>
<dd>{{ $registration->student?->parent_phone }}</dd>
<dt>البريد</dt>
<dd>{{ $registration->student?->email ?: 'غير متوفر' }}</dd>
</dl>
</div>
</div>
</div>
<div class="col-12 col-lg-7">
<div class="card hafez-card border-0">
<div class="card-body p-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0">معلومات التسجيل</h2>
<x-ui.status-badge :status="$registration->status" />
</div>
<dl class="row mb-0">
<dt class="col-sm-4">المسابقة</dt>
<dd class="col-sm-8">{{ $registration->competition?->title }}</dd>
<dt class="col-sm-4">المستوى</dt>
<dd class="col-sm-8">{{ $registration->competitionBranch?->name }}</dd>
<dt class="col-sm-4">تاريخ التسجيل</dt>
<dd class="col-sm-8">{{ $registration->registered_at?->format('Y-m-d H:i') }}</dd>
@if($registration->rejection_reason)<dt class="col-sm-4">سبب الرفض</dt>
<dd class="col-sm-8">{{ $registration->rejection_reason }}</dd>
@endif</dl>
@if($registration->status === 'pending')<hr>
<x-ui.action-buttons>
<form method="POST" action="{{ route('registrations.approve', $registration) }}" novalidate>
@csrf<button class="btn btn-success" type="submit">اعتماد التسجيل</button>
</form>
<form method="POST" action="{{ route('registrations.reject', $registration) }}" class="flex-grow-1" novalidate>
<div class="input-group">
<input name="rejection_reason" class="form-control" placeholder="سبب الرفض" required>
@csrf<button class="btn btn-outline-danger" type="submit">رفض</button>
</div>
</form>
</x-ui.action-buttons>
@endif</div>
</div>
</div>
</div>
</div>
</x-app-layout>
