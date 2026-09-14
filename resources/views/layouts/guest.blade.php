<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app(\App\Services\PlatformSettingsService::class)->get('platform.name', config('app.name', 'Hafez System')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="hafez-auth-shell">
    <main class="container py-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="hafez-auth-brand text-center mb-4">
                    <h1>{{ app(\App\Services\PlatformSettingsService::class)->get('platform.name', 'Hafez System') }}</h1>
                    <p>منصة إدارة مسابقات حفظ القرآن الكريم</p>
                </div>
                <div class="hafez-auth-panel p-4 p-lg-5">{{ $slot }}</div>
            </div>
        </div>
    </main>
</body>

</html>
