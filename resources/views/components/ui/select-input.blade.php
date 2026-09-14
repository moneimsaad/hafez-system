@props(['label' => null, 'name', 'options' => [], 'value' => null, 'placeholder' => 'اختر', 'required' => false])
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label ?? $name }} @if($required)<span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }} @if($errors->has($name)) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'form-select'.($errors->has($name) ? ' is-invalid' : '')]) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $key => $option)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $option }}</option>
        @endforeach
    </select>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
