@extends('layouts.app')
@section('body-class', 'app-page workflow-page')
@section('title', 'Modificar recordatorio #'.$row->id)
@section('content')
<x-page-heading :title="'Modificar recordatorio #'.$row->id" :back="route('reminders.show', $row->id)" />

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

<section class="table-panel" style="padding: 16px; margin-bottom: 20px; background: #fafcfe;">
    <p style="margin: 0;"><strong>Cita asociada:</strong> <a href="{{ route('appointments.show', $cita->id) }}">{{ $cita->codigo }}</a> · Paciente: {{ $cita->paciente_nombre }} · Médico: {{ $cita->medico_nombre }} ({{ $cita->especialidad }}) · Turno: {{ \App\Modules\Shared\Clinic::date($cita->inicio)->format('d/m/Y H:i') }}</p>
</section>

<form method="POST" action="{{ route('reminders.update', $row->id) }}" class="form-panel" style="padding: 24px; border: 1px solid var(--border); border-radius: 12px; background: #fff;">
    @csrf

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 18px;">
        <div>
            <label for="destinatario" style="font-weight: 600; display: block; margin-bottom: 6px;">Destinatario:</label>
            <select id="destinatario" name="destinatario" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                <option value="Paciente" {{ old('destinatario', $row->destinatario) === 'Paciente' ? 'selected' : '' }}>Paciente</option>
                <option value="Medico" {{ old('destinatario', $row->destinatario) === 'Medico' ? 'selected' : '' }}>Médico</option>
                <option value="Ambos" {{ old('destinatario', $row->destinatario) === 'Ambos' ? 'selected' : '' }}>Ambos (Paciente y Médico)</option>
            </select>
        </div>

        <div>
            <label for="medio" style="font-weight: 600; display: block; margin-bottom: 6px;">Medio de envío:</label>
            <select id="medio" name="medio" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                <option value="Correo" {{ old('medio', $row->medio) === 'Correo' ? 'selected' : '' }}>Correo electrónico</option>
                <option value="SMS" {{ old('medio', $row->medio) === 'SMS' ? 'selected' : '' }}>Mensaje de texto SMS</option>
                <option value="WhatsApp" {{ old('medio', $row->medio) === 'WhatsApp' ? 'selected' : '' }}>WhatsApp</option>
                <option value="Sistema" {{ old('medio', $row->medio) === 'Sistema' ? 'selected' : '' }}>Notificación interna en Portal</option>
            </select>
        </div>

        <div>
            <label for="fecha_programada" style="font-weight: 600; display: block; margin-bottom: 6px;">Fecha y hora programada:</label>
            <input type="datetime-local" id="fecha_programada" name="fecha_programada" value="{{ old('fecha_programada', \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('Y-m-d\TH:i')) }}" required style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
        </div>
    </div>

    <div style="margin-bottom: 18px;">
        <label for="mensaje" style="font-weight: 600; display: block; margin-bottom: 6px;">Mensaje del recordatorio:</label>
        <textarea id="mensaje" name="mensaje" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 7px; font-family: inherit;" required>{{ old('mensaje', $row->mensaje) }}</textarea>
    </div>

    <div class="heading-actions" style="margin-top: 24px;">
        <button type="submit" class="button button-primary">
            <x-icon name="check" /> Guardar cambios
        </button>
        <a class="button" href="{{ route('reminders.show', $row->id) }}">Cancelar</a>
    </div>
</form>
@endsection

