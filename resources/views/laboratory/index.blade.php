@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Órdenes y resultados de laboratorio')
@section('content')
<x-page-heading title="Órdenes y resultados de laboratorio" :back="route('home')" />
<form method="get" class="filters">
<x-field name="q" label="Nombre, apellido o documento del paciente" :value="request('q')" />
<x-field name="orden_id" label="Número de orden" type="number" :value="request('orden_id')" />
<x-field name="desde" label="Desde" type="date" :value="request('desde')" /><x-field name="hasta" label="Hasta" type="date" :value="request('hasta')" />
<x-field name="estado" label="Estado" type="select" :options="[''=>'Todos','Pendiente'=>'Pendiente','En proceso'=>'En proceso','Completada'=>'Completada','Cancelada'=>'Cancelada']" :value="request('estado')" /><button class="button">Buscar</button>
</form>
@forelse($rows as $row)<article class="table-panel"><a href="{{ route('orders.show',$row->id) }}">Orden {{ $row->id }}</a><p>{{ $row->paciente }} · {{ $row->estado }} · {{ \App\Modules\Shared\Clinic::date($row->fecha_solicitud)->format('d/m/Y H:i') }}</p></article>@empty<p>No se encontraron órdenes con esos criterios.</p>@endforelse
{{ $rows->links('pagination.default') }}
@endsection
