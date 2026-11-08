@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Mis citas')
@section('content')
<x-page-heading title="Mis citas" :back="route('home')" />
<a class="button button-primary" href="{{ route('reservations.create') }}">Reservar cita médica</a>
@forelse($rows as $row)<article class="table-panel"><a href="{{ route('appointments.show',$row->id) }}">{{ $row->codigo }}</a><p>{{ $row->medico }} · {{ $row->especialidad }} · {{ $row->estado }}</p><p>{{ \App\Modules\Shared\Clinic::date($row->inicio)->format('d/m/Y H:i') }}</p></article>@empty<p>No tienes citas registradas.</p>@endforelse
{{ $rows->links('pagination.default') }}
@endsection
