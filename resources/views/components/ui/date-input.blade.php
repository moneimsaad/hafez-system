@props(['label' => null, 'name', 'value' => null, 'required' => false, 'type' => 'date'])
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label ?? $name }} @if($required)<span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="text" inputmode="{{ $type === 'datetime' ? 'none' : 'numeric' }}" data-hafez-date="{{ $type }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} @if($errors->has($name)) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
