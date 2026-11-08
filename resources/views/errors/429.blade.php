@extends('layouts.app')
@section('title', 'Demasiadas solicitudes')
@section('content')
    <section class="error-page"><p class="error-code">429</p><h1>Espera un momento</h1><p class="text-muted">Se recibieron demasiadas solicitudes. Espera un minuto antes de intentarlo nuevamente.</p><div class="button-row"><a class="button button-primary" href="{{ route('login') }}">Volver al ingreso</a><a class="button" href="{{ route('home') }}">Volver al inicio</a></div></section>
@endsection
