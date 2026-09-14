<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | Hafez System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="hafez-error-page">
    <main class="hafez-error-shell" aria-labelledby="error-title">
        <section class="hafez-error-card">
            <div class="hafez-error-brand" aria-label="Hafez System">
                <span>Hafez System</span>
            </div>

            <div class="hafez-error-code" aria-hidden="true">{{ $code }}</div>
            <p class="hafez-error-kicker">منصة إدارة مسابقات حفظ القرآن</p>
            <h1 id="error-title" class="hafez-error-title">{{ $title }}</h1>
            <p class="hafez-error-description">{{ $description }}</p>

            <div class="hafez-error-actions">
                @foreach ($actions as $action)
                    <a href="{{ $action['href'] }}" class="btn {{ $action['class'] ?? 'btn-success' }}">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>

            <footer class="hafez-error-footer">يمكنك العودة إلى المسار السابق أو متابعة العمل من الصفحة الرئيسية.</footer>
        </section>
    </main>
</body>

</html>
