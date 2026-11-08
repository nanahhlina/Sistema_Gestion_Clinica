<?php

namespace App\Modules\GestionClinica\Controllers;

use App\Modules\GestionClinica\Services\AccesoClinico;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultaController extends FlujoController
{
    public function create(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'atencion.registrar');
        $doctor = $access->doctor($request->user(), 'atencion.registrar');
        $options = DB::table('cita')->where('paciente_id', $patient)->where('medico_id', $doctor)->whereIn('estado', ['Programada', 'Confirmada', 'Atendida'])->where('inicio', '<=', now())->orderByDesc('inicio')->pluck(DB::raw("codigo || ' · ' || to_char(inicio AT TIME ZONE 'America/La_Paz','DD/MM/YYYY HH24:MI') as label"), 'id')->all();

        return $this->form($request, 'Registrar diagnóstico', route('diagnoses.store', $patient), [
            'cita_id' => ['label' => 'Atención / cita propia', 'options' => $options, 'required' => true],
            'descripcion' => ['label' => 'Diagnóstico', 'type' => 'textarea', 'required' => true],
            'codigo_clasificacion' => ['label' => 'Código de clasificación (opcional)', 'max' => 30],
        ], [], 'diagnostico.'.$patient, ['message' => $options ? 'Selecciona una cita cuya hora de inicio ya llegó.' : 'No existen citas propias elegibles para registrar atención.']);
    }

    public function store(Request $request, int $patient, AccesoClinico $access)
    {
        $access->patient($request->user(), $patient, 'atencion.registrar');
        $data = $this->validated($request, ['cita_id' => 'required|integer|min:1', 'descripcion' => 'required|string|max:10000', 'codigo_clasificacion' => 'nullable|string|max:30', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $doctor = $access->doctor($request->user(), 'atencion.registrar');
        $eligible = fn () => DB::table('cita')->where('id', $data['cita_id'])->where('paciente_id', $patient)->where('medico_id', $doctor)->whereIn('estado', ['Programada', 'Confirmada', 'Atendida'])->where('inicio', '<=', now());
        abort_unless($eligible()->exists(), 404);
        $operation = 'diagnostico.'.$patient;
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Revisar diagnóstico', url()->current(), $data, ['Diagnóstico' => $data['descripcion'], 'Clasificación' => $data['codigo_clasificacion'] ?? '—'], $operation);
        }
        OperacionWeb::run($request, $operation, function () use ($data, $request, $eligible) {
            abort_unless($eligible()->lockForUpdate()->first(), 404);
            $co = DB::table('consulta')->where('cita_id', $data['cita_id'])->first();
            $id = $co?->id ?? DB::table('consulta')->insertGetId(['cita_id' => $data['cita_id'], 'registrada_por' => $request->user()->id]);

            return DB::table('diagnostico')->insertGetId(['consulta_id' => $id, 'descripcion' => trim($data['descripcion']), 'codigo_clasificacion' => $data['codigo_clasificacion'] ?? null]);
        });

        return redirect()->route('clinical.show', $patient)->with('success', 'Diagnóstico registrado correctamente.');
    }
}
