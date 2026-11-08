@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Detalle de cita')
@section('content')
<x-page-heading :title="'Cita '.$row->codigo" :back="route('home')" />
<dl><dt>Paciente</dt><dd>{{ $row->paciente }}</dd><dt>Médico</dt><dd>{{ $row->medico }}</dd><dt>Especialidad</dt><dd>{{ $row->especialidad }}</dd><dt>Turno</dt><dd>{{ \App\Modules\Shared\Clinic::date($row->inicio)->format('d/m/Y H:i') }}–{{ \App\Modules\Shared\Clinic::date($row->fin)->format('H:i') }}</dd><dt>Estado</dt><dd>{{ $row->estado }}</dd></dl>
@if(auth()->user()->hasPermission('citas.gestionar') && !auth()->user()->isDoctor() && !auth()->user()->isPatient() && in_array($row->estado,['Programada','Confirmada']))
<nav class="button-row" aria-label="Gestionar cita">
@foreach(['cancelar'=>'Cancelar','reprogramar'=>'Reprogramar','confirmar'=>'Confirmar asistencia','inasistencia'=>'Marcar inasistencia'] as $action=>$label)
@if($action!=='confirmar' || $row->estado==='Programada')<a class="button" href="{{ route('appointments.edit',['id'=>$row->id,'accion'=>$action]) }}">{{ $label }}</a>@endif
@endforeach</nav>@endif
<p>Precio de consulta: {{ $row->precio_consulta === null ? 'Sin definir' : $row->precio_consulta.' BOB' }}</p>
@if(auth()->user()->hasPermission('pagos.registrar') && !auth()->user()->isPatient() && !auth()->user()->isDoctor())<a class="button" href="{{ route('appointments.price.edit',$row->id) }}">Definir o corregir precio</a>@endif

<section style="margin-top: 24px;">
<h2>Recordatorios de la cita</h2>
@if((auth()->user()->hasPermission('recordatorios.gestionar') || auth()->user()->hasPermission('citas.gestionar')) && !auth()->user()->isDoctor() && !auth()->user()->isPatient() && in_array($row->estado, ['Programada', 'Confirmada']))
    <div style="margin-bottom: 14px;">
        <a class="button button-primary" href="{{ route('reminders.create', ['cita_id' => $row->id]) }}">
            <x-icon name="bell" /> Registrar recordatorio
        </a>
    </div>
@endif
@if(!empty($recordatorios) && count($recordatorios))
    <div class="table-panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Destinatario</th>
                        <th>Medio</th>
                        <th>Contacto</th>
                        <th>Fecha programada</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recordatorios as $rec)
                        <tr>
                            <td>#{{ $rec->id }}</td>
                            <td><span class="badge badge-info">{{ $rec->destinatario }}</span></td>
                            <td>{{ $rec->medio }}</td>
                            <td>{{ $rec->contacto_destino }}</td>
                            <td>{{ \App\Modules\Shared\Clinic::date($rec->fecha_programada)->format('d/m/Y H:i') }}</td>
                            <td><x-status :value="$rec->estado" /></td>
                            <td><a class="button button-small" href="{{ route('reminders.show', $rec->id) }}">Ver detalle</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <p class="text-muted">No hay recordatorios registrados para esta cita.</p>
@endif
</section>

<h2>Historial de cambios</h2>
@forelse($events as $event)<article class="table-panel"><p>{{ $event->estado_anterior ?? 'Nueva' }} → {{ $event->estado_nuevo }} · {{ $event->actor }}</p>
<p>{{ $event->detalle }}</p><p>Programación anterior: {{ $event->inicio_anterior ? \App\Modules\Shared\Clinic::date($event->inicio_anterior)->format('d/m/Y H:i') : '—' }} · Nueva: {{ \App\Modules\Shared\Clinic::date($event->inicio_nuevo)->format('d/m/Y H:i') }}</p>
<p>Registrado: {{ \App\Modules\Shared\Clinic::date($event->ocurrido_en)->format('d/m/Y H:i:s') }}</p></article>@empty<p>Sin cambios registrados.</p>@endforelse
@endsection
