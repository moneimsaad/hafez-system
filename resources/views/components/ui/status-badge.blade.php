@props(['status'])
@php($normalized = strtolower((string) $status))
@php($variant = match($normalized) { 'active', 'approved', 'successful', 'open for registration', 'مفتوح للتسجيل', 'submitted' => 'success', 'pending', 'قريباً', 'انتهى التسجيل', 'جاري الاختبارات' => 'warning', 'inactive', 'rejected', 'failed', 'مغلق', 'موقوفة' => 'danger', default => 'secondary' })
@php($label = match($normalized) { 'active' => 'نشط', 'inactive' => 'غير نشط', 'approved' => 'مقبول', 'pending' => 'قيد المراجعة', 'rejected' => 'مرفوض', 'successful' => 'ناجح', 'failed' => 'غير ناجح', 'submitted' => 'مرسل', 'draft' => 'مسودة', 'open for registration' => 'مفتوح للتسجيل', 'closed' => 'مغلق', 'running' => 'جارٍ', 'finished' => 'منتهٍ', default => $status })
<span {{ $attributes->merge(['class' => 'badge text-bg-'.$variant]) }}>{{ $label }}</span>
