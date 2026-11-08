@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Resultado de laboratorio')
@section('content')
<x-page-heading :title="'Resultado: '.$row->analisis" :back="route('home')" />
<dl><dt>Paciente</dt><dd>{{ $row->paciente }}</dd><dt>Orden</dt><dd>{{ $row->orden_id }}</dd><dt>Fecha de registro</dt><dd>{{ \App\Modules\Shared\Clinic::date($row->fecha_registro)->format('d/m/Y H:i') }}</dd><dt>Resultado</dt><dd style="white-space:pre-wrap">{{ $row->resultado }}</dd><dt>Observaciones</dt><dd>{{ $row->observaciones ?: 'Sin observaciones.' }}</dd><dt>Publicación para el paciente</dt><dd>{{ $row->publicado_en ? 'Publicado' : 'No publicado' }}</dd></dl>
@if($row->archivo_referencia)<a class="button" href="{{ route('results.download',$row->id) }}">Descargar informe</a>@endif
@if(auth()->user()->roleName()==='Laboratorista' && auth()->user()->hasPermission('laboratorio.procesar'))
@php($operation='publicacion.'.$row->id.'.'.($row->publicado_en?'0':'1'))
<form method="post" action="{{ route('results.publish',$row->id) }}">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),$operation,$key) }}"><input type="hidden" name="publicar" value="{{ $row->publicado_en?'0':'1' }}">
<p>{{ $row->publicado_en ? 'Al retirar la publicación el paciente dejará de ver este resultado y su informe.' : 'Al publicar confirmas que revisaste el resultado y autorizas que el paciente lo consulte.' }}</p>
<button class="button button-primary">{{ $row->publicado_en ? 'Retirar publicación' : 'Publicar para el paciente' }}</button></form>@endif
@endsection
