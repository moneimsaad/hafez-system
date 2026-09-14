<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>التحقق من الشهادة | Hafez System</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="hafez-public-shell">
    <main class="hafez-public-main">
        <div class="hafez-verification-wrap">
            <header class="hafez-verification-header">
                <span class="hafez-verification-kicker">Hafez System</span>
                <h1>التحقق من الشهادة</h1>
                <p>أدخل رقم الشهادة للتحقق من صحتها وبياناتها</p>
            </header>

            <section class="hafez-verification-search card border-0" aria-labelledby="verification-search-title">
                <div class="card-body">
                    <h2 id="verification-search-title" class="h6">تحقق من شهادة</h2>
                    <form method="GET" action="{{ route('certificates.verify.form') }}" class="hafez-verification-form d-flex gap-2" dir="rtl">
                        <label for="certificate_number" class="visually-hidden">رقم الشهادة</label>
                        <input id="certificate_number" class="form-control hafez-verification-number" name="certificate_number" value="{{ old('certificate_number', $certificateNumber) }}" placeholder="أدخل رقم الشهادة" autocomplete="off" dir="ltr" required>
                        <button class="btn btn-success text-nowrap" type="submit">تحقق</button>
                    </form>
                </div>
            </section>

            @if($state === 'found' && $certificate)
                <section class="hafez-verification-result card border-0" aria-live="polite" aria-labelledby="verification-result-title">
                    <div class="card-body">
                        @php
                            $displayPercentage = $certificate->result?->percentage === null
                                ? null
                                : rtrim(rtrim(number_format((float) $certificate->result->percentage, 2, '.', ''), '0'), '.');
                        @endphp
                        <div class="hafez-verification-success">
                            <span class="hafez-verification-check" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="m5 12 4.5 4.5L19 7" /></svg></span>
                            <div><h2 id="verification-result-title">تم التحقق من الشهادة</h2><span class="visually-hidden">شهادة صحيحة</span><p>هذه الشهادة مسجلة وصادرة من خلال Hafez System.</p></div>
                        </div>
                        <dl class="hafez-verification-grid">
                            <div class="hafez-verification-item hafez-verification-item--number"><dt>رقم الشهادة</dt><dd dir="ltr">{{ $certificate->certificate_number }}</dd></div>
                            <div class="hafez-verification-item hafez-verification-item--student"><dt>الطالب</dt><dd dir="auto">{{ $certificate->student?->full_name }}</dd></div>
                            <div class="hafez-verification-item"><dt>المسابقة</dt><dd>{{ $certificate->competition?->title }}</dd></div>
                            <div class="hafez-verification-item"><dt>مستوى المسابقة</dt><dd>{{ $certificate->competitionBranch?->name }}</dd></div>
                            <div class="hafez-verification-item hafez-verification-item--performance">
                                <dt>الأداء المحقق</dt>
                                <dd>
                                    <span class="hafez-verification-score-value">
                                        <strong dir="ltr">{{ $certificate->result?->final_score !== null ? $certificate->result->final_score : '—' }}</strong>
                                        <span>درجة</span>
                                    </span>
                                    @if($displayPercentage !== null)
                                        <span class="hafez-verification-performance-separator" aria-hidden="true">|</span>
                                        <span class="hafez-verification-score-percent" dir="ltr">{{ $displayPercentage }}%</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="hafez-verification-item"><dt>نوع الشهادة</dt><dd>{{ $certificate->certificate_type }}</dd></div>
                            <div class="hafez-verification-item"><dt>تاريخ الإصدار</dt><dd>{{ $certificate->issued_at?->locale('ar')->translatedFormat('j F Y، g:i a') }}</dd></div>
                        </dl>
                        <div class="hafez-verification-actions">
                            <a class="btn btn-success" href="{{ route('certificates.verify.preview', $certificate->certificate_number) }}">عرض الشهادة</a>
                        </div>
                    </div>
                </section>
            @elseif($state === 'not_found')
                <section class="hafez-verification-result hafez-verification-result--invalid card border-0" aria-live="polite">
                    <div class="card-body text-center"><span class="hafez-verification-icon" aria-hidden="true">!</span><h2>لم يتم العثور على الشهادة</h2><p>تأكد من رقم الشهادة وحاول مرة أخرى.</p></div>
                </section>
            @endif

            <a href="{{ url('/') }}" class="hafez-verification-home">العودة إلى الصفحة الرئيسية</a>
        </div>
    </main>
</body>
</html>
