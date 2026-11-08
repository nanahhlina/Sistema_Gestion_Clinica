@extends('layouts.app')
@section('title', 'Detalle de actividad')
@section('content')
    <x-page-heading :title="'Evento de auditoría #'.$record->id" description="Información de la actividad registrada. Este registro es de solo lectura." :back="$backUrl" />
    <section class="panel" aria-label="Detalle del evento de auditoría">
        <dl class="detail-list detail-grid">
            <div><dt>Evento</dt><dd>{{ $record->id }}</dd></div>
            <div><dt>Fecha y hora</dt><dd>{{ \Illuminate\Support\Carbon::parse($record->fecha)->timezone('America/La_Paz')->format('d/m/Y · H:i:s') }}</dd></div>
            <div><dt>Usuario</dt><dd>{{ $record->actor ?: 'Sistema' }}</dd></div>
            <div><dt>Acción</dt><dd>{{ $record->accion }}</dd></div>
            <div><dt>Entidad</dt><dd>{{ $record->entidad ?? '—' }}</dd></div>
            <div><dt>Registro afectado</dt><dd>{{ $record->registro_id ?? '—' }}</dd></div>
            <div><dt>Dirección IP</dt><dd>{{ $record->direccion_ip ?? 'No registrada' }}</dd></div>
            <div class="grid-full"><dt>Descripción</dt><dd>{{ $record->descripcion ?? 'Sin descripción' }}</dd></div>
        </dl>
    </section>
@endsection
