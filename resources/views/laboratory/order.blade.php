@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Orden de laboratorio')
@section('content')
<x-page-heading :title="'Orden '.$order->id" :back="route('orders.index')" />
<p>{{ $order->paciente }} · {{ $order->estado }} · {{ \App\Modules\Shared\Clinic::date($order->fecha_solicitud)->format('d/m/Y H:i') }}</p>
<p>Indicaciones: {{ $order->indicaciones ?: 'Sin indicaciones adicionales.' }}</p>
@foreach($items as $item)<article class="table-panel"><h2>{{ $item->nombre }}</h2>
@if($item->resultado_id)<a class="button" href="{{ route('results.show',$item->resultado_id) }}">Consultar resultado</a>
@elseif(in_array($order->estado,['Pendiente','En proceso']) && auth()->user()->roleName()==='Laboratorista' && auth()->user()->hasPermission('laboratorio.procesar'))<a class="button" href="{{ route('results.create',$item->id) }}">Registrar resultado</a>
@else<p>Resultado todavía no disponible.</p>@endif</article>@endforeach
@if($results->isEmpty())<p>La orden todavía no tiene resultados registrados.</p>@endif
@endsection
