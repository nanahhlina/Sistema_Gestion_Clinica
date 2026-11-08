<?php

namespace App\Modules\GestionCitasAgendaMedica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionCitasAgendaMedica\Services\ConsultaAgendaService;
use App\Modules\Shared\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AgendaMedicaController extends Controller
{
    public function __construct(private ConsultaAgendaService $appointments) {}

    public function index(Request $request): View|Response|JsonResponse
    {
        return $this->agenda($request);
    }

    public function doctor(Request $request, int $id): View|Response|JsonResponse
    {
        return $this->agenda($request, $id);
    }

    private function agenda(Request $request, ?int $doctorId = null): View|Response|JsonResponse
    {
        $scope = $this->appointments->doctorScope($request->user());
        if ($scope !== null) {
            abort_if($doctorId !== null && $scope !== $doctorId, 404);
        }
        $filters = $this->appointments->filters($request);
        $filters['vista'] ??= 'agenda';
        $doctorHistory = $filters['vista'] === 'historial';
        if ($scope !== null && ! empty($filters['medico_id'])) {
            abort_if((int) $filters['medico_id'] !== $scope, 404);
        }
        if ($doctorId !== null && ! empty($filters['medico_id'])) {
            abort_if((int) $filters['medico_id'] !== $doctorId, 404);
        }
        $fixedDoctor = $scope ?? $doctorId;
        $doctor = $fixedDoctor === null ? null : DB::table('medico as m')
            ->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('rol as r', 'r.id', '=', 'u.rol_id')->where('m.id', $fixedDoctor)
            ->select('m.id')->selectRaw("concat_ws(' ', u.nombres, u.apellidos) as nombre, (m.activo AND u.activo AND r.activo) as habilitado")->first();
        abort_if($fixedDoctor !== null && ! $doctor, 404);
        if (! $doctorHistory && ! $request->has('desde') && ! $request->has('hasta')) {
            $filters['desde'] = $filters['hasta'] = now('America/La_Paz')->toDateString();
        }
        if ($fixedDoctor !== null) {
            $filters['medico_id'] = $fixedDoctor;
        } elseif (! empty($filters['medico_id'])) {
            $request->validate(['medico_id' => ['exists:medico,id']]);
        }
        $selectedId = $filters['medico_id'] ?? null;
        $selected = $doctor ?? ($selectedId ? DB::table('medico as m')
            ->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('rol as r', 'r.id', '=', 'u.rol_id')
            ->where('m.id', $selectedId)->select('m.id')
            ->selectRaw('(m.activo AND u.activo AND r.activo) as habilitado')->first() : null);
        if (! $doctorHistory && $selected && ! $selected->habilitado) {
            $message = 'El médico seleccionado no está habilitado. Selecciona otro médico o consulta su historial de citas.';
            $historyUrl = route($doctorId === null ? 'agenda.index' : 'agenda.doctor',
                array_merge($doctorId === null ? [] : ['id' => $doctorId], array_diff_key($filters, ['page' => true]), ['vista' => 'historial']));
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['medico_id' => [$message]], 'historial' => $historyUrl], 422);
            }

            $canSelectDoctor = $scope === null;
            $doctors = $canSelectDoctor ? Clinic::doctors() : [];

            return response()->view('agenda.unavailable', compact('message', 'historyUrl', 'canSelectDoctor', 'doctors'), 422);
        }
        $showReason = $scope !== null && $request->user()->hasPermission('atencion.registrar');
        $query = $this->appointments->query($showReason);
        if (! $doctorHistory) {
            $this->appointments->enabledDoctors($query);
        }
        $this->appointments->filter($query, $filters);
        $rows = $query->orderBy('c.inicio')->orderBy('c.id')->paginate(20)->appends($filters);
        $title = $doctorHistory
            ? ($doctor ? 'Historial de citas de '.$doctor->nombre.($doctor->habilitado ? '' : ' (inactivo)') : 'Historial de citas por médico')
            : ($doctor ? 'Agenda de '.$doctor->nombre : 'Agenda médica');
        $backUrl = $scope === null && $doctorId !== null ? route('catalog.index', 'medicos') : route('home');
        $filterUrl = $doctorId === null ? route('agenda.index') : route('agenda.doctor', $doctorId);
        $doctors = $fixedDoctor === null ? Clinic::doctors(includeInactive: $doctorHistory) : null;
        $isHistory = false;
        $states = array_combine(ConsultaAgendaService::STATES, ConsultaAgendaService::STATES);

        return view('agenda.index', compact('rows', 'filters', 'title', 'backUrl', 'filterUrl', 'doctors', 'isHistory', 'showReason', 'states', 'doctorHistory'));
    }
}
