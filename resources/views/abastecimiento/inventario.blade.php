@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Inventario médico')
@section('content')
<x-page-heading title="Inventario médico" :back="route('home')" />
<a class="button" href="{{ route('supply.kardex') }}">Consultar kardex</a>
<form method="get" class="filters">
<x-field name="q" label="Buscar producto" :value="$filters['q'] ?? ''" maxlength="150" />
<x-field name="tipo" label="Tipo" type="select" :options="[''=>'Todos','Medicamento'=>'Medicamento','Insumo'=>'Insumo']" :value="$filters['tipo'] ?? ''" />
<x-field name="activo" label="Estado del producto" type="select" :options="[''=>'Todos',1=>'Activo',0=>'Inactivo']" :value="$filters['activo'] ?? ''" />
<button class="button">Consultar</button></form>
<p>{{ $rows->total() }} productos. Existencia = entradas + ajustes de entrada − salidas − ajustes de salida. Los ajustes no habilitan para venta un lote bloqueado o vencido.</p>
<div class="table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Existencias por producto"><table class="supply-table"><thead><tr><th>Producto</th><th>Tipo</th><th>Unidad</th><th>Estado</th><th>Físico</th><th>Disponible</th><th>Consulta</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->nombre }}</td><td>{{ $row->tipo }}</td><td>{{ $row->unidad_medida }}</td><td><x-status :value="$row->activo?'Activo':'Inactivo'" /></td><td>{{ $row->saldo }}</td><td>{{ $row->disponible }}</td><td><a href="{{ route('supply.inventory.show',$row->id) }}">Ver lotes</a><br><a href="{{ route('supply.kardex',['producto_id'=>$row->id]) }}">Kardex</a></td></tr>
@empty<tr><td colspan="7">No hay productos que coincidan con los filtros.</td></tr>@endforelse
</tbody></table></div></div>
{{ $rows->links('pagination.default') }}
@endsection
