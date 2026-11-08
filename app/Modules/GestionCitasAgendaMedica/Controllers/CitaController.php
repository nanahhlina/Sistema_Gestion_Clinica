<?php

namespace App\Modules\GestionCitasAgendaMedica\Controllers;

use App\Modules\GestionCitasAgendaMedica\Services\CitaService;
use App\Modules\GestionCitasAgendaMedica\Services\ConsultaAgendaService;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\GestionPacientes\Services\RecordatorioService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CitaController extends FlujoController
{
    protected bool $own = false;

    public function availability(Request $request, CitaService $service)
    {
        $this->own ? $service->patient($request->user()) : $service->manage($request->user());
        $data = $this->validated($request, ['paciente_id' => [$this->own ? 'prohibited' : 'required', 'integer', 'min:1'], 'medico_id' => 'required|integer|min:1', 'especialidad_id' => 'required|integer|min:1', 'fecha' => 'required|date_format:Y-m-d']);
        $patient = $this->own ? $service->patient($request->user()) : (int) $data['paciente_id'];
        abort_unless(DB::table('paciente')->where('id', $patient)->where('activo', true)->exists(), 404);
        $service->doctor($data['medico_id'], $data['especialidad_id']);
        $slots = $service->slots($data['medico_id'], $data['fecha'], $patient);

        return response()->json(['slots' => collect($slots)->map(fn ($label, $start) => ['inicio' => $start, 'label' => $label])->values()]);
    }

    public function create(Request $request, CitaService $service)
    {
        $this->own ? $service->patient($request->user()) : $service->manage($request->user());
        $filters = $this->validated($request, ['q' => 'nullable|string|max:100', 'paciente_id' => 'nullable|integer|min:1', 'especialidad_id' => 'nullable|integer|min:1', 'medico_id' => 'nullable|integer|min:1', 'fecha' => 'nullable|date_format:Y-m-d']);
        $patient = $this->own ? $service->patient($request->user()) : (int) ($filters['paciente_id'] ?? 0);
        $specialty = (int) ($filters['especialidad_id'] ?? 0);
        $doctor = (int) ($filters['medico_id'] ?? 0);
        $patients = DB::table('paciente')->where('activo', true)->when(! empty($filters['q']), fn ($q) => $q->whereRaw("concat_ws(' ',nombres,apellidos,ci) ILIKE ?", ['%'.$filters['q'].'%']))->orderBy('apellidos')->limit(50)->pluck(DB::raw("concat_ws(' ',nombres,apellidos,ci) as label"), 'id')->all();
        if ($patient && ! $this->own) {
            $p = DB::table('paciente')->where('id', $patient)->where('activo', true)->first() ?? abort(404);
            $patients[$patient] = $p->nombres.' '.$p->apellidos;
        }
        $slots = [];
        if ($doctor && $specialty) {
            $service->doctor($doctor, $specialty);
            if ($patient && ! empty($filters['fecha'])) {
                $slots = $service->slots($doctor, $filters['fecha'], $patient);
            }
        }
        $action = route($this->own ? 'reservations.store' : 'appointments.store');
        $operation = $this->own ? 'reserva' : 'cita';

        return view('appointments.create', compact('filters', 'patient', 'patients', 'specialty', 'doctor', 'slots', 'action', 'operation') + ['own' => $this->own, 'specialties' => Clinic::specialties(), 'doctors' => $specialty ? $service->choices($specialty) : [], 'key' => OperacionWeb::token()]);
    }

    public function store(Request $request, CitaService $service)
    {
        $this->own ? $service->patient($request->user()) : $service->manage($request->user());
        $data = $this->validated($request, ['paciente_id' => [$this->own ? 'prohibited' : 'required', 'integer', 'min:1'], 'medico_id' => 'required|integer|min:1', 'especialidad_id' => 'required|integer|min:1', 'inicio' => 'required|date', 'precio_consulta' => [$this->own || ! $request->user()->hasPermission('pagos.registrar') ? 'prohibited' : 'nullable', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'], 'motivo_consulta' => 'nullable|string|max:2000', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $patient = $this->own ? $service->patient($request->user()) : (int) $data['paciente_id'];
        $p = DB::table('paciente')->where('id', $patient)->where('activo', true)->first() ?? abort(404);
        $doctor = $service->doctor($data['medico_id'], $data['especialidad_id']);
        $operation = $this->own ? 'reserva' : 'cita';
        if ($data['paso'] === 'revisar') {
            $start = Clinic::date($data['inicio']);
            if (! array_key_exists($start->toIso8601String(), $service->slots($data['medico_id'], $start->toDateString(), $patient))) {
                Clinic::fail('El horario ya no está disponible. Elige otro turno.');
            }
            unset($data['paso']);

            return $this->review($request, 'Confirmar cita', url()->current(), $data, ['Paciente' => $p->nombres.' '.$p->apellidos, 'Médico' => $doctor->nombre, 'Especialidad' => $doctor->especialidad, 'Turno' => $start->format('d/m/Y H:i'), 'Precio (BOB)' => $data['precio_consulta'] ?? 'Sin definir', 'Motivo' => $data['motivo_consulta'] ?? '—'], $operation);
        }
        $id = OperacionWeb::run($request, $operation, function () use ($request, $data, $patient, $service) {
            $service->doctor($data['medico_id'], $data['especialidad_id']);
            if ($this->own) {
                return DB::selectOne('select vida_salud.reservar_cita_propia(?,?,?,?,?) as id', [$request->user()->id, $data['medico_id'], $data['especialidad_id'], Clinic::date($data['inicio']), $data['motivo_consulta'] ?? null])->id;
            }

            $id = DB::selectOne('select vida_salud.agendar_cita(?,?,?,?,?,?) as id', [$request->user()->id, $patient, $data['medico_id'], $data['especialidad_id'], Clinic::date($data['inicio']), $data['motivo_consulta'] ?? null])->id;
            if (isset($data['precio_consulta'])) {
                DB::table('cita')->where('id', $id)->update(['precio_consulta' => Importe::decimal($data['precio_consulta'])]);
            }

            return $id;
        });

        return redirect()->route('appointments.show', $id)->with('success', 'Cita registrada correctamente.');
    }

    public function show(Request $request, int $id, CitaService $service)
    {
        $row = $service->read($request->user(), $id);
        $events = DB::table('evento_cita as e')->join('usuario as u', 'u.id', '=', 'e.usuario_id')->where('e.cita_id', $id)->orderByDesc('e.id')->select('e.*')->selectRaw("concat_ws(' ',u.nombres,u.apellidos) as actor")->get();
        $recordatorios = DB::table('vida_salud.recordatorio')->where('cita_id', $id)->orderByDesc('id')->get();

        return view('appointments.show', compact('row', 'events', 'recordatorios'));
    }

    public function edit(Request $request, int $id, CitaService $service)
    {
        $service->manage($request->user());
        $row = DB::table('cita')->where('id', $id)->first() ?? abort(404);
        abort_unless(in_array($row->estado, ['Programada', 'Confirmada']), 422, 'La cita ya está finalizada.');
        $data = $this->validated($request, ['accion' => ['required', Rule::in(['cancelar', 'reprogramar', 'confirmar', 'inasistencia'])], 'fecha' => 'nullable|date_format:Y-m-d']);
        $operation = 'cita.'.$id.'.'.$data['accion'];
        $fields = ['version' => ['type' => 'hidden', 'value' => $service->version($row)]];
        if ($data['accion'] === 'cancelar') {
            $fields['motivo'] = ['label' => 'Motivo de cancelación', 'type' => 'textarea', 'required' => true, 'max' => 2000];
        }
        if ($data['accion'] === 'reprogramar') {
            $service->doctor($row->medico_id, $row->especialidad_id);
            $fields['inicio'] = ['label' => 'Nuevo turno', 'options' => empty($data['fecha']) ? [] : $service->slots($row->medico_id, $data['fecha'], $row->paciente_id, $id), 'required' => true];
        }

        return view('appointments.change', compact('row', 'data', 'fields', 'operation') + ['key' => OperacionWeb::token()]);
    }

    public function update(Request $request, int $id, CitaService $service)
    {
        $service->manage($request->user());
        $data = $this->validated($request, ['accion' => ['required', Rule::in(['cancelar', 'reprogramar', 'confirmar', 'inasistencia'])], 'version' => 'required|string|size:64', 'motivo' => 'required_if:accion,cancelar|nullable|string|max:2000', 'inicio' => 'required_if:accion,reprogramar|nullable|date', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $operation = 'cita.'.$id.'.'.$data['accion'];
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar cambio de cita', route('appointments.update', $id), $data, ['Operación' => $data['accion'], 'Nuevo turno' => $data['inicio'] ?? '—', 'Motivo' => $data['motivo'] ?? '—'], $operation);
        }
        OperacionWeb::run($request, $operation, function () use ($service, $id, $data, $request) {
            $row = $service->lock($id, $data['version']);
            if ($data['accion'] === 'cancelar') {
                DB::select('select vida_salud.cancelar_cita(?,?,?)', [$request->user()->id, $id, $data['motivo']]);
                app(RecordatorioService::class)->syncWithAppointmentChange($id, 'cancelar', null, $data['motivo'], $request->user());
            } elseif ($data['accion'] === 'reprogramar') {
                DB::select('select vida_salud.reprogramar_cita(?,?,?)', [$request->user()->id, $id, Clinic::date($data['inicio'])]);
                app(RecordatorioService::class)->syncWithAppointmentChange($id, 'reprogramar', $data['inicio'], null, $request->user());
            } else {
                DB::select('select vida_salud.cambiar_asistencia(?,?,?)', [$request->user()->id, $id, $data['accion'] === 'confirmar' ? 'Confirmada' : 'Inasistencia']);
            }

            return $row->id;
        });

        return redirect()->route('appointments.show', $id)->with('success', 'Cita actualizada correctamente.');
    }

    public function mine(Request $request, CitaService $service)
    {
        $rows = (new ConsultaAgendaService)->query()->where('c.paciente_id', $service->patient($request->user()))->orderByDesc('c.inicio')->paginate(20);

        return view('appointments.mine', compact('rows'));
    }
}
