@extends('layouts.app')
@section('body-class','app-page workflow-page')
@section('title','Reportes del sistema')
@section('content')
<x-page-heading title="Reportes del sistema" description="Información administrativa y totales del conjunto filtrado." :back="route('home')" />
<form method="get" class="filters">
<x-field name="tipo" label="Tipo de reporte" type="select" :options="$types" :value="$filters['tipo']" />
<button class="button">Elegir tipo de reporte</button>
</form>
<form method="get" class="filters filter-grid">
<input type="hidden" name="tipo" value="{{ $filters['tipo'] }}">
<x-field name="desde" label="Desde" type="date" :value="$filters['desde'] ?? null" /><x-field name="hasta" label="Hasta (día completo)" type="date" :value="$filters['hasta'] ?? null" />
@if($filters['tipo']!=='pacientes')
<x-field name="medico_id" label="Médico" type="select" :options="[''=>'Todos']+$doctors" :value="$filters['medico_id'] ?? null" />
<x-field name="especialidad_id" label="Especialidad" type="select" :options="[''=>'Todas']+$specialties" :value="$filters['especialidad_id'] ?? null" />
@endif
@if($filters['tipo']==='citas')
<x-field name="estado_cita" label="Estado de cita" type="select" :options="[''=>'Todos','Programada'=>'Programada','Confirmada'=>'Confirmada','Atendida'=>'Atendida','Cancelada'=>'Cancelada','Inasistencia'=>'Inasistencia']" :value="$filters['estado_cita'] ?? null" />
@endif
@if($filters['tipo']==='ordenes')
<x-field name="estado_orden" label="Estado de orden" type="select" :options="[''=>'Todos','Pendiente'=>'Pendiente','En proceso'=>'En proceso','Completada'=>'Completada','Cancelada'=>'Cancelada']" :value="$filters['estado_orden'] ?? null" />
@endif
@if($filters['tipo']==='pacientes')
<x-field name="activo" label="Estado del paciente" type="select" :options="[''=>'Todos','1'=>'Activo','0'=>'Inactivo']" :value="$filters['activo'] ?? null" />
@endif
@if($filters['tipo']==='resultados')
<x-field name="analisis_id" label="Análisis" type="select" :options="[''=>'Todos']+$analyses" :value="$filters['analisis_id'] ?? null" />
<x-field name="publicacion" label="Publicación del resultado" type="select" :options="[''=>'Todos','publicado'=>'Publicado','no_publicado'=>'No publicado']" :value="$filters['publicacion'] ?? null" />
@endif
<button class="button button-primary">Generar reporte</button>
</form>
<h2>{{ $types[$filters['tipo']] }}</h2><dl>@foreach($totals as $label=>$total)<dt>{{ $label }}</dt><dd>{{ $total }}</dd>@endforeach</dl>
@if($rows->count())<div class="table-panel"><div class="table-scroll" role="region" aria-label="Registros del reporte" tabindex="0"><table class="workflow-table"><caption>Registros administrativos del reporte</caption><thead><tr>@foreach(array_keys((array)$rows->first()) as $column)<th scope="col">{{ ucfirst(str_replace('_',' ',$column)) }}</th>@endforeach</tr></thead><tbody>
@foreach($rows as $row)<tr>@foreach((array)$row as $column=>$value)<td>@if(in_array($column,['inicio','fin','creado_en','fecha_atencion','fecha_solicitud','fecha_registro'])){{ \App\Modules\Shared\Clinic::date($value)->format('d/m/Y H:i') }}@elseif($column==='activo'){{ $value?'Activo':'Inactivo' }}@else{{ $value }}@endif</td>@endforeach</tr>@endforeach
</tbody></table></div></div>{{ $rows->links('pagination.default') }}@else<p>No hay información disponible con los criterios seleccionados.</p>@endif
@endsection
