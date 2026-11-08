<?php

namespace App\Modules\GestionPacientes\Controllers;

use App\Modules\GestionCitasAgendaMedica\Services\ConsultaAgendaService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\CatalogoController;
use App\Modules\Shared\Services\CatalogoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacienteController extends CatalogoController
{
    protected const MODULO = 'pacientes';

    public function __construct(CatalogoService $catalog, private ConsultaAgendaService $appointments)
    {
        parent::__construct($catalog);
    }

    public function citas(Request $request, int $id): View
    {
        Clinic::authorize($request->user(), 'pacientes.gestionar');
        $patient = DB::table('paciente')->where('id', $id)->first();
        abort_unless($patient, 404);
        $filters = $this->appointments->filters($request);
        unset($filters['vista']);
        if (! empty($filters['medico_id'])) {
            $request->validate(['medico_id' => ['exists:medico,id']]);
        }
        $query = $this->appointments->query()->where('c.paciente_id', $id);
        $this->appointments->filter($query, $filters);
        $rows = $query->orderByDesc('c.inicio')->orderByDesc('c.id')->paginate(20)->appends($filters);
        $title = 'Citas de '.$patient->nombres.' '.$patient->apellidos;
        $backUrl = route('catalog.index', 'pacientes');
        $filterUrl = route('appointments.patient', $id);
        $doctors = Clinic::doctors(includeInactive: true);
        $isHistory = true;
        $doctorHistory = false;
        $showReason = false;
        $states = array_combine(ConsultaAgendaService::STATES, ConsultaAgendaService::STATES);

        return view('agenda.index', compact('rows', 'filters', 'title', 'backUrl', 'filterUrl', 'doctors', 'isHistory', 'showReason', 'states', 'doctorHistory'));
    }
}
