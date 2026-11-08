@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Cobros de consultas')
@section('content')
<x-page-heading title="Cobros de consultas" :back="route('home')" />
<form method="get" class="filters"><x-field name="q" label="Código, nombre o documento del paciente" :value="$filters['q']??''" /><x-field name="fecha" label="Fecha de cita (Bolivia)" type="date" :value="$filters['fecha']??''" /><button class="button">Buscar</button></form>
<div class="table-panel"><div class="table-scroll"><table><thead><tr><th>Cita</th><th>Paciente</th><th>Estado</th><th>Precio BOB</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{ route('finance.payments.create',$row->id) }}">{{ $row->codigo }}</a></td><td>{{ $row->paciente }}</td><td>{{ $row->estado }}</td><td>{{ $row->precio_consulta??'Sin definir' }}</td></tr>@empty<tr><td colspan="4">No hay citas que coincidan.</td></tr>@endforelse
</tbody></table></div></div>{{ $rows->links('pagination.default') }}
@endsection
