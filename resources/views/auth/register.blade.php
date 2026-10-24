@extends('layouts.app')
@section('title', 'Solicitar registro')
@section('body-class', 'login-page')
@section('main-class', 'login-shell')
@section('hide-header', 'true')
@section('hide-footer', 'true')
@section('content')
    <section class="login-card register-card" aria-labelledby="register-title">
        <div class="login-heading"><h1 id="register-title">Clínica Vida Salud</h1><p>Solicitar registro</p></div>
        <div class="alert alert-info"><x-icon name="shield" /><p>Tu cuenta quedará <strong>pendiente de aprobación e inactiva</strong>. Acércate a recepción con tu documento de identidad para que el personal verifique tus datos y active tu acceso. El registro no vincula automáticamente una historia clínica.</p></div>
        <form method="POST" action="{{ route('register') }}" class="form-stack" data-submit-form>
            @csrf
            <p class="text-muted text-small">Los campos con asterisco son obligatorios.</p>
            <div class="form-grid">
                <div class="grid-full"><x-field name="nombre_usuario" label="Nombre de usuario" :required="true" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="80" pattern="[A-Za-z0-9._\-]+" hint="Usa letras minúsculas sin acentos, números, puntos, guion o guion bajo." /></div>
                <x-field name="nombres" label="Nombres" :required="true" autocomplete="given-name" maxlength="100" />
                <x-field name="apellidos" label="Apellidos" :required="true" autocomplete="family-name" maxlength="100" />
                <div class="grid-full"><x-field name="correo" label="Correo electrónico" type="email" :required="true" autocomplete="email" maxlength="150" /></div>
                <x-field name="ci" label="CI (opcional)" maxlength="40" hint="Recepción verificará tu documento de identidad." />
                <x-field name="fecha_nacimiento" label="Fecha de nacimiento" type="date" :required="true" :max="now()->toDateString()" autocomplete="bday" />
                <div class="grid-full"><x-field name="telefono" label="Teléfono" type="tel" :required="true" autocomplete="tel" maxlength="40" /></div>
                <x-field name="password" label="Contraseña" type="password" :required="true" minlength="12" maxlength="255" autocomplete="new-password" hint="Usa al menos 12 caracteres." />
                <x-field name="password_confirmation" label="Confirmar contraseña" type="password" :required="true" minlength="12" maxlength="255" autocomplete="new-password" />
            </div>
            <button class="button button-primary button-full" type="submit" data-loading-text="Enviando solicitud…">Enviar solicitud de registro</button>
        </form>
        <p class="auth-link">¿Ya tienes una cuenta activa? <a href="{{ route('login') }}">Iniciar sesión</a></p>
    </section>
@endsection
