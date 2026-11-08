<?php

namespace App\Modules\ReportesSistema\Controllers;

use App\Modules\ReportesSistema\Services\ReporteService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReporteController extends FlujoController
{
    public function index(Request $request, ReporteService $reports)
    {
        abort_unless($request->user()->roleName() === 'Administrador', 403);
        Clinic::authorize($request->user(), 'reportes.administrativos');
        $filters = $this->validated($request, [
            'tipo' => ['sometimes', Rule::in(array_keys(ReporteService::TYPES))],
            'desde' => 'nullable|date_format:Y-m-d', 'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
            'medico_id' => 'nullable|integer|min:1', 'especialidad_id' => 'nullable|integer|min:1', 'analisis_id' => 'nullable|integer|min:1',
            'estado_cita' => ['nullable', Rule::in(['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'])],
            'estado_orden' => ['nullable', Rule::in(['Pendiente', 'En proceso', 'Completada', 'Cancelada'])],
            'activo' => ['nullable', Rule::in(['0', '1'])], 'publicacion' => ['nullable', Rule::in(['publicado', 'no_publicado'])], 'page' => 'nullable|integer|min:1',
        ]);
        $filters['tipo'] ??= 'citas';

        return view('reports.index', $reports->query($filters) + ['filters' => $filters, 'types' => ReporteService::TYPES, 'doctors' => Clinic::doctors(true), 'specialties' => DB::table('especialidad')->orderBy('nombre')->pluck('nombre', 'id')->all(), 'analyses' => DB::table('analisis_laboratorio')->orderBy('nombre')->pluck('nombre', 'id')->all()]);
    }
}
