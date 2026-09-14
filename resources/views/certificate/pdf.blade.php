<!doctype html>
<html lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { position: relative; min-height: 190mm; margin: 0; padding: 16mm 22mm 13mm; color: #17233a; direction: ltr; font-family: DejaVu Sans, sans-serif; text-align: center; border: 2px solid #12364a; }
        body:before { content: ''; position: absolute; inset: 6mm; border: 1px solid #c7a24a; }
        .corner { position: absolute; width: 52mm; height: 31mm; border-color: #147d70; border-style: solid; opacity: .9; }
        .corner.top { top: 10mm; right: 10mm; border-width: 3px 3px 0 0; }
        .corner.bottom { bottom: 10mm; left: 10mm; border-width: 0 0 3px 3px; }
        .brand { position: relative; display: table; width: 100%; margin-bottom: 12mm; color: #12364a; }.brand strong, .brand small { display: table-cell; width: 50%; vertical-align: middle; font-weight: normal; }.brand strong { color: #147d70; font-size: 11px; text-align: left; letter-spacing: .2px; }.brand small { color: #647687; font-size: 9px; text-align: right; }
        .eyebrow { margin: 0 0 4px; color: #147d70; font-size: 10px; letter-spacing: 1.5px; }
        .title-rule { position: relative; width: 42%; height: 1px; margin: 5px auto; background: #c7a24a; }
        .title-rule span { position: absolute; top: -3px; left: 50%; width: 7px; height: 7px; margin-left: -3px; background: #147d70; transform: rotate(45deg); }
        h1 { margin: 9px 0; color: #12364a; font-size: 30px; line-height: 1.2; }
        .body { position: relative; margin: 11mm auto 0; max-width: 205mm; }
        p { margin: 0; line-height: 1.75; }
        .issuer { color: #52677a; font-size: 13px; }.student { margin: 9px 0 10px; color: #12364a; font-size: 26px; line-height: 1.38; word-wrap: break-word; }.recognition { color: #52677a; font-size: 13px; }.competition { margin-top: 7px; color: #147d70; font-size: 18px; font-weight: bold; line-height: 1.45; }.level { margin-top: 5px; color: #52677a; font-size: 12px; }.achievement { display: inline-block; margin-top: 11px; padding: 4px 13px; color: #12364a; border-top: 1px solid #d7b965; border-bottom: 1px solid #d7b965; font-size: 11px; }.achievement strong { color: #147d70; }
        .meta { position: absolute; right: 22mm; bottom: 27mm; left: 22mm; display: table; width: calc(100% - 44mm); padding-top: 6mm; border-top: 1px solid #cadbd7; text-align: right; }.meta > div { display: table-cell; width: 33.33%; vertical-align: bottom; }.meta small, .meta strong { display: block; }.meta small { margin-bottom: 3px; color: #647687; font-size: 9px; }.meta strong { color: #12364a; font-size: 10px; }.meta .number { text-align: right; }.meta .center { text-align: center; }.meta .center p { margin: 0 0 5px; color: #647687; font-size: 8px; }.meta .qr { text-align: left; }.qr img { display: block; width: 15mm; height: 15mm; margin: 0 0 2px; }.qr small { text-align: left; }
    </style>
</head>
@php
    $pdfText = \App\Support\ArabicPdfText::class;
    $branchName = trim((string) $branch?->name);
    $branchScope = trim((string) ($branch?->memorization_amount ?: $branch?->description));
    $achievementPercentage = (float) ($percentage ?? 0) > 75
        ? rtrim(rtrim(number_format((float) $percentage, 2, '.', ''), '0'), '.')
        : null;
    $issuerLine = "تتقدم «{$organizationName}» بخالص التقدير والاعتزاز إلى";
    $levelLine = trim($branchName.($branchName && $branchScope ? ' — ' : '').$branchScope);
@endphp
<body>
    <div class="corner top"></div><div class="corner bottom"></div>
    <header class="brand"><strong>Hafez System</strong><small>{{ $pdfText::visual('منصة إدارة مسابقات حفظ القرآن الكريم') }}</small></header>
    <p class="eyebrow">{{ $pdfText::visual('شهادة رسمية') }}</p><div class="title-rule"><span></span></div><h1>{{ $pdfText::visual('شهادة تقدير') }}</h1><div class="title-rule"><span></span></div>
    <main class="body">
        <p class="issuer">{{ $pdfText::visual($issuerLine) }}</p>
        <h2 class="student">{{ $pdfText::visual($student?->full_name) }}</h2>
        <p class="recognition">{{ $pdfText::visual('تقديراً لمشاركةٍ متميزة وجهودٍ مباركة في') }}</p>
        <p class="competition">{{ $pdfText::visual($competition?->title) }}</p>
        @if($levelLine)<p class="level">{{ $pdfText::visual($levelLine) }}</p>@endif
        @if($achievementPercentage !== null)<p class="achievement">{{ $pdfText::visual("وقد تحققت نسبة {$achievementPercentage}%، تقديراً للأداء المتميز في المسابقة.") }}</p>@endif
    </main>
    <footer class="meta">@if($showQr)<div class="qr"><img src="{{ $qrDataUri }}" alt="QR"><small>{{ $pdfText::visual('امسح للتحقق من صحة الشهادة') }}</small></div>@endif<div class="center"><p>{{ $pdfText::visual('مع أطيب الأمنيات بمزيد من التوفيق والتميّز مع كتاب الله.') }}</p><small>{{ $pdfText::visual('تاريخ الإصدار') }}</small><strong>{{ $issuedAt->format('Y-m-d') }}</strong></div><div class="number"><small>{{ $pdfText::visual('رقم الشهادة') }}</small><strong>{{ $certificateNumber }}</strong></div></footer>
</body>
</html>
