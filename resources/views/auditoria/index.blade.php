@extends('layouts.app')
@section('title', 'Bitácora de actividad')
@section('content')
    <x-page-heading title="Bitácora de actividad" description="Consulta las acciones registradas en el sistema." />
    <form class="filters" method="GET" action="{{ route('auditoria.index') }}"><div class="filter-grid"><x-field name="desde" label="Desde" type="date" :value="request('desde')" /><x-field name="hasta" label="Hasta" type="date" :value="request('hasta')" /><x-field name="entidad" label="Entidad" :value="request('entidad')" placeholder="Por ejemplo: cita" maxlength="80" /><div class="filter-actions"><button class="button button-primary" type="submit"><x-icon name="search" />Filtrar</button><a class="button" href="{{ route('auditoria.index') }}">Limpiar</a></div></div></form>
    <div class="table-panel">
        @if($rows->count())<div class="table-scroll" role="region" aria-label="Bitácora de actividad" tabindex="0"><table><caption class="sr-only">Registro de auditoría</caption><thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Entidad</th><th scope="col">Registro</th><th scope="col">Descripción</th></tr></thead><tbody>@foreach($rows as $row)<tr><td class="cell-date">{{ \Illuminate\Support\Carbon::parse($row->fecha)->format('d/m/Y · H:i:s') }}</td><td>{{ $row->actor ?: 'Sistema' }}</td><td>{{ ucfirst(str_replace('_', ' ', $row->accion)) }}</td><td>{{ $row->entidad }}</td><td>{{ $row->registro_id ?? '—' }}</td><td>{{ $row->descripcion }}</td></tr>@endforeach</tbody></table></div>{{ $rows->withQueryString()->links('pagination.default') }}@else<x-empty-state title="Sin actividad registrada" message="Las acciones realizadas en el sistema aparecerán aquí." icon="shield" />@endif
    </div>
@endsection
