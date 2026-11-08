<?php

namespace App\Modules\GestionClinica\Controllers;

use App\Modules\GestionClinica\Services\AccesoClinico;
use App\Modules\GestionLaboratorio\Services\LaboratorioService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HistoriaClinicaController extends FlujoController
{
    private function medicalVersion(?object $history): string
    {
        return hash('sha256', json_encode([$history->enfermedades_cronicas ?? null, $history->medicamentos_actuales ?? null, $history->actualizada_en ?? null]));
    }

    public function editMedical(Request $request, int $patient, AccesoClinico $access)
    {
        $access->doctor($request->user(), 'atencion.registrar');
        $access->patient($request->user(), $patient);
        $history = DB::table('historia_clinica')->where('paciente_id', $patient)->first();

        return $this->form($request, 'Actualizar datos médicos', route('clinical.medical.save', $patient), [
            'enfermedades_cronicas' => ['label' => 'Enfermedades crónicas', 'type' => 'textarea', 'max' => 10000],
            'medicamentos_actuales' => ['label' => 'Medicamentos actuales', 'type' => 'textarea', 'max' => 10000],
            'version' => ['type' => 'hidden'],
        ], ['enfermedades_cronicas' => $history->enfermedades_cronicas ?? '', 'medicamentos_actuales' => $history->medicamentos_actuales ?? '', 'version' => $this->medicalVersion($history)], 'datos_medicos.'.$patient);
    }

    public function saveMedical(Request $request, int $patient, AccesoClinico $access)
    {
        $access->doctor($request->user(), 'atencion.registrar');
        $access->patient($request->user(), $patient);
        $data = $this->validated($request, ['enfermedades_cronicas' => 'nullable|string|max:10000', 'medicamentos_actuales' => 'nullable|string|max:10000', 'version' => 'required|string|size:64', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar datos médicos', route('clinical.medical.save', $patient), $data, ['Enfermedades crónicas' => $data['enfermedades_cronicas'] ?? 'Sin registrar', 'Medicamentos actuales' => $data['medicamentos_actuales'] ?? 'Sin registrar'], 'datos_medicos.'.$patient);
        }
        OperacionWeb::run($request, 'datos_medicos.'.$patient, function () use ($request, $patient, $access, $data) {
            DB::table('paciente')->where('id', $patient)->lockForUpdate()->first();
            $access->patient($request->user(), $patient);
            $history = DB::table('historia_clinica')->where('paciente_id', $patient)->lockForUpdate()->first();
            if (! hash_equals($this->medicalVersion($history), $data['version'])) {
                Clinic::fail('Los datos médicos cambiaron. Consulta el historial nuevamente.');
            }
            $values = ['enfermedades_cronicas' => $data['enfermedades_cronicas'] ?? null, 'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
                'actualizada_por' => $request->user()->id, 'actualizada_en' => DB::raw('clock_timestamp()')];
            if ($history) {
                DB::table('historia_clinica')->where('paciente_id', $patient)->update($values);
            } else {
                DB::table('historia_clinica')->insert(['paciente_id' => $patient, 'registrada_por' => $request->user()->id] + $values);
            }

            return $patient;
        });

        return redirect()->route('clinical.show', $patient)->with('success', 'Datos médicos actualizados.');
    }

    public function index(Request $request, AccesoClinico $access)
    {
        $doctor = $access->doctor($request->user(), 'historia.consultar');
        $data = $this->validated($request, ['q' => 'nullable|string|min:2|max:100', 'page' => 'nullable|integer|min:1']);
        $query = $access->scope(DB::table('paciente as p'), $doctor);
        if (! empty($data['q'])) {
            $query->whereRaw("concat_ws(' ',p.nombres,p.apellidos,p.ci) ILIKE ?", ['%'.$data['q'].'%']);
        }
        $rows = $query->orderBy('p.apellidos')->orderBy('p.id')->paginate(20)->withQueryString();

        return view('clinical.patients', compact('rows'));
    }

    public function mine(Request $request, AccesoClinico $access, LaboratorioService $lab)
    {
        return $this->show($request, Clinic::patientId($request->user()), $access, $lab);
    }

    public function show(Request $request, int $patient, AccesoClinico $access, LaboratorioService $lab)
    {
        $p = $access->patient($request->user(), $patient);
        $this->validated($request, ['page' => 'nullable|integer|min:1', 'resultados_page' => 'nullable|integer|min:1']);
        $own = $request->user()->isPatient();
        $history = DB::table('historia_clinica')->where('paciente_id', $patient)->first();
        $visits = DB::table('consulta as co')->join('cita as c', 'c.id', '=', 'co.cita_id')->join('usuario as u', 'u.id', '=', 'co.registrada_por')->where('c.paciente_id', $patient)
            ->select('co.id', 'co.fecha_atencion')->when(! $own, fn ($q) => $q->addSelect('co.notas_clinicas'))->selectRaw("concat_ws(' ',u.nombres,u.apellidos) as medico")
            ->orderByDesc('co.fecha_atencion')->orderByDesc('co.id')->paginate(10)->withQueryString();
        $diagnoses = DB::table('diagnostico')->whereIn('consulta_id', $visits->pluck('id'))->orderBy('registrado_en')->get();
        $treatments = DB::table('tratamiento')->whereIn('diagnostico_id', $diagnoses->pluck('id'))->orderBy('registrado_en')->get()->groupBy('diagnostico_id');
        $results = $lab->results($request->user())->where('c.paciente_id', $patient)->orderByDesc('r.fecha_registro')->paginate(10, ['*'], 'resultados_page')->withQueryString();

        return view('clinical.history', compact('p', 'history', 'visits', 'diagnoses', 'treatments', 'results', 'own'));
    }
}
