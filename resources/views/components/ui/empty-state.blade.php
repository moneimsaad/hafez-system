@props(['message' => 'لا توجد بيانات لعرضها', 'action' => null, 'actionLabel' => null])
<div class="hafez-empty-state text-center py-5"><div class="hafez-empty-icon" aria-hidden="true">⌁</div><p class="fw-semibold mb-2">{{ $message }}</p>@if($action && $actionLabel)<a href="{{ $action }}" class="btn btn-sm btn-success">{{ $actionLabel }}</a>@endif</div>
