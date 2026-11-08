@extends('layouts.app')
@section('title', 'Confirmar cierre de sesión')
@section('content')
    <x-page-heading title="Cerrar sesión" description="Confirma si deseas finalizar tu sesión en la clínica." :back="route('home')" />
    <section class="panel form-panel" aria-labelledby="logout-heading">
        <h2 id="logout-heading">¿Deseas cerrar tu sesión?</h2>
        <p class="text-muted">Para volver a entrar, necesitarás tu usuario y contraseña.</p>
        <form method="POST" action="{{ route('logout') }}" class="form-stack" data-logout-form data-login-url="{{ route('login') }}" data-confirm-url="{{ route('logout.confirm') }}">
            @csrf
            <p class="alert alert-danger" role="alert" tabindex="-1" hidden data-logout-error></p>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Sí, cerrar sesión</button>
                <a class="button" href="{{ route('home') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
