@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Gestionar cita')
@section('content')
<x-page-heading :title="ucfirst($data['accion']).' cita '.$row->codigo" :back="route('appointments.show',$row->id)" />
@if($data['accion']==='reprogramar')
<form method="get" class="filters"><input type="hidden" name="accion" value="reprogramar"><x-field name="fecha" label="Nueva fecha" type="date" :value="$data['fecha'] ?? null" required /><button class="button">Buscar turnos</button></form>
@if(empty($fields['inicio']['options']))<p role="status">No hay turnos para mostrar. Consulta otra fecha.</p>@endif
@endif
<form class="form-panel" method="post" action="{{ route('appointments.update',$row->id) }}">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),$operation,$key) }}">
<input type="hidden" name="accion" value="{{ $data['accion'] }}"><input type="hidden" name="version" value="{{ $fields['version']['value'] }}">
@if($data['accion']==='cancelar')<x-field name="motivo" label="Motivo de cancelación" type="textarea" required />@endif
@if($data['accion']==='reprogramar')<x-field name="inicio" label="Nuevo turno" type="select" :options="$fields['inicio']['options']" required />@endif
<button name="paso" value="revisar" class="button button-primary">Revisar cambio</button>
</form>
@endsection
