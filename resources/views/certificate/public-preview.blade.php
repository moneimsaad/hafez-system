<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>شهادة {{ $certificate->certificate_number }} | Hafez System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hafez-public-shell certificate-public-preview">
    <main class="certificate-public-preview__main">
        <div class="certificate-public-preview__toolbar no-print">
            <a href="{{ route('certificates.verify', $certificate->certificate_number) }}" class="btn btn-outline-secondary">العودة للتحقق</a>
        </div>
        @include('certificate.partials.certificate-sheet')
    </main>
</body>
</html>
