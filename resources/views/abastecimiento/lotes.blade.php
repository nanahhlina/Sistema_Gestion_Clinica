@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Lotes y existencias')
@section('content')
<x-page-heading :title="isset($product)?'Lotes de '.$product->nombre:'Lotes y existencias'" :back="route('home')" />
@if(auth()->user()->hasPermission('lotes.gestionar'))
<a class="button button-primary" href="{{ route('supply.lots.create',['producto_id'=>$filters['producto_id'] ?? null]) }}">Registrar lote</a>
@endif
@unless(isset($product))
<form method="get" class="filters">
<x-field name="q" label="Buscar producto o lote" :value="$filters['q'] ?? ''" maxlength="150" />
@if(!empty($filters['producto_id']))<input type="hidden" name="producto_id" value="{{ $filters['producto_id'] }}">@endif
<x-field name="estado" label="Estado" type="select" :options="[''=>'Todos','Disponible'=>'Disponible','Bloqueado'=>'Bloqueado']" :value="$filters['estado'] ?? ''" />
<x-field name="vigencia" label="Vencimiento" type="select" :options="[''=>'Todos','vigente'=>'Vigente','vencido'=>'Vencido','sin_fecha'=>'Sin fecha']" :value="$filters['vigencia'] ?? ''" />
<button class="button">Consultar</button></form>
@endunless
<p>{{ $rows->total() }} lotes. El saldo físico incluye lotes bloqueados/vencidos. Disponible excluye también productos inactivos. El vencimiento incluye el día indicado en Bolivia.</p>
<div class="table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Existencias por lote"><table class="supply-table"><thead><tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Estado</th><th>Físico</th><th>Disponible</th><th>Acciones</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->producto }} @unless($row->producto_activo)<span>— inactivo</span>@endunless<br>{{ $row->unidad_medida }}</td><td>{{ $row->numero_lote }}</td><td>{{ $row->fecha_vencimiento ?? 'Sin fecha' }} @if($row->fecha_vencimiento && $row->fecha_vencimiento<$today)<x-status value="Vencido" />@endif</td><td><x-status :value="$row->estado" /></td><td>{{ $row->saldo }}</td><td>{{ $row->disponible }}</td><td>
@if(auth()->user()->hasPermission('lotes.gestionar'))<a href="{{ route('supply.lots.edit',$row->id) }}">Editar / cambiar estado</a>@endif
@if(auth()->user()->hasPermission('inventario.consultar'))<a href="{{ route('supply.kardex',['lote_id'=>$row->id]) }}">Kardex</a>@endif
@if(auth()->user()->hasPermission('inventario.gestionar'))<a href="{{ route('supply.movements.create',$row->id) }}">Registrar movimiento</a>@endif
</td></tr>
@empty<tr><td colspan="7">No hay lotes para esta consulta.</td></tr>@endforelse
</tbody></table></div></div>
{{ $rows->links('pagination.default') }}
@endsection
