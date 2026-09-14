@props(['label' => null, 'name', 'value' => null, 'rows' => 4, 'required' => false])
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label ?? $name }} @if($required)<span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span>@endif</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }} @if($errors->has($name)) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
