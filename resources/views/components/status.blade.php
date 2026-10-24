@props(['value'])
@php
    $status = mb_strtolower((string) $value);
    $tone = match ($status) {
        'confirmada', 'confirmado', 'atendida', 'atendido', 'completada', 'completado', 'publicada', 'publicado', 'finalizada', 'finalizado', 'activo', 'activa', 'aprobada' => 'success',
        'cancelada', 'cancelado', 'inasistencia', 'inactivo', 'inactiva', 'rechazada' => 'danger',
        'pendiente', 'solicitada', 'solicitado', 'programada', 'programado' => 'warning',
        default => 'info',
    };
@endphp
<span {{ $attributes->class(['badge', 'badge-'.$tone]) }}><span class="status-dot" aria-hidden="true"></span>{{ $value === null || $value === '' ? 'Sin estado' : ucfirst(str_replace('_', ' ', (string) $value)) }}</span>
