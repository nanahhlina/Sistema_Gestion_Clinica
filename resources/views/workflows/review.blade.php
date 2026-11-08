@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title', $title)
@section('content')
<x-page-heading :title="$title" :back="route('home')" />
<dl>@foreach($summary as $label => $value)<dt>{{ $label }}</dt><dd style="white-space:pre-wrap">{{ $value }}</dd>@endforeach</dl>
<form method="post" action="{{ $action }}">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ $signature }}">
@foreach($data as $name => $value)
@include('workflows.hidden',['fieldName'=>$name,'fieldValue'=>$value])
@endforeach
<button name="paso" value="guardar" class="button button-primary">Confirmar y guardar</button>
</form>
<p>Para corregir información, vuelve al formulario con el botón Atrás del navegador.</p>
@endsection
