@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'hint' => null, 'multiple' => false, 'scope' => null])
@php
    $fieldId = $attributes->get('id', str_replace(['[', ']', '.'], '-', $name));
    $useOldInput = $scope === null || old('_section') === $scope;
    $fieldValue = $type === 'password' || $type === 'file' ? null : ($useOldInput ? old($name, $value) : $value);
    $hasError = $useOldInput && ($errors->has($name) || $errors->has($name.'.*'));
    $describedBy = trim(($hint ? $fieldId.'-hint ' : '').($hasError ? $fieldId.'-error' : ''));
@endphp
<div class="field {{ $type === 'checkbox' ? 'field-checkbox' : '' }}">
    @if($type === 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="checkbox-label" for="{{ $fieldId }}"><input {{ $attributes->except('id') }} id="{{ $fieldId }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) $fieldValue) @required($required) @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif><span>{{ $label }}</span></label>
    @else
        <label for="{{ $fieldId }}">{{ $label }} @if($required)<span class="required-mark" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span>@endif</label>
        @if($type === 'textarea')
            <textarea {{ $attributes->except('id')->class(['form-control']) }} id="{{ $fieldId }}" name="{{ $name }}" rows="4" @required($required) @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $fieldValue }}</textarea>
        @elseif($type === 'select' || $multiple)
            <select {{ $attributes->except('id')->class(['form-control']) }} id="{{ $fieldId }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" @if($multiple) multiple @endif @required($required) @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                @unless($multiple)<option value="">Selecciona una opción</option>@endunless
                @foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', (array) $fieldValue), true))>{{ $optionLabel }}</option>@endforeach
            </select>
        @elseif($type === 'password')
            <div class="password-control"><input {{ $attributes->except('id')->class(['form-control']) }} id="{{ $fieldId }}" type="password" name="{{ $name }}" @required($required) @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif><button class="password-toggle" type="button" data-password-toggle="{{ $fieldId }}" aria-label="Mostrar contraseña" aria-pressed="false" hidden><x-icon name="eye" /></button></div>
        @else
            <input {{ $attributes->except('id')->class(['form-control']) }} id="{{ $fieldId }}" type="{{ $type }}" name="{{ $name }}" @if($type !== 'file') value="{{ $fieldValue }}" @endif @required($required) @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @endif
    @endif
    @if($hint)<p id="{{ $fieldId }}-hint" class="field-hint">{{ $hint }}</p>@endif
    @if($hasError)<p id="{{ $fieldId }}-error" class="field-error">{{ $errors->first($name) ?: $errors->first($name.'.*') }}</p>@endif
</div>
