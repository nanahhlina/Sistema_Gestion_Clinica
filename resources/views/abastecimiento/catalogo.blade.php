@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title',$definition['title'])
@section('content')
<x-page-heading :title="$definition['title']" :back="route('home')" />
<a class="button button-primary" href="{{ route('supply.'.$kind.'.create') }}">Registrar {{ $kind==='productos'?'producto':'proveedor' }}</a>
<form method="get" class="filters">
<x-field name="q" :label="$kind==='productos'?'Buscar nombre':'Buscar nombre o NIT'" :value="$filters['q'] ?? null" maxlength="150" />
<x-field name="activo" label="Estado" type="select" :options="[''=>'Todos',1=>'Activo',0=>'Inactivo']" :value="$filters['activo'] ?? ''" />
@if($kind==='productos')
<x-field name="tipo" label="Tipo" type="select" :options="[''=>'Todos','Medicamento'=>'Medicamento','Insumo'=>'Insumo']" :value="$filters['tipo'] ?? ''" />
@endif
<button class="button">Buscar</button></form>
<p>{{ $rows->total() }} registros encontrados. Desactivar conserva los registros y sus relaciones.</p>
<div class="table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Listado de registros"><table class="supply-table"><thead><tr><th>Nombre</th>
@if($kind==='productos')<th>Tipo</th><th>Unidad</th><th>Precio BOB</th>@else<th>NIT</th><th>Teléfono</th><th>Correo</th>@endif
<th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->nombre }}</td>
@if($kind==='productos')<td>{{ $row->tipo }}</td><td>{{ $row->unidad_medida }}</td><td>{{ $row->precio_venta ?? 'Sin definir' }}</td>@else<td>{{ $row->nit ?? 'Sin NIT' }}</td><td>{{ $row->telefono ?? '—' }}</td><td>{{ $row->correo ?? '—' }}</td>@endif
<td><x-status :value="$row->activo?'Activo':'Inactivo'" /></td><td><a href="{{ route('supply.'.$kind.'.edit',$row->id) }}">Editar / cambiar estado</a>
@if($kind==='productos' && auth()->user()->hasPermission('inventario.consultar'))<a href="{{ route('supply.inventory.show',$row->id) }}">Existencias por lote</a>@endif
</td></tr>
@empty<tr><td colspan="6">No hay coincidencias. Cambia los filtros o registra un nuevo {{ $kind==='productos'?'producto':'proveedor' }}.</td></tr>@endforelse
</tbody></table></div></div>
{{ $rows->links('pagination.default') }}
@endsection
