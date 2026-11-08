@extends('layouts.app')
@section('body-class', 'app-page workflow-page')
@section('title', 'Cancelar recordatorio #'.$row->id)
@section('content')
<x-page-heading :title="'Cancelar recordatorio #'.$row->id" :back="route('reminders.show', $row->id)" />

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <x-icon name="alert" />
        <div>
            <strong>Revisa los siguientes campos:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<section class="table-panel" style="padding: 20px; margin-bottom: 24px; border-color: #efc6cb; background: #fffbfb;">
    <h2 style="color: var(--danger); margin-top: 0;">¿Estás seguro de cancelar este recordatorio?</h2>
    <p>El recordatorio no será enviado y quedará registrado como <strong>Cancelado</strong> en el historial.</p>
    <dl style="display: grid; grid-template-columns: 140px 1fr; gap: 8px; margin: 14px 0 0;">
        <dt style="font-weight: 600; color: var(--muted);">Recordatorio:</dt>
        <dd style="margin: 0;">#{{ $row->id }} ({{ $row->destinatario }} por {{ $row->medio }})</dd>

        <dt style="font-weight: 600; color: var(--muted);">Cita médica:</dt>
        <dd style="margin: 0;">{{ $row->cita_codigo }} · Paciente: {{ $row->paciente_nombre }} · Médico: {{ $row->medico_nombre }}</dd>

        <dt style="font-weight: 600; color: var(--muted);">Programado para:</dt>
        <dd style="margin: 0;">{{ \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i') }}</dd>
    </dl>
</section>

<form method="POST" action="{{ route('reminders.cancel', $row->id) }}" class="form-panel" style="padding: 20px; border: 1px solid var(--border); border-radius: 12px; background: #fff;">
    @csrf

    <div style="margin-bottom: 18px;">
        <label for="motivo_cancelacion" style="font-weight: 600; display: block; margin-bottom: 6px;">Motivo de cancelación (obligatorio):</label>
        <textarea id="motivo_cancelacion" name="motivo_cancelacion" rows="3" required placeholder="Ej: Solicitud del paciente, reprogramación manual, o cancelación de la cita." style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 7px; font-family: inherit;">{{ old('motivo_cancelacion') }}</textarea>
    </div>

    <div class="heading-actions">
        <button type="submit" class="button button-danger">
            <x-icon name="cross" /> Confirmar cancelación del recordatorio
        </button>
        <a class="button" href="{{ route('reminders.show', $row->id) }}">Regresar</a>
    </div>
</form>
@endsection

