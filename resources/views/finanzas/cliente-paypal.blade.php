@extends('layouts.app')
@section('body-class','app-page customer-payment-page')
@section('title','Estado del pago')
@section('hide-header', true)
@section('content')
<section class="customer-payment" aria-live="polite">
    <div class="customer-brand">✚ <span>Clínica Vida Salud</span></div>
    @if($row->estado === 'Completado')
        <div class="payment-check" aria-hidden="true"><svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24"/><path d="m15 26 7 7 15-16"/></svg></div>
        <h1>¡Pago completado!</h1><p>Tu pago fue confirmado correctamente.</p>
        <div class="customer-receipt"><div><span>Importe</span><strong>Bs {{ number_format((float) $row->monto, 2, '.', ',') }}</strong></div><div><span>Comprobante</span><strong>VS-P-{{ $row->id }}</strong></div><div><span>Método</span><strong>PayPal Sandbox</strong></div></div>
        <p class="payment-muted">Este comprobante es informativo y no constituye factura fiscal.</p><button type="button" class="button button-primary no-print" data-print>Imprimir comprobante</button>
    @elseif($row->estado === 'Pendiente')
        <div class="payment-pending-icon" aria-hidden="true">{{ $cancelled ? '↩' : '…' }}</div><h1>{{ $cancelled ? 'Pago sin completar' : 'Verificando tu pago' }}</h1><p>{{ $cancelled ? 'Regresaste sin completar el pago. Si necesitas ayuda, contacta con la clínica.' : 'Estamos comprobando el resultado con PayPal. No necesitas volver a pagar.' }}</p>
        <form method="post" action="{{ $verifyUrl }}" @if($autoVerify) data-customer-verify @endif>@csrf<button class="button button-primary">Verificar mi pago</button></form>
    @else
        <div class="payment-pending-icon" aria-hidden="true">!</div><h1>Pago no completado</h1><p>El pago no está disponible. Contacta con la clínica para recibir ayuda.</p>
    @endif
</section>
@endsection
@push('scripts')<script type="module" src="{{ asset('assets/payment.js') }}?v={{ substr(hash_file('sha256', public_path('assets/payment.js')), 0, 16) }}"></script>@endpush
