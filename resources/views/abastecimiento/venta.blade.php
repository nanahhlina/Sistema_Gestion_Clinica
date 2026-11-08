@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Comprobante de venta')
@section('content')
<x-page-heading :title="'Venta VS-V-'.$row->id" :back="route('supply.sales.index')" /><p>{{ $row->paciente }} · {{ $row->estado }} · {{ $row->total }} BOB</p>
@foreach($details as $d)<p>{{ $d->nombre }} × {{ $d->cantidad }} · {{ $d->precio_unitario }} BOB · subtotal {{ $d->subtotal }} BOB</p>@endforeach
<h2>Lotes entregados</h2>@forelse($lots as $l)<p>{{ $l->numero_lote }}: {{ $l->cantidad }} unidades</p>@empty<p>Sin entrega registrada.</p>@endforelse
<h2>Pagos y comprobantes</h2>@foreach($payments as $p)<p><a href="{{ route('finance.payments.show',$p->id) }}">Pago #{{ $p->id }} · {{ $p->estado }} · {{ $p->monto }} BOB</a></p>@endforeach
@if($row->estado==='Pendiente')<a class="button" href="{{ route('finance.sale.payment',$row->id) }}">Reintentar pago cuando el intento anterior esté resuelto</a>@endif
<p class="no-print">Imprime desde el menú del navegador. Este comprobante no constituye factura fiscal.</p>
@if($row->estado==='PendienteResolucion' && auth()->user()->roleName()==='Administrador')
<p>Conserva el pago confirmado. Puedes completar la entrega cuando haya existencias o devolver el importe desde el comprobante de pago.</p>
@php($retryKey=\App\Modules\Shared\Services\OperacionWeb::token())
<form class="no-print" method="post" action="{{ route('supply.sales.retry',$row->id) }}">@csrf<input type="hidden" name="clave" value="{{ $retryKey }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),'entregar_venta.'.$row->id,$retryKey) }}"><button class="button">Confirmar reintento de entrega completa</button></form>
@endif
@endsection
