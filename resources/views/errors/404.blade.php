@extends('layouts.app')
@section('title', 'Página no encontrada')
@section('content')
    <section class="error-page"><p class="error-code">404</p><h1>No encontramos esta página</h1><p class="text-muted">El enlace puede haber cambiado o el registro ya no está disponible.</p><div class="button-row"><a class="button button-primary" href="{{ route('home') }}">Volver al inicio</a></div></section>
@endsection
