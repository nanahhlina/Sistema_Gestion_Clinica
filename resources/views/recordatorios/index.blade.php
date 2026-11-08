@extends('layouts.app')
@section('title', 'Gestión de recordatorios')
@section('content')
<x-page-heading title="Gestión de recordatorios" description="Administra y envía recordatorios de citas médicas para pacientes y médicos (CU24)." :back="route('home')" />

<div class="heading-actions" style="margin-bottom: 20px;">
    <a class="button button-primary" href="{{ route('reminders.create') }}">
        <x-icon name="plus" /> Registrar recordatorio
    </a>
    <form method="POST" action="{{ route('reminders.process') }}" style="display:inline;">
        @csrf
        <button type="submit" class="button" title="Envía los recordatorios cuya hora programada ya llegó">
            <x-icon name="bell" /> Procesar recordatorios programados
        </button>
    </form>
</div>

<section class="home-summary" style="margin-bottom: 24px;">
    <div class="stats-grid">
        <article class="stat-card">
            <h3>Total recordatorios</h3>
            <p class="stat-value">{{ $stats['total'] }}</p>
        </article>
        <article class="stat-card">
            <h3>Programados</h3>
            <p class="stat-value" style="color: #8c611b;">{{ $stats['programados'] }}</p>
        </article>
        <article class="stat-card">
            <h3>Enviados</h3>
            <p class="stat-value" style="color: #247149;">{{ $stats['enviados'] }}</p>
        </article>
        <article class="stat-card">
            <h3>Con fallo</h3>
            <p class="stat-value" style="color: #a62d42;">{{ $stats['fallidos'] }}</p>
        </article>
        <article class="stat-card">
            <h3>Cancelados</h3>
            <p class="stat-value" style="color: #6c757d;">{{ $stats['cancelados'] }}</p>
        </article>
    </div>
</section>

<form method="GET" action="{{ route('reminders.index') }}" class="filters">
    <div class="filter-grid">
        <x-field name="q" label="Buscar paciente, médico o cita" type="text" :value="$filters['q'] ?? null" placeholder="Nombre, CI o código" />
        <x-field name="estado" label="Estado" type="select" :options="['' => 'Todos los estados'] + \App\Modules\GestionPacientes\Services\RecordatorioService::ESTADOS" :value="$filters['estado'] ?? null" />
        <x-field name="destinatario" label="Destinatario" type="select" :options="['' => 'Todos los destinatarios'] + \App\Modules\GestionPacientes\Services\RecordatorioService::DESTINATARIOS" :value="$filters['destinatario'] ?? null" />
        <x-field name="medio" label="Medio" type="select" :options="['' => 'Todos los medios'] + \App\Modules\GestionPacientes\Services\RecordatorioService::MEDIOS" :value="$filters['medio'] ?? null" />
        <x-field name="desde" label="Fecha desde" type="date" :value="$filters['desde'] ?? null" />
        <x-field name="hasta" label="Fecha hasta" type="date" :value="$filters['hasta'] ?? null" />
        <div class="filter-actions">
            <button type="submit" class="button button-primary"><x-icon name="search" /> Filtrar</button>
            <a class="button" href="{{ route('reminders.index') }}">Limpiar</a>
        </div>
    </div>
</form>

<p class="text-muted">{{ $rows->total() }} {{ $rows->total() === 1 ? 'recordatorio registrado' : 'recordatorios registrados' }}.</p>

<div class="table-panel">
    @if($rows->count())
        <div class="table-scroll" role="region" aria-label="Listado de recordatorios" tabindex="0">
            <table>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Cita médica</th>
                        <th scope="col">Paciente</th>
                        <th scope="col">Médico / Especialidad</th>
                        <th scope="col">Destinatario</th>
                        <th scope="col">Medio</th>
                        <th scope="col">Programado para</th>
                        <th scope="col">Enviado el</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td><a href="{{ route('reminders.show', $row->id) }}"><strong>#{{ $row->id }}</strong></a></td>
                            <td>
                                <a href="{{ route('appointments.show', $row->cita_id) }}">{{ $row->cita_codigo }}</a>
                                <br><small class="text-muted">{{ \App\Modules\Shared\Clinic::date($row->cita_inicio)->format('d/m/Y H:i') }}</small>
                                @if($row->cita_estado === 'Cancelada')
                                    <br><span class="badge badge-danger">Cita cancelada</span>
                                @endif
                            </td>
                            <td>
                                {{ $row->paciente_nombre }}
                                <br><small class="text-muted">CI: {{ $row->paciente_ci }}</small>
                            </td>
                            <td>
                                {{ $row->medico_nombre }}
                                <br><small class="text-muted">{{ $row->especialidad }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $row->destinatario }}</span>
                            </td>
                            <td>
                                <strong>{{ $row->medio }}</strong>
                                <br><small class="text-muted">{{ $row->contacto_destino }}</small>
                            </td>
                            <td class="cell-date">
                                {{ \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i') }}
                            </td>
                            <td class="cell-date">
                                {{ $row->fecha_envio ? \App\Modules\Shared\Clinic::date($row->fecha_envio)->format('d/m/Y H:i') : '—' }}
                                @if($row->intentos > 0)
                                    <br><small class="text-muted">{{ $row->intentos }} {{ $row->intentos === 1 ? 'intento' : 'intentos' }}</small>
                                @endif
                            </td>
                            <td>
                                <x-status :value="$row->estado" />
                                @if($row->estado === 'Fallido')
                                    <br><small class="text-muted" title="{{ $row->error_envio }}">Error registrado</small>
                                @endif
                            </td>
                            <td>
                                <div class="button-row">
                                    <a class="button button-small" href="{{ route('reminders.show', $row->id) }}" title="Ver detalles e historial">
                                        <x-icon name="eye" /> Ver
                                    </a>
                                    @if(in_array($row->estado, ['Programado', 'Fallido']))
                                        <a class="button button-small" href="{{ route('reminders.edit', $row->id) }}" title="Modificar recordatorio">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('reminders.send', $row->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="button button-small button-primary" title="{{ $row->estado === 'Fallido' ? 'Reintentar envío' : 'Enviar ahora' }}">
                                                <x-icon name="arrow-right" /> {{ $row->estado === 'Fallido' ? 'Reintentar' : 'Enviar' }}
                                            </button>
                                        </form>
                                        <a class="button button-small button-danger" href="{{ route('reminders.cancel.form', $row->id) }}" title="Cancelar recordatorio">
                                            <x-icon name="cross" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $rows->links('pagination.default') }}
    @else
        <x-empty-state title="Sin recordatorios" message="No se encontraron recordatorios con los filtros seleccionados." icon="bell" />
    @endif
</div>
@endsection

