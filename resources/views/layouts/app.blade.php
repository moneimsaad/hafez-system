<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app(\App\Services\PlatformSettingsService::class)->get('platform.name', config('app.name', 'Hafez System')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="hafez-app">
    @include('layouts.navigation')
    <div class="hafez-shell">
        <aside class="hafez-sidebar">
            <button type="button" class="hafez-sidebar-toggle d-none d-lg-inline-flex" data-sidebar-toggle aria-expanded="true" aria-controls="desktop-sidebar">
                <span class="hafez-sidebar-toggle__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="m14.5 5-7 7 7 7" /></svg>
                </span>
                <span class="visually-hidden">طي القائمة الجانبية</span>
            </button>
            <div id="desktop-sidebar" class="h-100">@include('layouts.sidebar')</div>
        </aside>
        <main class="hafez-content">
            <x-ui.breadcrumbs />
            @isset($header)<header class="hafez-page-header">{{ $header }}</header>@endisset
            {{ $slot }}
        </main>
    </div>
</body>

</html>
