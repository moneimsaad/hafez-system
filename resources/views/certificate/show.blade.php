<x-app-layout>
    <x-slot name="header">
        <div class="certificate-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><p class="small text-success fw-bold mb-1">سجل الشهادات</p><h1 class="h3 mb-0">تفاصيل الشهادة</h1></div>
            <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary no-print">العودة للشهادات</a>
        </div>
    </x-slot>

    <div class="container-fluid py-4 px-3 px-lg-4 certificate-details-page">
        <x-ui.alert />
        @include('certificate.partials.certificate-sheet')
        <div class="certificate-actions no-print"><button class="btn btn-success px-4" type="button" onclick="window.print()">طباعة</button></div>
    </div>
</x-app-layout>
