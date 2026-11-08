@extends('layouts.app')
@section('body-class', 'app-page workflow-page')
@section('title', 'Mis recordatorios')
@section('content')
<x-page-heading title="Mis recordatorios" description="Avisos y recordatorios sobre tus citas médicas programadas." :back="route('home')" />

<div class="heading-actions" style="margin-bottom: 20px;">
    <a class="button button-primary" href="{{ route('appointments.mine') }}">
        <x-icon name="calendar" /> Ver mis citas
    </a>
</div>

@if($rows->count())
    <div style="display: grid; gap: 16px;">
        @foreach($rows as $row)
            <article class="table-panel" style="padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <span class="badge badge-info">{{ $row->medio }}</span>
                        <strong style="margin-left: 8px;">Cita {{ $row->cita_codigo }}</strong>
                    </div>
                    <x-status :value="$row->estado" />
                </div>

                <p style="margin: 6px 0; font-size: 1.05rem;">
                    <strong>Médico:</strong> Dr(a). {{ $row->medico_nombre }} ({{ $row->especialidad }})
                </p>
                <p style="margin: 4px 0; color: var(--muted); font-size: .9rem;">
                    <strong>Turno de atención:</strong> {{ \App\Modules\Shared\Clinic::date($row->cita_inicio)->format('d/m/Y H:i') }} – {{ \App\Modules\Shared\Clinic::date($row->cita_fin)->format('H:i') }}
                </p>

                <blockquote style="margin: 12px 0 6px; padding: 10px 14px; background: #f8fafc; border-left: 3px solid var(--blue); border-radius: 4px; font-style: normal;">
                    {{ $row->mensaje }}
                </blockquote>

                <p style="margin: 8px 0 0; font-size: .8rem; color: var(--muted);">
                    @if($row->fecha_envio)
                        Enviado el {{ \App\Modules\Shared\Clinic::date($row->fecha_envio)->format('d/m/Y H:i') }}
                    @else
                        Programado para enviarse el {{ \App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i') }}
                    @endif
                </p>
            </article>
        @endforeach
    </div>
    <div style="margin-top: 20px;">
        {{ $rows->links('pagination.default') }}
    </div>
@else
    <x-empty-state title="Sin recordatorios" message="No tienes recordatorios registrados en este momento." icon="bell" />
@endif
@endsection

