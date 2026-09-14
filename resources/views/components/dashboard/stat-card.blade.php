@props(['label', 'value' => null, 'icon' => ''])
<div class="card hafez-stat-card border-0 h-100">
    <div class="card-body p-4 d-flex align-items-start justify-content-between">
        <div><p class="hafez-stat-label mb-2">{{ $label }}</p><p class="display-6 fw-bold mb-0">{{ $value ?? '—' }}</p></div>
        <span class="hafez-stat-icon" aria-hidden="true"><x-ui.sidebar-icon :name="$icon" class="hafez-stat-icon__svg" /></span>
    </div>
</div>
