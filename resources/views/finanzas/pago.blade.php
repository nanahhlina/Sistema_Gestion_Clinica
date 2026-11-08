@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Pago')
@section('content')
<x-page-heading :title="'Pago VS-P-'.$row->id" :back="route('home')" />
<div class="payment-layout">
<section class="payment-card payment-main" aria-labelledby="payment-heading">
    <div class="payment-top"><div><span class="payment-eyebrow">CLÍNICA VIDA SALUD</span><h2 id="payment-heading">{{ $row->estado === 'Completado' ? 'Pago confirmado' : ($row->estado === 'Pendiente' ? 'Pago pendiente' : 'Estado del pago') }}</h2><p class="payment-muted">{{ $row->cita_id ? 'Consulta médica' : 'Venta de medicamentos' }} · VS-P-{{ $row->id }}</p></div><span class="payment-status {{ $row->estado === 'Completado' ? 'is-paid' : 'is-pending' }}">{{ $row->estado }}</span></div>
    <div class="payment-amount"><span>Total</span><strong>Bs {{ number_format((float) $row->monto, 2, '.', ',') }}</strong>@if($row->metodo === 'PayPal')<small>PayPal Sandbox · USD {{ $row->monto_pasarela }}</small>@else<small>Pago en efectivo</small>@endif</div>
    @if($row->metodo === 'PayPal' && $row->estado === 'Pendiente')
        <div class="payment-next"><h3>{{ $transaction?->approval_url ? 'Comparte el enlace con el cliente' : 'Prepara el pago con PayPal' }}</h3>
        @if($transaction?->approval_url)
            <p>El cliente puede pagar desde su dispositivo con una cuenta de prueba de PayPal Sandbox.</p>
            <label for="paypal-link">Enlace de pago</label><div class="payment-copy"><input id="paypal-link" value="{{ $transaction->approval_url }}" readonly aria-describedby="copy-status"><button type="button" class="button" data-copy-payment>Copiar enlace</button></div><p class="payment-muted" id="copy-status" role="status">Comparte este enlace solo con el cliente que realizará el pago.</p>
            <div class="payment-actions"><a class="button button-primary" href="{{ $transaction->approval_url }}" rel="noreferrer">Pagar con PayPal</a><form method="post" action="{{ route('finance.paypal.process',$row->id) }}">@csrf<button class="button">Actualizar estado</button></form></div>
        @else
            <p>Genera un enlace para que el cliente apruebe el pago en PayPal Sandbox.</p><form method="post" action="{{ route('finance.paypal.process',$row->id) }}">@csrf<button class="button button-primary">Generar enlace de pago</button></form>
        @endif
        @if($row->resultado_incierto)<p class="payment-warning" role="status">La operación está en verificación. Consulta el estado antes de iniciar otro cobro.</p>@endif
        </div>
    @elseif($row->estado === 'Completado')
        <p class="payment-success">✓ El pago fue confirmado. Puedes imprimir el comprobante desde el navegador.</p>
    @endif
</section>
<aside class="payment-card payment-details"><h2>Detalles del pago</h2><dl><dt>Paciente</dt><dd>{{ $destination->paciente ?? '—' }}</dd>@if(isset($destination->medico))<dt>Médico</dt><dd>{{ $destination->medico }}</dd><dt>Cita</dt><dd>{{ $destination->codigo }} · {{ \App\Modules\Shared\Clinic::date($destination->inicio)->format('d/m/Y H:i') }}</dd>@endif<dt>Método</dt><dd>{{ $row->metodo }}</dd><dt>Registrado</dt><dd>{{ \App\Modules\Shared\Clinic::date($row->fecha_creacion)->format('d/m/Y H:i') }}</dd>@if($row->fecha_confirmacion)<dt>Confirmado</dt><dd>{{ \App\Modules\Shared\Clinic::date($row->fecha_confirmacion)->format('d/m/Y H:i') }}</dd>@endif
@if($row->metodo === 'Efectivo')<dt>Recibido</dt><dd>Bs {{ $row->recibido_efectivo }}</dd><dt>Cambio</dt><dd>Bs {{ $row->cambio_efectivo }}</dd>@endif
@if($row->reembolsado_en)<dt>Devolución</dt><dd>{{ \App\Modules\Shared\Clinic::date($row->reembolsado_en)->format('d/m/Y H:i') }} · Responsable #{{ $row->reembolsado_por }} · {{ $row->motivo_reembolso }}</dd>@endif</dl><p class="payment-muted">Comprobante informativo; no constituye factura fiscal.</p>
@if($row->venta_id)<a class="button no-print" href="{{ route('supply.sales.show',$row->venta_id) }}">Detalle de venta</a>@endif
@if($row->estado==='Completado' && auth()->user()->roleName()==='Administrador' && auth()->user()->hasPermission('pagos.reembolsar'))<a class="button no-print" href="{{ route('finance.payments.refund.form',$row->id) }}">Solicitar devolución completa</a>@endif
</aside>
</div>
@if($row->metodo==='PayPal')<details class="payment-advanced no-print"><summary>Opciones de gestión</summary><div class="payment-advanced-content">
@if($transaction)<p>Orden: {{ $transaction->paypal_order_id }} · Captura: {{ $transaction->paypal_capture_id ?? 'Pendiente' }}</p>@endif
@if($row->estado==='Pendiente')<form method="post" action="{{ route('finance.paypal.cancel',$row->id) }}">@csrf<button class="button">Solicitar cancelación del intento sin captura</button></form>@endif
@if($transaction?->reembolso_solicitado_en)<p>Estado del reembolso: {{ $transaction->estado_reembolso }}</p>@if($row->estado==='Completado')<form method="post" action="{{ route('finance.paypal.process',$row->id) }}">@csrf<button class="button">Verificar devolución pendiente</button></form>@endif @endif
@if(auth()->user()->roleName()==='Administrador' && auth()->user()->hasPermission('pagos.reembolsar'))<a class="button" href="{{ route('finance.paypal.associate.form',$row->id) }}">Conciliar identificador externo</a>@endif
</div></details>@endif
@endsection
@push('scripts')<script type="module" src="{{ asset('assets/payment.js') }}?v={{ substr(hash_file('sha256', public_path('assets/payment.js')), 0, 16) }}"></script>@endpush
