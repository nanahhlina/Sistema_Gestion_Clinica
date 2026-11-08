@extends('layouts.app')
@section('title', 'Módulos')
@section('content')
    <div class="home-heading"><h1>Módulos</h1><p>Selecciona una opción para comenzar.</p></div>
    <div class="module-grid">
        @forelse($modules as $module)
            <a class="module-card" href="{{ data_get($module, 'url') }}"><span class="module-icon"><x-icon :name="data_get($module, 'icon', 'grid')" /></span><h2>{{ data_get($module, 'title') }}</h2><p>{{ data_get($module, 'description') }}</p></a>
        @empty
            <x-empty-state class="grid-full" title="Tu acceso está listo" message="Aún no tienes módulos asignados. Comunícate con la administración de la clínica para revisar tus permisos." icon="shield" />
        @endforelse
    </div>
    @if(count($stats ?? []))<section class="home-summary" aria-label="Actividad de la clínica"><h2 class="section-title">Resumen de actividad</h2><x-stats :items="$stats" /></section>@endif
@endsection
