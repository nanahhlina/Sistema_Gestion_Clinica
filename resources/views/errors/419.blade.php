@extends('layouts.app')
@section('title', 'Sesión vencida')
@section('content')
    <section class="error-page"><p class="error-code">419</p><h1>Tu sesión ha vencido</h1><p class="text-muted">Actualiza la página o inicia sesión nuevamente para continuar. Es posible que debas volver a ingresar los datos del formulario.</p><div class="button-row"><a class="button button-primary" href="{{ route('login') }}">Iniciar sesión</a><a class="button" href="{{ route('home') }}">Volver al inicio</a></div></section>
@endsection
