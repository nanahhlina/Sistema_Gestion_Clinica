@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Configuración financiera')
@section('content')
<x-page-heading title="Configuración financiera" :back="route('home')" />
<p>Los precios se expresan en bolivianos. PayPal Sandbox utilizará USD con la cotización confirmada para cada pago.</p>
<p>Tasa vigente: <strong>{{ $current ? $current->bob_por_usd.' BOB por USD' : 'Sin configurar' }}</strong></p>
@unless($current)<p role="status">Configura la tasa antes de iniciar pagos mediante PayPal. El efectivo no necesita conversión.</p>@endunless
<a class="button button-primary" href="{{ route('finance.configuration.edit') }}">Configurar tipo de cambio</a>
<h2>Historial del tipo de cambio</h2>
<div class="table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Historial de cambios del tipo de cambio"><table class="exchange-history"><thead><tr><th>Fecha Bolivia</th><th>Responsable</th><th>Anterior</th><th>Nueva (BOB/USD)</th><th>Motivo</th></tr></thead><tbody>
@forelse($history as $event)
@php($change=json_decode($event->descripcion,true) ?? [])
<tr><td>{{ \App\Modules\Shared\Clinic::date($event->fecha)->format('d/m/Y H:i:s') }}</td><td>{{ $event->responsable }}</td><td>{{ $change['anterior'] ?? 'Sin configurar' }}</td><td>{{ $change['nueva'] ?? '—' }}</td><td>{{ $change['motivo'] ?? '—' }}</td></tr>
@empty<tr><td colspan="5">No hay cambios registrados.</td></tr>@endforelse
</tbody></table></div></div>
{{ $history->links('pagination.default') }}
@endsection
