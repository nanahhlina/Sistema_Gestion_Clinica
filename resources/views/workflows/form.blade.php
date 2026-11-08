@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title', $title)
@section('content')
<x-page-heading :title="$title" :back="route('home')" />
@if(!empty($context['message']))<p role="status">{{ $context['message'] }}</p>@endif
@if(!empty($context['search']))
<form method="get" class="filters"><label>Buscar paciente <input name="q" value="{{ request('q') }}" maxlength="100"></label><button class="button">Buscar</button></form>
@endif
<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="form-panel">
@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ $signature }}">
@foreach($fields as $name => $field)
@if(($field['type'] ?? '') === 'hidden')<input type="hidden" name="{{ $name }}" value="{{ $values[$name] ?? $field['value'] ?? '' }}">
@else
<div class="field"><label for="f-{{ $loop->index }}">{{ $field['label'] }}{{ !empty($field['required']) ? ' (obligatorio)' : '' }}</label>
@if(isset($field['options']))
<select class="form-control" id="f-{{ $loop->index }}" name="{{ $name }}" @required($field['required'] ?? false) @if(!empty($field['multiple'])) multiple @endif>
@unless(!empty($field['multiple']))<option value="">Selecciona una opción</option>@endunless
@foreach($field['options'] as $value => $label)<option value="{{ $value }}" @selected(in_array((string)$value, array_map(fn($v)=>is_bool($v)?(string)(int)$v:(string)$v, (array)($values[rtrim($name,'[]')] ?? [])), true))>{{ $label }}</option>@endforeach
</select>
@elseif(($field['type'] ?? '') === 'textarea')
<textarea class="form-control" id="f-{{ $loop->index }}" name="{{ $name }}" maxlength="{{ $field['max'] ?? 10000 }}" @required($field['required'] ?? false)>{{ $values[$name] ?? '' }}</textarea>
@else
<input class="form-control" id="f-{{ $loop->index }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" @if(($field['type'] ?? '') !== 'file') value="{{ $values[$name] ?? '' }}" @endif @required($field['required'] ?? false) @if(($field['type'] ?? '') === 'file') accept=".pdf,.jpg,.jpeg,.png" @else maxlength="{{ $field['max'] ?? 255 }}" @endif>
@endif
</div>
@endif
@endforeach
<p>Revisa la información antes de confirmar el registro.</p>
<button class="button button-primary" name="paso" value="{{ $context['direct'] ?? false ? 'guardar' : 'revisar' }}">{{ $context['direct'] ?? false ? 'Guardar' : 'Revisar información' }}</button>
</form>
@endsection
