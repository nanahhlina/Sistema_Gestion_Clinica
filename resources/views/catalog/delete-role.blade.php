@extends('layouts.app')
@section('title', 'Eliminar rol')
@section('content')
    <x-page-heading title="Eliminar rol" description="Confirma la operación. Los roles protegidos o con usuarios asignados no pueden eliminarse." :back="route('catalog.index', 'roles')" />
    <section class="panel form-panel">
        <p>¿Deseas eliminar el rol <strong>{{ $role->nombre }}</strong> y sus asignaciones de permisos?</p>
        <form method="POST" action="{{ route('catalog.roles.destroy', $role->id) }}" data-submit-form>
            @csrf @method('DELETE')
            <div class="form-actions"><button class="button button-primary" type="submit">Eliminar rol</button><a class="button" href="{{ route('catalog.index', 'roles') }}">Cancelar</a></div>
        </form>
    </section>
@endsection
