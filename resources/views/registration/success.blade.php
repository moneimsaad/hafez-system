<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تم التسجيل | Hafez System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hafez-auth-shell">
    <main class="container py-4 py-lg-5">
        <div class="row justify-content-center"><div class="col-12 col-xl-9">
            <div class="text-center mb-4"><div class="text-success fs-1 mb-3" aria-hidden="true">✓</div><h1 class="h2 text-success mb-2">تم تسجيل طلبك بنجاح</h1><p class="text-muted mb-0">تم استلام بيانات الطالب، وسيتم مراجعة الطلب من الجهة المنظمة.</p></div>
            <div class="card hafez-card border-0 mb-4"><div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3"><div><p class="small text-success fw-bold mb-1">رقم التسجيل</p><p class="h2 mb-0 font-monospace" dir="ltr">{{ $registration->registration_number }}</p></div><button type="button" class="btn btn-outline-success" data-copy-registration="{{ $registration->registration_number }}">نسخ الرقم</button></div>
                <p class="small text-muted mb-0">احتفظ بهذا الرقم لمتابعة حالة التسجيل.</p>
                <div class="alert alert-success mt-3 mb-0 d-none" data-copy-feedback>تم نسخ رقم التسجيل.</div>
            </div></div>
            @php($competition = $registration->competition)
            <div class="card hafez-card border-0 mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">ملخص التسجيل</h2><dl class="row mb-0">
                <dt class="col-sm-4 text-muted">الطالب</dt><dd class="col-sm-8">{{ $registration->student?->full_name ?: 'بيانات الطالب المسجلة' }}</dd>
                <dt class="col-sm-4 text-muted">المسابقة</dt><dd class="col-sm-8">{{ $competition?->title }}</dd>
                <dt class="col-sm-4 text-muted">الجهة المنظمة</dt><dd class="col-sm-8">{{ $competition?->creator?->organization_name ?: $competition?->creator?->name ?: 'الجهة المنظمة' }}</dd>
                <dt class="col-sm-4 text-muted">مستوى المسابقة</dt><dd class="col-sm-8">{{ $registration->competitionBranch?->name ?: '—' }}</dd>
                <dt class="col-sm-4 text-muted">تاريخ التسجيل</dt><dd class="col-sm-8"><span dir="ltr">{{ optional($registration->registered_at)->format('Y-m-d H:i') }}</span></dd>
            </dl></div></div>
            <div class="card hafez-card border-0 mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">ما الخطوة التالية؟</h2><ol class="mb-0 ps-3"><li class="mb-2">ستراجع الجهة المنظمة طلب التسجيل والبيانات المرسلة.</li><li class="mb-2">استخدم رقم التسجيل لمتابعة حالة الطلب.</li><li>عند قبول الطلب، ستظهر لك الإجراءات التالية في صفحة المتابعة.</li></ol></div></div>
            <div class="d-grid d-sm-flex justify-content-center gap-2"><a href="{{ route('registrations.status') }}" class="btn btn-success">متابعة حالة التسجيل</a>@if ($competition?->creator?->username && $competition?->competition_number)<a href="{{ route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number]) }}" class="btn btn-outline-success">تسجيل طالب آخر</a>@elseif ($competition)<a href="{{ route('competitions.public-register', $competition) }}" class="btn btn-outline-success">تسجيل طالب آخر</a>@endif</div>
        </div></div>
    </main>
<script>document.querySelector('[data-copy-registration]')?.addEventListener('click',async function(){try{await navigator.clipboard.writeText(this.dataset.copyRegistration);const f=document.querySelector('[data-copy-feedback]');f?.classList.remove('d-none');setTimeout(()=>f?.classList.add('d-none'),2500)}catch(e){}});</script>
</body>
</html>
