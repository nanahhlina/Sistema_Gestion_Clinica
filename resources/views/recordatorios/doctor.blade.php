@extends('layouts.app')
@section('title', 'Recordatorios de consultas médicas')
@section('content')
<x-page-heading title="Recordatorios de consultas médicas" description="Recordatorios enviados y programados para las citas de tus pacientes." :back="route('home')" />

<div class="heading-actions" style="margin-bottom: 20px;">
    <a class="button button-primary" href="{{ route('agenda.index') }}">
        <x-icon name="calendar" /> Consultar agenda médica
    </a>
</div>

@if($rows->count())
    <div class="table-panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cita</th>
                        <th>Paciente</th>
                        <th>Turno</th>
                        <th>Destinatario</th>
                        <th>Medio</th>
                        <th>Fecha programada</th>
                        <th>Fecha de envío</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td><strong>#{{ $row->id }}</strong></td>
                            <td><a href="{{ route('appointments.show', $row->cita_id) }}">{{ $row->cita_codigo }}</a></td>
                            <td>{{ $row->paciente_nombre }}</td>
                            <td class="cell-date">{{ \App\Modules\Shared\Clinic::date($row->cita_inicio)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge badge-info">{{ $row->destinatario }}</span></td>
                            <td>{{ $row->medio }}</td>
                            <td class="cell-date">{{ \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i') }}</td>
                            <td class="cell-date">{{ $row->fecha_envio ? \App\Modules\Shared\Clinic::date($row->fecha_envio)->format('d/m/Y H:i') : '—' }}</td>
                            <td><x-status :value="$row->estado" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $rows->links('pagination.default') }}
    </div>
@else
    <x-empty-state title="Sin recordatorios" message="No hay recordatorios de citas registrados para tus consultas." icon="bell" />
@endif
@endsection

