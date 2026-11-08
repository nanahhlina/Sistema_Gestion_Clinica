@extends('layouts.app')
@section('title', 'Médico no habilitado')
@section('content')
    <x-page-heading title="Médico no habilitado" :back="route('home')" />
    <p class="alert alert-warning" role="alert">{{ $message }}</p>
    @if($canSelectDoctor)
        <form method="GET" action="{{ route('agenda.index') }}" class="filters">
            <x-field name="medico_id" label="Seleccionar otro médico" type="select" :options="$doctors" :required="true" />
            <button class="button button-primary" type="submit">Consultar agenda</button>
        </form>
    @endif
    <div class="button-row">
        <a class="button" href="{{ $historyUrl }}">Historial de citas por médico</a>
        <a class="button" href="{{ route('home') }}">Volver al inicio</a>
    </div>
@endsection
