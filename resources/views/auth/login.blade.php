@extends('layouts.app')
@section('title', 'Iniciar sesión')
@section('body-class', 'login-page')
@section('main-class', 'login-shell')
@section('hide-header', 'true')
@section('hide-footer', 'true')
@section('content')
    <section class="login-card" aria-labelledby="login-title">
        <div class="login-heading"><h1 id="login-title">Clínica Vida Salud</h1><p>Iniciar sesión</p></div>
        <form method="POST" action="{{ route('login') }}" class="form-stack" data-submit-form>
            @csrf
            <x-field name="login" label="Usuario" :required="true" autocomplete="username" autofocus autocapitalize="none" spellcheck="false" />
            <x-field name="password" label="Contraseña" type="password" :required="true" autocomplete="current-password" />
            <button class="button button-primary button-full" type="submit" data-loading-text="Ingresando…">Ingresar</button>
        </form>
    </section>
@endsection
