@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Pacientes para atención clínica')
@section('content')
<x-page-heading title="Pacientes para atención clínica" description="Pacientes con una cita programada, confirmada o atendida asignada a ti." :back="route('home')" />
<form method="get" class="filters"><label>Nombre, apellido o documento <input name="q" minlength="2" maxlength="100" value="{{ request('q') }}"></label><button class="button">Buscar</button></form>
@forelse($rows as $p)<article class="table-panel"><h2>{{ $p->nombres }} {{ $p->apellidos }}</h2><p>{{ $p->ci }}{{ $p->activo ? '' : ' · Paciente inactivo' }}</p><a class="button" href="{{ route('clinical.show',$p->id) }}">Consultar historial clínico</a></article>@empty<p>No se encontraron pacientes autorizados con esos criterios.</p>@endforelse
{{ $rows->links('pagination.default') }}
@endsection
