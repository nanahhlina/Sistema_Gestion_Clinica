@extends('layouts.app')
@section('title', $definition['title'])
@section('content')
    <x-page-heading :title="$definition['title']" :description="$canWrite ? 'Consulta y administra los registros de la clínica.' : 'Consulta los registros disponibles de la clínica.'">
        @if($canWrite)<a class="button button-primary" href="{{ route('catalog.create', ['module' => $module]) }}"><x-icon name="plus" />Agregar {{ mb_strtolower($definition['singular']) }}</a>@endif
    </x-page-heading>
    <form method="GET" action="{{ route('catalog.index', ['module' => $module]) }}" class="filters">
        <div class="filter-grid"><x-field name="q" label="Buscar" type="search" :value="request('q')" placeholder="Escribe para buscar…" /><div class="filter-actions"><button class="button button-primary" type="submit"><x-icon name="search" />Buscar</button><a class="button" href="{{ route('catalog.index', ['module' => $module]) }}">Limpiar</a></div></div>
    </form>
    <div class="table-panel">
        @if($rows->count())
            <div class="table-scroll" role="region" aria-label="{{ $definition['title'] }}" tabindex="0"><table><caption class="sr-only">Listado de {{ mb_strtolower($definition['title']) }}</caption><thead><tr>@foreach($columns as $label)<th scope="col">{{ $label }}</th>@endforeach @if($canWrite)<th scope="col">Acciones</th>@endif</tr></thead><tbody>
                @foreach($rows as $row)<tr>@foreach($columns as $field => $label)<td>@if(in_array($field, ['activo', 'active'], true))<x-status :value="data_get($row, $field) ? 'Activo' : 'Inactivo'" />@else{{ data_get($row, $field) ?? '—' }}@endif</td>@endforeach @if($canWrite)<td class="cell-actions"><a class="button button-small" href="{{ route('catalog.edit', ['module' => $module, 'id' => $row->id]) }}" aria-label="Editar {{ $definition['singular'] }} {{ $row->id }}"><x-icon name="edit" />Editar</a></td>@endif</tr>@endforeach
            </tbody></table></div>
            {{ $rows->withQueryString()->links('pagination.default') }}
        @else
            <x-empty-state title="No se encontraron registros" :message="request('q') ? 'Prueba con otro término de búsqueda o limpia el filtro.' : 'Los registros de este módulo aparecerán aquí.'" />
        @endif
    </div>
@endsection
