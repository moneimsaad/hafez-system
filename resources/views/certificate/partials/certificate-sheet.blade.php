@php
    $branchName = trim((string) $certificate->competitionBranch?->name);
    $branchScope = trim((string) ($certificate->competitionBranch?->memorization_amount ?: $certificate->competitionBranch?->description));
    $achievementPercentage = (float) ($certificate->result?->percentage ?? 0) > 75
        ? rtrim(rtrim(number_format((float) $certificate->result->percentage, 2, '.', ''), '0'), '.')
        : null;
@endphp

<article class="certificate-sheet" aria-label="شهادة تقدير">
    <div class="certificate-sheet__corner certificate-sheet__corner--top" aria-hidden="true"></div>
    <div class="certificate-sheet__corner certificate-sheet__corner--bottom" aria-hidden="true"></div>
    <div class="certificate-sheet__pattern" aria-hidden="true"></div>

    <header class="certificate-sheet__brand">
        <strong class="certificate-sheet__brand-en">Hafez System</strong>
        <small class="certificate-sheet__brand-ar">منصة إدارة مسابقات حفظ القرآن الكريم</small>
    </header>

    <section class="certificate-sheet__title-zone">
        <p class="certificate-sheet__eyebrow">شهادة رسمية</p>
        <div class="certificate-sheet__title-rule" aria-hidden="true"><span></span></div>
        <h2>شهادة تقدير</h2>
        <div class="certificate-sheet__title-rule" aria-hidden="true"><span></span></div>
    </section>

    <section class="certificate-sheet__body">
        <p class="certificate-sheet__issuer">تتقدم <strong>«{{ $organizationName }}»</strong> بخالص التقدير والاعتزاز إلى</p>
        <h3 class="certificate-sheet__student" dir="auto">{{ $certificate->student?->full_name }}</h3>
        <p class="certificate-sheet__recognition">تقديراً لمشاركةٍ متميزة وجهودٍ مباركة في</p>
        <p class="certificate-sheet__competition">{{ $certificate->competition?->title }}</p>
        @if($branchName || $branchScope)
            <p class="certificate-sheet__level">{{ $branchName }}@if($branchName && $branchScope) <span aria-hidden="true">—</span> @endif{{ $branchScope }}</p>
        @endif
        @if($achievementPercentage !== null)
            <p class="certificate-sheet__achievement">وقد تحققت نسبة <strong dir="ltr">{{ $achievementPercentage }}%</strong>، تقديراً للأداء المتميز في المسابقة.</p>
        @endif
    </section>

    <footer class="certificate-sheet__meta">
        <div class="certificate-sheet__number"><small>رقم الشهادة</small><strong dir="ltr">{{ $certificate->certificate_number }}</strong></div>
        <div class="certificate-sheet__footer-center"><p>مع أطيب الأمنيات بمزيد من التوفيق والتميّز مع كتاب الله.</p><small>تاريخ الإصدار</small><strong>{{ optional($certificate->issued_at)->format('Y-m-d') }}</strong></div>
        <div class="certificate-sheet__qr"><img src="{{ $qrDataUri }}" alt="رمز QR للتحقق من صحة الشهادة"><small>امسح للتحقق من صحة الشهادة</small></div>
    </footer>
</article>
