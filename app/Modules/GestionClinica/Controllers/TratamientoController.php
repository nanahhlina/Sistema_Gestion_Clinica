<?php

namespace App\Modules\GestionClinica\Controllers;

use App\Modules\GestionClinica\Services\AccesoClinico;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TratamientoController extends FlujoController
{
    public function create(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'atencion.registrar');
        $options = DB::table('diagnostico')->whereIn('consulta_id', array_keys($access->consultations($request->user(), $patient)))->orderByDesc('id')->pluck('descripcion', 'id')->all();

        return $this->form($request, 'Registrar tratamiento', route('treatments.store', $patient), [
            'diagnostico_id' => ['label' => 'Diagnóstico previo propio', 'options' => $options, 'required' => true],
            'descripcion' => ['label' => 'Tratamiento', 'type' => 'textarea', 'required' => true],
            'indicaciones' => ['label' => 'Indicaciones', 'type' => 'textarea'],
            'fecha_inicio' => ['label' => 'Fecha de inicio', 'type' => 'date'],
            'fecha_fin' => ['label' => 'Fecha final', 'type' => 'date'],
        ], [], 'tratamiento.'.$patient, ['message' => $options ? 'Selecciona el diagnóstico al que corresponde el tratamiento.' : 'No hay diagnósticos propios disponibles para asociar.']);
    }

    public function store(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'atencion.registrar');
        $data = $this->validated($request, ['diagnostico_id' => 'required|integer|min:1', 'descripcion' => 'required|string|max:10000', 'indicaciones' => 'nullable|string|max:10000', 'fecha_inicio' => 'nullable|date_format:Y-m-d', 'fecha_fin' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('fecha_inicio'), 'after_or_equal:fecha_inicio')], 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $access->ownDiagnosis($request->user(), $patient, $data['diagnostico_id']);
        $operation = 'tratamiento.'.$patient;
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Revisar tratamiento', url()->current(), $data, ['Tratamiento' => $data['descripcion'], 'Indicaciones' => $data['indicaciones'] ?? '—', 'Inicio' => $data['fecha_inicio'] ?? '—', 'Fin' => $data['fecha_fin'] ?? '—'], $operation);
        }
        OperacionWeb::run($request, $operation, function () use ($access, $request, $patient, $data) {
            $access->ownDiagnosis($request->user(), $patient, $data['diagnostico_id']);
            unset($data['paso']);

            return DB::table('tratamiento')->insertGetId($data);
        });

        return redirect()->route('clinical.show', $patient)->with('success', 'Tratamiento registrado correctamente.');
    }
}
