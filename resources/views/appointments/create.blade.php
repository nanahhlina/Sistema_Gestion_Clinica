@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title', $own ? 'Reservar cita médica' : 'Agendar cita médica')
@section('content')
<x-page-heading :title="$own ? 'Reservar cita médica' : 'Agendar cita médica'" :back="route('home')" />
<section data-availability="{{ route($own?'reservations.availability':'appointments.availability') }}">
<form method="get" id="appointment-filters" class="filters" data-appointment-filter>
@unless($own)
<label>Buscar paciente <input name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}"></label>
<x-field name="paciente_id" label="Paciente activo" type="select" :options="[''=>'Selecciona un paciente']+$patients" :value="$patient" />
@endunless
<x-field name="especialidad_id" label="Especialidad" type="select" :options="[''=>'Selecciona una especialidad']+$specialties" :value="$specialty" />
<x-field name="fecha" label="Fecha" type="date" :value="$filters['fecha'] ?? null" />
<button class="button button-primary">Consultar disponibilidad</button>
</form>
@if($specialty && !$doctors)<p role="status">No hay médicos disponibles para esta especialidad.</p>@endif
@if($specialty && $patient && !empty($filters['fecha']) && $doctors)
<div class="form-panel">
<x-field name="medico_id" id="medico-disponibilidad" form="appointment-filters" label="Médico" type="select" :options="[''=>'Selecciona un médico']+$doctors" :value="$doctor" hint="Selecciona un médico y consulta sus turnos para la fecha indicada." />
<button type="submit" form="appointment-filters" class="button button-primary">Consultar turnos del médico</button>
</div>
@endif
@if($slots)
<form method="post" action="{{ $action }}" class="form-panel">@csrf
<input type="hidden" name="clave" value="{{ $key }}"><input type="hidden" name="firma" value="{{ \App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),$operation,$key) }}">
@unless($own)<input type="hidden" name="paciente_id" value="{{ $patient }}">@endunless
<input type="hidden" name="especialidad_id" value="{{ $specialty }}"><input type="hidden" name="medico_id" value="{{ $doctor }}">
@if(!$own && auth()->user()->hasPermission('pagos.registrar'))
<x-field name="precio_consulta" label="Precio de consulta (BOB, opcional)" inputmode="decimal" maxlength="13" hint="Vacío: sin definir. Cero: sin cargo." />
@endif
<x-field name="inicio" label="Turno disponible (inicio y fin)" type="select" :options="$slots" required hint="Selecciona un turno para revisar y confirmar la cita." />
<div class="field"><label for="motivo">Motivo de consulta (opcional)</label><textarea id="motivo" name="motivo_consulta" maxlength="2000"></textarea></div>
<p role="status" data-availability-status></p><button type="button" class="button" data-availability-retry hidden>Reintentar consulta</button>
<button name="paso" value="revisar" class="button button-primary" data-book-appointment>Revisar cita</button>
</form>
@elseif($doctor && $patient && !empty($filters['fecha']))<p role="status">No hay horarios disponibles para esta fecha. Selecciona otra fecha o médico.</p>
@elseif($specialty && $patient && !empty($filters['fecha']) && $doctors)<p>Selecciona el médico en la sección inferior y pulsa Consultar turnos del médico.</p>
@else<p>{{ $own ? 'Selecciona especialidad y fecha.' : 'Selecciona paciente, especialidad y fecha.' }} Pulsa Consultar disponibilidad para elegir el médico en la sección inferior.</p>@endif
</section>
@endsection
