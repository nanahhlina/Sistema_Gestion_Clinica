@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title',$own?'Mi historial clínico':'Historial clínico')
@section('content')
<x-page-heading :title="($own?'Mi historial clínico: ':'Historial clínico: ').$p->nombres.' '.$p->apellidos" :back="route('home')" />
@unless($own)<nav class="button-row">
@if(auth()->user()->hasPermission('atencion.registrar'))<a class="button" href="{{ route('diagnoses.create',$p->id) }}">Registrar diagnóstico</a><a class="button" href="{{ route('treatments.create',$p->id) }}">Registrar tratamiento</a>@endif
@if(auth()->user()->hasPermission('laboratorio.solicitar'))<a class="button" href="{{ route('orders.create',$p->id) }}">Solicitar análisis</a>@endif
</nav>@endunless
<dl><dt>Tipo de sangre</dt><dd>{{ $p->tipo_sangre ?? 'Sin registrar' }}</dd><dt>Nombre del contacto de emergencia</dt><dd>{{ $p->contacto_emergencia ?: 'Sin registrar' }}</dd><dt>Teléfono del contacto de emergencia</dt><dd>{{ $p->telefono_emergencia ?: 'Sin registrar' }}</dd><dt>Parentesco del contacto de emergencia</dt><dd>{{ $p->parentesco_emergencia ?? 'Sin registrar' }}</dd><dt>Enfermedades crónicas</dt><dd>{{ $history->enfermedades_cronicas ?? 'Sin registrar' }}</dd><dt>Medicamentos actuales</dt><dd>{{ $history->medicamentos_actuales ?? 'Sin registrar' }}</dd></dl>
@if(!$own && auth()->user()->hasPermission('atencion.registrar'))<a class="button" href="{{ route('clinical.medical.edit',$p->id) }}">Actualizar datos médicos</a>@endif
<h2>Antecedentes y alergias</h2>
@if($history)<dl><dt>Personales</dt><dd>{{ $history->antecedentes_personales ?: 'Sin antecedentes registrados.' }}</dd><dt>Familiares</dt><dd>{{ $history->antecedentes_familiares ?: 'Sin antecedentes registrados.' }}</dd><dt>Alergias</dt><dd>{{ $history->alergias ?: 'Sin alergias registradas.' }}</dd></dl>@else<p>Sin antecedentes registrados.</p>@endif
<h2>Atenciones, diagnósticos y tratamientos</h2>
@forelse($visits as $visit)<article class="table-panel"><h3>{{ \App\Modules\Shared\Clinic::date($visit->fecha_atencion)->format('d/m/Y H:i') }} · {{ $visit->medico }}</h3>
@unless($own)<p>{{ $visit->notas_clinicas }}</p>@endunless
@forelse($diagnoses->where('consulta_id',$visit->id) as $diagnosis)<h4>Diagnóstico</h4><p>{{ $diagnosis->descripcion }} {{ $diagnosis->codigo_clasificacion }}</p><p>Registro: {{ \App\Modules\Shared\Clinic::date($diagnosis->registrado_en)->format('d/m/Y H:i') }}</p>
@foreach($treatments->get($diagnosis->id,collect()) as $treatment)<h4>Tratamiento</h4><p>{{ $treatment->descripcion }}</p><p>{{ $treatment->indicaciones }}</p><p>{{ $treatment->fecha_inicio }} – {{ $treatment->fecha_fin }} · Registro: {{ \App\Modules\Shared\Clinic::date($treatment->registrado_en)->format('d/m/Y H:i') }}</p>@endforeach
@empty<p>Sin diagnósticos registrados para esta atención.</p>@endforelse</article>@empty<p>No existen atenciones clínicas registradas.</p>@endforelse
{{ $visits->links('pagination.default') }}
<h2>Resultados de laboratorio{{ $own?' publicados':'' }}</h2>
@forelse($results as $result)<article class="table-panel"><a href="{{ route('results.show',$result->id) }}">{{ $result->analisis }} · {{ \App\Modules\Shared\Clinic::date($result->fecha_registro)->format('d/m/Y H:i') }}</a><p>{{ $result->resultado }}</p></article>@empty<p>No hay resultados disponibles.</p>@endforelse
{{ $results->links('pagination.default') }}
@endsection
