<?php

namespace App\Modules\ReportesSistema\Services;

use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public const TYPES = ['citas' => 'Citas', 'pacientes' => 'Pacientes registrados', 'atenciones' => 'Atenciones médicas', 'ordenes' => 'Órdenes de laboratorio', 'resultados' => 'Resultados de laboratorio'];

    public function query(array $filters): array
    {
        $type = $filters['tipo'];
        if ($type === 'pacientes') {
            $query = DB::table('paciente as p')->select('p.id', 'p.nombres', 'p.apellidos', 'p.activo', 'p.creado_en');
            $date = 'p.creado_en';
            $id = 'p.id';
            if (isset($filters['activo']) && $filters['activo'] !== '') {
                $query->where('p.activo', (bool) $filters['activo']);
            }
        } else {
            $query = DB::table('cita as c')->join('paciente as p', 'p.id', '=', 'c.paciente_id')->join('medico as m', 'm.id', '=', 'c.medico_id')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('especialidad as e', 'e.id', '=', 'c.especialidad_id');
            if ($type === 'citas') {
                $query->select('c.codigo', 'c.inicio', 'c.fin', 'c.estado');
                $date = 'c.inicio';
                $id = 'c.id';
                if (! empty($filters['estado_cita'])) {
                    $query->where('c.estado', $filters['estado_cita']);
                }
            } else {
                $query->join('consulta as co', 'co.cita_id', '=', 'c.id');
                if ($type === 'atenciones') {
                    $query->select('co.id', 'co.fecha_atencion');
                    $date = 'co.fecha_atencion';
                    $id = 'co.id';
                } else {
                    $query->join('orden_laboratorio as o', 'o.consulta_id', '=', 'co.id');
                    if ($type === 'ordenes') {
                        $query->select('o.id', 'o.fecha_solicitud', 'o.estado')
                            ->selectSub(DB::table('orden_analisis as oa')->selectRaw('count(*)')->whereColumn('oa.orden_id', 'o.id'), 'analisis_solicitados')
                            ->selectSub(DB::table('orden_analisis as oa')->join('resultado_laboratorio as rl', 'rl.orden_analisis_id', '=', 'oa.id')->selectRaw('count(*)')->whereColumn('oa.orden_id', 'o.id'), 'resultados_registrados');
                        $date = 'o.fecha_solicitud';
                        $id = 'o.id';
                        if (! empty($filters['estado_orden'])) {
                            $query->where('o.estado', $filters['estado_orden']);
                        }
                    } else {
                        $query->join('orden_analisis as oa', 'oa.orden_id', '=', 'o.id')->join('analisis_laboratorio as a', 'a.id', '=', 'oa.analisis_id')->join('resultado_laboratorio as r', 'r.orden_analisis_id', '=', 'oa.id')
                            ->select('r.id', 'r.fecha_registro', 'o.id as orden_id', 'a.nombre as analisis')->selectRaw("CASE WHEN r.publicado_en IS NULL THEN 'No publicado' ELSE 'Publicado' END as publicacion");
                        $date = 'r.fecha_registro';
                        $id = 'r.id';
                        if (! empty($filters['analisis_id'])) {
                            $query->where('a.id', $filters['analisis_id']);
                        }
                        if (($filters['publicacion'] ?? '') === 'publicado') {
                            $query->whereNotNull('r.publicado_en');
                        }
                        if (($filters['publicacion'] ?? '') === 'no_publicado') {
                            $query->whereNull('r.publicado_en');
                        }
                    }
                }
            }
            $query->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente,concat_ws(' ',u.nombres,u.apellidos) as medico,e.nombre as especialidad");
            if (! empty($filters['medico_id'])) {
                $query->where('c.medico_id', $filters['medico_id']);
            }
            if (! empty($filters['especialidad_id'])) {
                $query->where('c.especialidad_id', $filters['especialidad_id']);
            }
        }
        if (! empty($filters['desde'])) {
            $query->where($date, '>=', Clinic::date($filters['desde'])->startOfDay());
        }
        if (! empty($filters['hasta'])) {
            $query->where($date, '<', Clinic::date($filters['hasta'])->addDay()->startOfDay());
        }
        $totals = ['Registros' => (clone $query)->count()];
        if ($type === 'citas') {
            foreach ((clone $query)->reorder()->select('c.estado')->selectRaw('count(*) as cantidad')->groupBy('c.estado')->get() as $row) {
                $totals[$row->estado] = (int) $row->cantidad;
            }
        }
        if ($type === 'ordenes') {
            $aggregate = DB::query()->fromSub(clone $query, 'reporte')->selectRaw('coalesce(sum(analisis_solicitados),0) as analisis,coalesce(sum(resultados_registrados),0) as resultados')->first();
            $totals['Análisis solicitados'] = (int) $aggregate->analisis;
            $totals['Resultados registrados'] = (int) $aggregate->resultados;
        }
        $rows = $query->orderByDesc($date)->orderByDesc($id)->paginate(20)->withQueryString();

        return compact('rows', 'totals');
    }
}
