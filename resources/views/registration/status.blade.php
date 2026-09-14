<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>متابعة التسجيل | Hafez System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hafez-registration-status-page">
<main class="hafez-registration-status-shell" aria-labelledby="registration-status-title">
    <section class="hafez-registration-status-card">
        <div class="hafez-registration-status-brand" aria-label="Hafez System">Hafez System</div>
        <p class="hafez-registration-status-kicker">منصة إدارة مسابقات حفظ القرآن</p>
        <h1 id="registration-status-title" class="hafez-registration-status-title">متابعة تسجيل الطالب</h1>
        <p class="hafez-registration-status-description">أدخل رقم التسجيل لمعرفة آخر حالة للطلب.</p>

        <div class="hafez-registration-status-form-area">
            <x-ui.validation-errors />

            <form method="POST" action="{{ route('registrations.status.search') }}" novalidate>
                @csrf
                <label class="form-label hafez-registration-status-label" for="registration_number">رقم التسجيل</label>
                <p class="hafez-registration-status-example" id="registration-format">
                    يتكوّن الرقم من السنة وخمسة أرقام، مثل <span dir="ltr">2026-56455</span>
                </p>
                <input class="form-control form-control-lg hafez-registration-status-input @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" placeholder="2026-56455" required inputmode="numeric" dir="ltr" aria-describedby="registration-format registration-help">
                @error('registration_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div id="registration-help" class="form-text hafez-registration-status-help">استخدم الرقم الذي ظهر لك بعد إرسال طلب التسجيل.</div>
                <button class="btn btn-success hafez-registration-status-submit" type="submit">متابعة الحالة</button>
            </form>
        </div>

        @if(request()->isMethod('post'))
            @if($registration)
                @php($status = ['pending' => ['قيد المراجعة', 'warning'], 'approved' => ['مقبول', 'success'], 'rejected' => ['مرفوض', 'danger']][$registration->status] ?? [$registration->status, 'secondary'])
                <section class="hafez-registration-status-result" aria-label="نتيجة البحث">
                    <div class="hafez-registration-status-result-heading">
                        <span>نتيجة البحث</span>
                        <span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span>
                    </div>
                    <dl class="hafez-registration-status-details">
                        <div><dt>رقم التسجيل</dt><dd><strong dir="ltr">{{ $registration->registration_number }}</strong></dd></div>
                        <div><dt>الطالب</dt><dd>{{ $registration->student?->full_name ?: '—' }}</dd></div>
                        <div><dt>المسابقة</dt><dd>{{ $registration->competition?->title }}</dd></div>
                        <div><dt>مستوى المسابقة</dt><dd>{{ $registration->competitionBranch?->name }}</dd></div>
                        <div><dt>تاريخ التسجيل</dt><dd><span dir="ltr">{{ optional($registration->registered_at)->format('Y-m-d') }}</span></dd></div>
                    </dl>
                    @if($registration->status === 'rejected')
                        <div class="alert alert-danger hafez-registration-status-rejection mb-0"><strong>سبب الرفض:</strong> {{ $registration->rejection_reason ?: 'لم يتم تسجيل سبب تفصيلي.' }}</div>
                    @endif
                </section>
            @else
                <div class="alert alert-warning hafez-registration-status-not-found mb-0" role="status">لم يتم العثور على تسجيل بهذا الرقم. راجع الرقم وحاول مرة أخرى.</div>
            @endif
        @endif

        <footer class="hafez-registration-status-footer"><a href="{{ route('registrations.create') }}">العودة إلى تسجيل طالب</a></footer>
    </section>
</main>
</body>
</html>
