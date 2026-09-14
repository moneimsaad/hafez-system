@props(['label' => 'تأكيد الحذف', 'confirm' => 'هل أنت متأكد؟'])
<button type="submit" {{ $attributes->merge(['class' => 'btn btn-outline-danger']) }} onclick="return confirm('{{ $confirm }}')">{{ $label }}</button>
