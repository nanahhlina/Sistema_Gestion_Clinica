@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Ventas de medicamentos')
@section('content')
<x-page-heading title="Ventas de medicamentos" :back="route('home')" /><a class="button button-primary" href="{{ route('supply.sales.create') }}">Registrar venta</a>
<form method="get" class="filters"><x-field name="q" label="Paciente o documento" :value="$filters['q']??''" /><x-field name="estado" label="Estado" type="select" :options="[''=>'Todos','Pendiente'=>'Pendiente','Completada'=>'Completada','PendienteResolucion'=>'Pendiente de resolución','Cancelada'=>'Cancelada']" :value="$filters['estado']??''" /><button class="button">Buscar</button></form>
<div class="table-panel"><div class="table-scroll"><table><thead><tr><th>Venta</th><th>Paciente</th><th>Total BOB</th><th>Estado</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{ route('supply.sales.show',$row->id) }}">#{{ $row->id }}</a></td><td>{{ $row->paciente }}</td><td>{{ $row->total }}</td><td>{{ $row->estado }}</td></tr>@empty<tr><td colspan="4">No hay ventas.</td></tr>@endforelse
</tbody></table></div></div>{{ $rows->links('pagination.default') }}
@endsection
