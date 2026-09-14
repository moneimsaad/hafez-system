

@props(['type' => null, 'message' => null])

@php
    $alertMessage = $message ?? session('status') ?? session('success') ?? session('warning') ?? session('error');
    $alertType = $type ?? (session('error') ? 'danger' : (session('warning') ? 'warning' : 'success'));
@endphp

@if($alertMessage)
    <div {{ $attributes->merge(['class' => 'alert alert-'.$alertType.' d-flex align-items-start justify-content-between gap-3']) }} role="alert" aria-live="polite">
        <span>{{ $alertMessage }}</span>
        <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="إغلاق التنبيه"></button>
    </div>
@endif
