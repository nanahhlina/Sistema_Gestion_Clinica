@extends('layouts.app')
@section('title', 'Bitácora de actividad')
@section('content')
    <x-page-heading title="Bitácora de actividad" description="Consulta las acciones registradas en el sistema." />
    <form class="filters" method="GET" action="{{ route('auditoria.index') }}">
        <div class="filter-grid">
            <x-field name="desde" label="Desde" type="date" :value="$filters['desde'] ?? null" />
            <x-field name="hasta" label="Hasta" type="date" :value="$filters['hasta'] ?? null" />
            <x-field name="usuario_id" label="Usuario" type="select" :value="$filters['usuario_id'] ?? null" :options="$users" />
            <x-field name="accion" label="Acción" type="select" :value="$filters['accion'] ?? null" :options="$actions" />
            <x-field name="entidad" label="Entidad" :value="$filters['entidad'] ?? null" placeholder="Por ejemplo: cita" maxlength="80" />
            <div class="filter-actions">
                <button class="button button-primary" type="submit"><x-icon name="search" />Filtrar</button>
                <a class="button" href="{{ route('auditoria.index') }}">Limpiar</a>
            </div>
        </div>
    </form>
    <div class="table-panel">
        @if($rows->count())
            <div class="table-scroll" role="region" aria-label="Bitácora de actividad" tabindex="0">
                <table>
                    <caption class="sr-only">Registro de auditoría</caption>
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Entidad</th><th scope="col">Registro</th><th scope="col">Descripción</th><th scope="col">Detalle</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td class="cell-date">{{ \Illuminate\Support\Carbon::parse($row->fecha)->timezone('America/La_Paz')->format('d/m/Y · H:i:s') }}</td>
                                <td>{{ $row->actor ?: 'Sistema' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $row->accion)) }}</td>
                                <td>{{ $row->entidad ?? '—' }}</td>
                                <td>{{ $row->registro_id ?? '—' }}</td>
                                <td>{{ $row->descripcion ?? '—' }}</td>
                                <td><a class="button button-small" href="{{ route('auditoria.show', ['id' => $row->id, ...$filters]) }}" aria-label="Ver detalle del evento {{ $row->id }}">Ver detalle</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links('pagination.default') }}
        @else
            <x-empty-state title="Sin actividad registrada" message="No hay acciones que coincidan con los filtros seleccionados." icon="shield" />
        @endif
    </div>
@endsection
