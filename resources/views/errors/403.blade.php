@extends('layouts.app')
@section('title', 'Acceso restringido')
@section('content')
    <section class="error-page"><p class="error-code">403</p><h1>Acceso restringido</h1><p class="text-muted">Tu cuenta no tiene permiso para acceder a esta sección. Puedes continuar desde los módulos disponibles.</p><div class="button-row"><a class="button button-primary" href="{{ route('home') }}">Volver al inicio</a></div></section>
@endsection
