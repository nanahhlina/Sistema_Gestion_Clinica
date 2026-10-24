@extends('layouts.app')
@section('title', ($record ? 'Editar ' : 'Agregar ').mb_strtolower($definition['singular']))
@section('content')
    <x-page-heading :title="($record ? 'Editar ' : 'Agregar ').mb_strtolower($definition['singular'])" description="Completa la información. Los campos con asterisco son obligatorios." :back="route('catalog.index', ['module' => $module])" />
    <section class="panel form-panel">
        <form method="POST" action="{{ $record ? route('catalog.update', ['module' => $module, 'id' => $record->id]) : route('catalog.store', ['module' => $module]) }}" class="form-stack" data-submit-form>
            @csrf
            @if($record) @method('PUT') @endif
            <div class="form-grid">
                @foreach($definition['fields'] as $name => $field)
                    @php
                        $multiple = in_array($name, ['especialidad_ids', 'permiso_ids'], true) || in_array($field['type'], ['multiselect', 'multiple'], true);
                        $fieldOptions = $options[$name] ?? $field['options'] ?? [];
                        $fieldType = $multiple ? 'select' : $field['type'];
                        $fieldValue = data_get($record, $name, $fieldType === 'checkbox' ? true : null);
                    @endphp
                    <div class="{{ $multiple || $fieldType === 'textarea' ? 'grid-full' : '' }}">
                        <x-field :name="$name" :label="$field['label']" :type="$fieldType" :value="$fieldValue" :options="$fieldOptions" :multiple="$multiple" :required="(($field['required'] ?? false) || (!$record && $fieldType === 'password')) && !($record && $fieldType === 'password')" :hint="$multiple ? 'Puedes seleccionar varias opciones con Ctrl o Cmd; en tu teléfono, toca las opciones.' : ($fieldType === 'password' ? ($record ? 'Deja este campo vacío para conservar la contraseña actual. Si la cambias, usa al menos 12 caracteres.' : 'Usa al menos 12 caracteres.') : null)" :minlength="$fieldType === 'password' ? 12 : null" :max="$name === 'fecha_nacimiento' ? now()->toDateString() : null" :autocomplete="$fieldType === 'password' ? 'new-password' : 'off'" />
                    </div>
                @endforeach
            </div>
            <div class="form-actions"><button class="button button-primary" type="submit"><x-icon name="check" />{{ $record ? 'Guardar cambios' : 'Guardar registro' }}</button><a class="button" href="{{ route('catalog.index', ['module' => $module]) }}">Cancelar</a></div>
        </form>
    </section>
@endsection
