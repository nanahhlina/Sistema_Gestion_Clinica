@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Detalle de compra')
@section('content')
<x-page-heading :title="'Compra #'.$row->id" :back="route('supply.purchases.index')" />
<p>{{ $row->proveedor }} · {{ $row->estado }} · {{ $row->total }} BOB</p>
@foreach($details as $d)<p>{{ $d->nombre }}: {{ $d->cantidad }} {{ $d->unidad_medida }} a {{ $d->precio_unitario }} BOB. Recibido {{ $d->recibida }}; pendiente {{ $d->cantidad-$d->recibida }}.</p>@endforeach
@if(in_array($row->estado,['Registrada','Parcial']))
<h2>Registrar recepción</h2>
<p>Indica el número y vencimiento de un lote existente o registra uno nuevo. Puedes dividir una línea entre varios lotes. Proveedores/productos desactivados conservan las compras anteriores.</p>
<form method="post" action="{{ route('supply.purchases.receive',$row->id) }}" class="form-panel">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),'recepcion_compra.'.$row->id,$key) }}">
@foreach($lines as $i=>$line)
<fieldset><legend>Recepción {{ $i+1 }}</legend>
<x-field :name="'lineas['.$i.'][detalle_id]'" label="Producto pendiente" type="select" :options="$details->filter(fn($d)=>$d->cantidad>$d->recibida)->mapWithKeys(fn($d)=>[$d->id=>$d->nombre.' (pendiente '.($d->cantidad-$d->recibida).')'])->all()" :value="$line['detalle_id']??''" />
<x-field :name="'lineas['.$i.'][cantidad]'" label="Cantidad recibida" :value="$line['cantidad']??''" />
<x-field :name="'lineas['.$i.'][numero_lote]'" label="Número de lote" :value="$line['numero_lote']??''" maxlength="80" />
<x-field :name="'lineas['.$i.'][fecha_vencimiento]'" label="Vencimiento (obligatorio en medicamentos)" type="date" :value="$line['fecha_vencimiento']??''" />
</fieldset>@endforeach
<button class="button" name="paso" value="agregar" formnovalidate>Agregar lote o producto</button><button class="button button-primary" name="paso" value="revisar">Revisar recepción</button></form>
@if($row->estado==='Registrada')
<form method="post" action="{{ route('supply.purchases.cancel',$row->id) }}">@csrf
@php($cancelKey=\App\Modules\Shared\Services\OperacionWeb::token())
<input type="hidden" name="clave" value="{{ $cancelKey }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),'cancelar_compra.'.$row->id,$cancelKey) }}"><button class="button">Confirmar cancelación sin recepciones</button></form>
@endif
@endif
@endsection
