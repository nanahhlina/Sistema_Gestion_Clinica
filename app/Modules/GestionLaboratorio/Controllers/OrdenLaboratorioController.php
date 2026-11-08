<?php

namespace App\Modules\GestionLaboratorio\Controllers;

use App\Modules\GestionClinica\Services\AccesoClinico;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrdenLaboratorioController extends FlujoController
{
    public function create(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'laboratorio.solicitar');
        $access->doctor($request->user(), 'laboratorio.solicitar');
        $consultations = DB::table('consulta as co')->join('cita as c', 'c.id', '=', 'co.cita_id')->where('c.paciente_id', $patient)->where('co.registrada_por', $request->user()->id)->orderByDesc('co.fecha_atencion')->pluck('co.fecha_atencion', 'co.id')->all();

        return $this->form($request, 'Solicitar análisis de laboratorio', route('orders.store', $patient), [
            'consulta_id' => ['label' => 'Consulta propia', 'options' => $consultations, 'required' => true],
            'analisis_ids[]' => ['label' => 'Análisis solicitados (puedes seleccionar varios)', 'options' => DB::table('analisis_laboratorio')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all(), 'multiple' => true, 'required' => true],
            'indicaciones' => ['label' => 'Indicaciones / observaciones', 'type' => 'textarea'],
        ], [], 'orden.'.$patient, ['message' => $consultations ? 'Selecciona los análisis requeridos.' : 'Registra primero una atención médica para este paciente.']);
    }

    public function store(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'laboratorio.solicitar');
        $data = $this->validated($request, ['consulta_id' => 'required|integer|min:1', 'analisis_ids' => 'required|array|min:1|max:100', 'analisis_ids.*' => 'required|integer|min:1|distinct', 'indicaciones' => 'nullable|string|max:10000', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $access->ownConsultation($request->user(), $patient, $data['consulta_id'], 'laboratorio.solicitar');
        $analyses = DB::table('analisis_laboratorio')->where('activo', true)->whereIn('id', $data['analisis_ids'])->pluck('nombre', 'id');
        abort_unless($analyses->count() === count($data['analisis_ids']), 422, 'Hay análisis inexistentes o desactivados.');
        $operation = 'orden.'.$patient;
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Revisar orden', url()->current(), $data, ['Análisis' => $analyses->implode(', '), 'Indicaciones' => $data['indicaciones'] ?? '—'], $operation);
        }
        $id = OperacionWeb::run($request, $operation, function () use ($request, $patient, $access, $data) {
            $access->ownConsultation($request->user(), $patient, $data['consulta_id'], 'laboratorio.solicitar');
            abort_unless(DB::table('analisis_laboratorio')->whereIn('id', $data['analisis_ids'])->where('activo', true)->sharedLock()->get()->count() === count($data['analisis_ids']), 422, 'Un análisis fue desactivado.');
            $id = DB::table('orden_laboratorio')->insertGetId(['consulta_id' => $data['consulta_id'], 'solicitada_por' => $request->user()->id, 'indicaciones' => $data['indicaciones'] ?? null]);
            foreach ($data['analisis_ids'] as $analysis) {
                DB::table('orden_analisis')->insert(['orden_id' => $id, 'analisis_id' => $analysis]);
            }

            return $id;
        });

        return redirect()->route('orders.show', $id)->with('success', 'Orden registrada correctamente.');
    }
}
