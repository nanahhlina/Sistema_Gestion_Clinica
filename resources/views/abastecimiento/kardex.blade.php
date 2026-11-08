@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Kardex de inventario')
@section('content')
<x-page-heading title="Kardex de inventario" :back="route('supply.inventory.index')" />
<form method="get" class="filters">
@foreach(['producto_id','lote_id'] as $key)
@if(!empty($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
@endforeach
<x-field name="desde" label="Desde (Bolivia)" type="date" :value="$filters['desde'] ?? null" />
<x-field name="hasta" label="Hasta, día completo (Bolivia)" type="date" :value="$filters['hasta'] ?? null" />
<x-field name="tipo" label="Tipo" type="select" :options="[''=>'Todos']+\App\Modules\GestionAbastecimiento\Services\InventarioService::TYPES" :value="$filters['tipo'] ?? ''" />
<button class="button">Filtrar</button></form>
<p>{{ $rows->total() }} movimientos. Los saldos corresponden al instante de cada movimiento, incluyendo el historial anterior a los filtros. Los registros no se editan ni eliminan; las correcciones se registran con un movimiento compensatorio.</p>
<div class="table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Kardex de movimientos"><table class="supply-table"><thead><tr><th>Registro / fecha Bolivia</th><th>Producto / lote</th><th>Tipo</th><th>Cantidad</th><th>Saldo lote / producto</th><th>Responsable</th><th>Origen y motivo</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>#{{ $row->id }}<br>{{ \App\Modules\Shared\Clinic::date($row->fecha)->format('d/m/Y H:i:s') }}</td><td>{{ $row->producto }}<br>{{ $row->numero_lote }}</td><td>{{ \App\Modules\GestionAbastecimiento\Services\InventarioService::TYPES[$row->tipo] }}</td><td>{{ $row->cantidad }} {{ $row->unidad_medida }}</td><td>{{ $row->saldo_lote }} / {{ $row->saldo_producto }}</td><td>{{ $row->responsable }}</td><td>
@if($row->detalle_compra_id)Detalle compra #{{ $row->detalle_compra_id }}@elseif($row->detalle_venta_lote_id)Asignación venta #{{ $row->detalle_venta_lote_id }}@elseMovimiento manual @endif
<p>{{ $row->motivo ?? 'Sin motivo adicional' }}</p></td></tr>
@empty<tr><td colspan="7">No hay movimientos para los filtros seleccionados.</td></tr>@endforelse
</tbody></table></div></div>
{{ $rows->links('pagination.default') }}
@endsection
