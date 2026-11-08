@extends('layouts.app')
@section('body-class', 'app-page workflow-page')
@section('title', 'Recordatorio #'.$row->id)
@section('content')
<x-page-heading :title="'Recordatorio #'.$row->id" :back="route('reminders.index')">
    <x-slot:actions>
        <x-status :value="$row->estado" />
    </x-slot:actions>
</x-page-heading>

{{-- Flujo Secundario: Alertas de cita cancelada o reprogramada --}}
@if($citaActual && $citaActual->estado === 'Cancelada' && in_array($row->estado, ['Programado', 'Fallido']))
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <x-icon name="alert" />
        <div style="flex: 1;">
            <strong>¡Atención! La cita médica asociada fue cancelada.</strong>
            <p style="margin: 4px 0 8px;">Motivo de cancelación de la cita: <em>{{ $citaActual->motivo_cancelacion ?: 'Sin motivo registrado' }}</em>.</p>
            <form method="POST" action="{{ route('reminders.sync', $row->id) }}" style="display:inline;">
                @csrf
                <button type="submit" class="button button-small button-danger">
                    <x-icon name="cross" /> Cancelar este recordatorio automáticamente
                </button>
            </form>
        </div>
    </div>
@endif

{{-- Flujo Secundario: Alerta de error de envío --}}
@if($row->estado === 'Fallido')
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <x-icon name="alert" />
        <div style="flex: 1;">
            <strong>Fallo en el envío del recordatorio (Flujo Secundario - Error de envío)</strong>
            <p style="margin: 4px 0 8px;">Detalle del error registrado: <code>{{ $row->error_envio }}</code></p>
            <p style="margin: 0 0 10px;">Intentos acumulados: {{ $row->intentos }}. Puedes corregir los datos de contacto o reintentar el envío ahora.</p>
            <div class="button-row">
                <form method="POST" action="{{ route('reminders.send', $row->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button button-small button-primary">
                        <x-icon name="arrow-right" /> Reintentar envío ahora
                    </button>
                </form>
                <a class="button button-small" href="{{ route('reminders.edit', $row->id) }}">
                    <x-icon name="edit" /> Modificar datos de contacto / medio
                </a>
            </div>
        </div>
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    {{-- Card de Recordatorio --}}
    <article class="table-panel" style="padding: 20px;">
        <h2 style="margin-top: 0; color: var(--blue-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            Datos del recordatorio
        </h2>
        <dl style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; margin: 0;">
            <dt style="font-weight: 600; color: var(--muted);">Estado:</dt>
            <dd style="margin: 0;"><x-status :value="$row->estado" /></dd>

            <dt style="font-weight: 600; color: var(--muted);">Destinatario:</dt>
            <dd style="margin: 0;"><span class="badge badge-info">{{ $row->destinatario }}</span></dd>

            <dt style="font-weight: 600; color: var(--muted);">Medio de envío:</dt>
            <dd style="margin: 0;"><strong>{{ $row->medio }}</strong></dd>

            <dt style="font-weight: 600; color: var(--muted);">Contacto destino:</dt>
            <dd style="margin: 0;"><code>{{ $row->contacto_destino }}</code></dd>

            @if($row->contacto_doctor)
                <dt style="font-weight: 600; color: var(--muted);">Contacto médico:</dt>
                <dd style="margin: 0;"><code>{{ $row->contacto_doctor }}</code></dd>
            @endif

            <dt style="font-weight: 600; color: var(--muted);">Programado para:</dt>
            <dd style="margin: 0;">{{ \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i') }}</dd>

            <dt style="font-weight: 600; color: var(--muted);">Fecha de envío:</dt>
            <dd style="margin: 0;">{{ $row->fecha_envio ? \App\Modules\Shared\Clinic::date($row->fecha_envio)->format('d/m/Y H:i:s') : 'Pendiente' }}</dd>

            <dt style="font-weight: 600; color: var(--muted);">Intentos:</dt>
            <dd style="margin: 0;">{{ $row->intentos }}</dd>

            <dt style="font-weight: 600; color: var(--muted);">Registrado por:</dt>
            <dd style="margin: 0;">{{ $row->registrado_por_nombre }} ({{ \App\Modules\Shared\Clinic::date($row->creado_en)->format('d/m/Y H:i') }})</dd>

            @if($row->motivo_cancelacion)
                <dt style="font-weight: 600; color: var(--danger);">Cancelación:</dt>
                <dd style="margin: 0; color: var(--danger);">{{ $row->motivo_cancelacion }}</dd>
            @endif
        </dl>

        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border);">
            <strong style="display: block; margin-bottom: 6px;">Mensaje a enviar:</strong>
            <blockquote style="margin: 0; padding: 12px 14px; background: #f8fafc; border-left: 4px solid var(--blue); border-radius: 4px; font-style: normal; line-height: 1.5;">
                {{ $row->mensaje }}
            </blockquote>
        </div>
    </article>

    {{-- Card de Cita Médica --}}
    <article class="table-panel" style="padding: 20px;">
        <h2 style="margin-top: 0; color: var(--blue-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            Cita médica vinculada
        </h2>
        <dl style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; margin: 0;">
            <dt style="font-weight: 600; color: var(--muted);">Cita:</dt>
            <dd style="margin: 0;"><a href="{{ route('appointments.show', $row->cita_id) }}"><strong>{{ $row->cita_codigo }}</strong></a></dd>

            <dt style="font-weight: 600; color: var(--muted);">Estado de cita:</dt>
            <dd style="margin: 0;"><x-status :value="$row->cita_estado" /></dd>

            <dt style="font-weight: 600; color: var(--muted);">Fecha y hora:</dt>
            <dd style="margin: 0;"><strong>{{ \App\Modules\Shared\Clinic::date($row->cita_inicio)->format('d/m/Y H:i') }}</strong> – {{ \App\Modules\Shared\Clinic::date($row->cita_fin)->format('H:i') }}</dd>

            <dt style="font-weight: 600; color: var(--muted);">Especialidad:</dt>
            <dd style="margin: 0;">{{ $row->especialidad }}</dd>

            <dt style="font-weight: 600; color: var(--muted);">Paciente:</dt>
            <dd style="margin: 0;">
                <strong>{{ $row->paciente_nombre }}</strong> (CI: {{ $row->paciente_ci }})
                <br><small class="text-muted">Tel: {{ $row->paciente_telefono }} | Correo: {{ $row->paciente_correo ?: '—' }}</small>
            </dd>

            <dt style="font-weight: 600; color: var(--muted);">Médico:</dt>
            <dd style="margin: 0;">
                <strong>Dr(a). {{ $row->medico_nombre }}</strong>
                <br><small class="text-muted">Tel: {{ $row->medico_telefono ?: '—' }} | Correo: {{ $row->medico_correo ?: '—' }}</small>
            </dd>
        </dl>

        @if(!auth()->user()->isPatient() && !auth()->user()->isDoctor() && in_array($row->estado, ['Programado', 'Fallido']))
            <div style="margin-top: 24px; padding-top: 14px; border-top: 1px solid var(--border);">
                <strong style="display: block; margin-bottom: 10px;">Acciones de gestión:</strong>
                <div class="button-row">
                    <form method="POST" action="{{ route('reminders.send', $row->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="button button-primary">
                            <x-icon name="arrow-right" /> Enviar ahora
                        </button>
                    </form>
                    <a class="button" href="{{ route('reminders.edit', $row->id) }}">
                        <x-icon name="edit" /> Modificar
                    </a>
                    <a class="button button-danger" href="{{ route('reminders.cancel.form', $row->id) }}">
                        <x-icon name="cross" /> Cancelar recordatorio
                    </a>
                </div>

                {{-- Botón para verificar Flujo Secundario: Simular fallo --}}
                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed var(--border);">
                    <form method="POST" action="{{ route('reminders.send', $row->id) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="simular_fallo" value="1">
                        <button type="submit" class="button button-small" title="Prueba el registro del fallo y cambio de estado a Fallido" style="color: #a62d42;">
                            <x-icon name="alert" /> Simular fallo de envío (Flujo Secundario)
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </article>
</div>

{{-- Historial de eventos y envíos del recordatorio --}}
<section class="table-panel" style="padding: 20px;">
    <h2>Historial de eventos y envíos</h2>
    <p class="text-muted">Trazabilidad completa de registros, modificaciones, intentos de envío y cancelaciones (Paso 10).</p>

    @if(count($history))
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Acción</th>
                        <th>Estado anterior</th>
                        <th>Estado nuevo</th>
                        <th>Detalle</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $item)
                        <tr>
                            <td class="cell-date">{{ \App\Modules\Shared\Clinic::date($item->fecha)->format('d/m/Y H:i:s') }}</td>
                            <td><strong>{{ $item->accion }}</strong></td>
                            <td>{{ $item->estado_anterior ?: '—' }}</td>
                            <td><x-status :value="$item->estado_nuevo" /></td>
                            <td>{{ $item->detalle }}</td>
                            <td>{{ $item->usuario_nombre ?: 'Sistema automático' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">Sin eventos registrados aún.</p>
    @endif
</section>
@endsection

