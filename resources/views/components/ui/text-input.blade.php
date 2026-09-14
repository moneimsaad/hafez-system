@props(['label' => null, 'name', 'value' => null, 'required' => false])
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label ?? $name }} @if($required)<span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} @if($errors->has($name)) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>
    {{ $slot }}
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
