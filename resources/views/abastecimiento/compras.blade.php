@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Compras')
@section('content')
<x-page-heading title="Compras" :back="route('home')" />
<a class="button button-primary" href="{{ route('supply.purchases.create') }}">Registrar compra</a>
<form method="get" class="filters"><x-field name="q" label="Proveedor" :value="$filters['q']??''" /><x-field name="estado" label="Estado" type="select" :options="[''=>'Todos','Registrada'=>'Registrada','Parcial'=>'Parcial','Recibida'=>'Recibida','Cancelada'=>'Cancelada']" :value="$filters['estado']??''" /><button class="button">Buscar</button></form>
<div class="table-panel"><div class="table-scroll"><table><thead><tr><th>Compra</th><th>Proveedor</th><th>Total BOB</th><th>Estado</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{ route('supply.purchases.show',$row->id) }}">#{{ $row->id }}</a></td><td>{{ $row->proveedor }}</td><td>{{ $row->total }}</td><td>{{ $row->estado }}</td></tr>@empty<tr><td colspan="4">No hay compras.</td></tr>@endforelse
</tbody></table></div></div>{{ $rows->links('pagination.default') }}
@endsection
