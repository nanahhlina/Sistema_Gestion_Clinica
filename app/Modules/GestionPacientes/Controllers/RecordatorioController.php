<?php

namespace App\Modules\GestionPacientes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionPacientes\Services\RecordatorioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecordatorioController extends Controller
{
    public function __construct(private RecordatorioService $service) {}

    private function authorizeManager(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user->hasPermission('recordatorios.gestionar')
            || ($user->hasPermission('citas.gestionar') && ! $user->isDoctor() && ! $user->isPatient()),
            403,
            'No tienes autorización para gestionar recordatorios.'
        );
    }

    public function index(Request $request): View
    {
        $this->authorizeManager($request);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(array_keys(RecordatorioService::ESTADOS))],
            'destinatario' => ['nullable', Rule::in(array_keys(RecordatorioService::DESTINATARIOS))],
            'medio' => ['nullable', Rule::in(array_keys(RecordatorioService::MEDIOS))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $this->service->query($filters);

        $stats = [
            'total' => DB::table('vida_salud.recordatorio')->count(),
            'programados' => DB::table('vida_salud.recordatorio')->where('estado', 'Programado')->count(),
            'enviados' => DB::table('vida_salud.recordatorio')->where('estado', 'Enviado')->count(),
            'fallidos' => DB::table('vida_salud.recordatorio')->where('estado', 'Fallido')->count(),
            'cancelados' => DB::table('vida_salud.recordatorio')->where('estado', 'Cancelado')->count(),
        ];

        $rows = $query->orderByDesc('r.id')->paginate(20)->withQueryString();

        return view('recordatorios.index', compact('rows', 'filters', 'stats'));
    }

    public function create(Request $request): View
    {
        $this->authorizeManager($request);

        $patientId = $request->query('paciente_id') ? (int) $request->query('paciente_id') : null;
        $citaId = $request->query('cita_id') ? (int) $request->query('cita_id') : null;
        $search = trim((string) $request->query('q', ''));

        $selectedCita = null;
        $selectedPatient = null;
        $patientAppointments = [];

        if ($citaId) {
            $selectedCita = $this->service->getAppointment($citaId);
            if ($selectedCita) {
                $patientId = $selectedCita->paciente_id;
            }
        }

        if ($patientId) {
            $selectedPatient = DB::table('vida_salud.paciente')->where('id', $patientId)->first();
            if ($selectedPatient) {
                $patientAppointments = $this->service->patientAppointments($patientId);
                if (! $selectedCita && ! empty($patientAppointments)) {
                    $selectedCita = $this->service->getAppointment($patientAppointments[0]->id);
                    $citaId = $selectedCita?->id;
                }
            }
        }

        $patients = DB::table('vida_salud.paciente as p')
            ->where('p.activo', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->whereRaw("concat_ws(' ', p.nombres, p.apellidos, p.ci) ILIKE ?", ['%'.$search.'%']);
            })
            ->orderBy('p.apellidos')
            ->limit(30)
            ->pluck(DB::raw("concat_ws(' ', p.nombres, p.apellidos, '· CI:', p.ci) as label"), 'p.id')
            ->all();

        $defaultMessage = $selectedCita ? $this->service->defaultMessage($selectedCita, 'Paciente') : '';

        return view('recordatorios.create', compact(
            'patients',
            'patientId',
            'selectedPatient',
            'selectedCita',
            'patientAppointments',
            'citaId',
            'search',
            'defaultMessage'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        $data = $request->validate([
            'cita_id' => ['required', 'integer', 'min:1'],
            'destinatario' => ['required', Rule::in(['Paciente', 'Medico', 'Ambos'])],
            'medio' => ['required', Rule::in(['Correo', 'SMS', 'WhatsApp', 'Sistema'])],
            'momento_tipo' => ['required', Rule::in(['inmediato', '24h_antes', '2h_antes', '1d_8am', 'personalizado'])],
            'fecha_personalizada' => ['nullable', 'required_if:momento_tipo,personalizado', 'date'],
            'mensaje' => ['nullable', 'string', 'max:2000'],
            'simular_fallo' => ['nullable', 'boolean'],
        ]);

        $id = $this->service->create($request->user(), $data);

        $row = $this->service->find($id);
        $msg = $row->estado === 'Enviado'
            ? 'Recordatorio registrado y enviado exitosamente.'
            : ($row->estado === 'Fallido'
                ? 'Recordatorio registrado. Atención: Ocurrió un fallo en el envío, revise los detalles.'
                : 'Recordatorio registrado y programado correctamente.');

        return redirect()->route('reminders.show', $id)->with('success', $msg);
    }

    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $isScoped = $user->isPatient() || $user->isDoctor();
        $row = $this->service->find($id, $isScoped ? $user : null);
        $history = $this->service->history($id);

        $citaActual = $this->service->getAppointment($row->cita_id);

        return view('recordatorios.show', compact('row', 'history', 'citaActual'));
    }

    public function edit(Request $request, int $id): View
    {
        $this->authorizeManager($request);
        $row = $this->service->find($id);

        abort_unless(in_array($row->estado, ['Programado', 'Fallido'], true), 422, 'Solo se pueden editar recordatorios pendientes o fallidos.');

        $cita = $this->service->getAppointment($row->cita_id);

        return view('recordatorios.edit', compact('row', 'cita'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManager($request);

        $data = $request->validate([
            'destinatario' => ['required', Rule::in(['Paciente', 'Medico', 'Ambos'])],
            'medio' => ['required', Rule::in(['Correo', 'SMS', 'WhatsApp', 'Sistema'])],
            'fecha_programada' => ['required', 'date'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->update($request->user(), $id, $data);

        return redirect()->route('reminders.show', $id)->with('success', 'Recordatorio actualizado correctamente.');
    }

    public function cancelForm(Request $request, int $id): View
    {
        $this->authorizeManager($request);
        $row = $this->service->find($id);

        abort_unless(in_array($row->estado, ['Programado', 'Fallido'], true), 422, 'El recordatorio no se puede cancelar.');

        return view('recordatorios.cancel', compact('row'));
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManager($request);

        $data = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'max:500'],
        ]);

        $this->service->cancel($request->user(), $id, $data['motivo_cancelacion']);

        return redirect()->route('reminders.show', $id)->with('success', 'Recordatorio cancelado correctamente.');
    }

    public function sendNow(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManager($request);

        $simulateFail = (bool) $request->input('simular_fallo', false);
        $success = $this->service->send($request->user(), $id, $simulateFail);

        if ($success) {
            return redirect()->route('reminders.show', $id)->with('success', 'Recordatorio enviado exitosamente.');
        }

        return redirect()->route('reminders.show', $id)->with('warning', 'Ocurrió un error al enviar el recordatorio. Se registró el fallo para revisión.');
    }

    public function processScheduled(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        $result = $this->service->processScheduled($request->user());

        $msg = "Proceso completado. Recordatorios pendientes: {$result['total']}, enviados: {$result['enviados']}, fallidos: {$result['fallidos']}.";

        return redirect()->route('reminders.index')->with('success', $msg);
    }

    public function syncAppointment(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManager($request);
        $row = $this->service->find($id);
        $cita = $this->service->getAppointment($row->cita_id);

        if ($cita->estado === 'Cancelada') {
            $this->service->cancel($request->user(), $id, 'Cancelado en sincronización con la cita médica cancelada.');

            return redirect()->route('reminders.show', $id)->with('success', 'El recordatorio fue cancelado debido a la cancelación de la cita.');
        }

        $this->service->syncWithAppointmentChange($row->cita_id, 'reprogramar', $cita->inicio, null, $request->user());

        return redirect()->route('reminders.show', $id)->with('success', 'El recordatorio fue sincronizado con el nuevo horario de la cita.');
    }

    public function patientIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isPatient(), 403);

        $rows = $this->service->query([], $user)->orderByDesc('r.fecha_programada')->paginate(20);

        return view('recordatorios.patient', compact('rows'));
    }

    public function doctorIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isDoctor(), 403);

        $rows = $this->service->query([], $user)->orderByDesc('r.fecha_programada')->paginate(20);

        return view('recordatorios.doctor', compact('rows'));
    }
}
