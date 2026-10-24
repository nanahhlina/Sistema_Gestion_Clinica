@extends('layouts.app')
@section('title', 'No pudimos completar la solicitud')
@section('content')
    <section class="error-page"><p class="error-code">500</p><h1>Ocurrió un problema</h1><p class="text-muted">No pudimos completar esta solicitud. Inténtalo nuevamente dentro de unos momentos.</p><div class="button-row"><a class="button button-primary" href="{{ route('home') }}">Volver al inicio</a></div></section>
@endsection
