<?php

namespace App\Modules\GestionPacientes\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordatorioService
{
    public const MEDIOS = [
        'Correo' => 'Correo electrónico',
        'SMS' => 'Mensaje de texto SMS',
        'WhatsApp' => 'WhatsApp',
        'Sistema' => 'Notificación interna / Portal',
    ];

    public const DESTINATARIOS = [
        'Paciente' => 'Paciente',
        'Medico' => 'Médico',
        'Ambos' => 'Ambos (Paciente y Médico)',
    ];

    public const ESTADOS = [
        'Programado' => 'Programado',
        'Enviado' => 'Enviado',
        'Fallido' => 'Con fallo de envío',
        'Cancelado' => 'Cancelado',
    ];

    public function query(array $filters = [], ?User $scopedUser = null): Builder
    {
        $query = DB::table('vida_salud.recordatorio as r')
            ->join('vida_salud.cita as c', 'c.id', '=', 'r.cita_id')
            ->join('vida_salud.paciente as p', 'p.id', '=', 'r.paciente_id')
            ->join('vida_salud.medico as m', 'm.id', '=', 'r.medico_id')
            ->join('vida_salud.usuario as um', 'um.id', '=', 'm.usuario_id')
            ->join('vida_salud.especialidad as e', 'e.id', '=', 'c.especialidad_id')
            ->leftJoin('vida_salud.usuario as ur', 'ur.id', '=', 'r.registrado_por')
            ->select(
                'r.*',
                'c.codigo as cita_codigo',
                'c.inicio as cita_inicio',
                'c.fin as cita_fin',
                'c.estado as cita_estado',
                'c.motivo_cancelacion as cita_motivo_cancelacion',
                'e.nombre as especialidad',
                'p.nombres as paciente_nombres',
                'p.apellidos as paciente_apellidos',
                'p.ci as paciente_ci',
                'p.telefono as paciente_telefono',
                'p.correo as paciente_correo',
                'um.nombres as medico_nombres',
                'um.apellidos as medico_apellidos',
                'um.correo as medico_correo',
                'm.telefono as medico_telefono'
            )
            ->selectRaw("concat_ws(' ', p.nombres, p.apellidos) as paciente_nombre")
            ->selectRaw("concat_ws(' ', um.nombres, um.apellidos) as medico_nombre")
            ->selectRaw("concat_ws(' ', ur.nombres, ur.apellidos) as registrado_por_nombre");

        if ($scopedUser) {
            if ($scopedUser->isPatient()) {
                $query->where(function ($q) use ($scopedUser) {
                    $q->where('r.paciente_id', Clinic::patientId($scopedUser))
                        ->whereIn('r.destinatario', ['Paciente', 'Ambos']);
                });
            } elseif ($scopedUser->isDoctor()) {
                $query->where(function ($q) use ($scopedUser) {
                    $q->where('r.medico_id', Clinic::doctorId($scopedUser))
                        ->whereIn('r.destinatario', ['Medico', 'Ambos']);
                });
            }
        }

        if (! empty($filters['estado'])) {
            $query->where('r.estado', $filters['estado']);
        }

        if (! empty($filters['destinatario'])) {
            $query->where('r.destinatario', $filters['destinatario']);
        }

        if (! empty($filters['medio'])) {
            $query->where('r.medio', $filters['medio']);
        }

        if (! empty($filters['cita_id'])) {
            $query->where('r.cita_id', $filters['cita_id']);
        }

        if (! empty($filters['paciente_id'])) {
            $query->where('r.paciente_id', $filters['paciente_id']);
        }

        if (! empty($filters['medico_id'])) {
            $query->where('r.medico_id', $filters['medico_id']);
        }

        if (! empty($filters['desde'])) {
            $query->where('r.fecha_programada', '>=', Clinic::date($filters['desde'])->startOfDay());
        }

        if (! empty($filters['hasta'])) {
            $query->where('r.fecha_programada', '<', Clinic::date($filters['hasta'])->addDay()->startOfDay());
        }

        if (! empty($filters['q'])) {
            $search = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw("concat_ws(' ', p.nombres, p.apellidos, p.ci) ILIKE ?", [$search])
                    ->orWhereRaw("concat_ws(' ', um.nombres, um.apellidos) ILIKE ?", [$search])
                    ->orWhere('c.codigo', 'ILIKE', $search)
                    ->orWhere('r.mensaje', 'ILIKE', $search)
                    ->orWhere('r.contacto_destino', 'ILIKE', $search);
            });
        }

        return $query;
    }

    public function find(int $id, ?User $scopedUser = null): object
    {
        $item = $this->query([], $scopedUser)->where('r.id', $id)->first();
        abort_unless($item, 404, 'El recordatorio solicitado no existe o no tienes acceso.');

        return $item;
    }

    public function history(int $recordatorioId): array
    {
        return DB::table('vida_salud.historial_recordatorio as h')
            ->leftJoin('vida_salud.usuario as u', 'u.id', '=', 'h.usuario_id')
            ->where('h.recordatorio_id', $recordatorioId)
            ->select('h.*')
            ->selectRaw("concat_ws(' ', u.nombres, u.apellidos) as usuario_nombre")
            ->orderByDesc('h.id')
            ->get()
            ->all();
    }

    public function getAppointment(int $citaId): ?object
    {
        return DB::table('vida_salud.cita as c')
            ->join('vida_salud.paciente as p', 'p.id', '=', 'c.paciente_id')
            ->join('vida_salud.medico as m', 'm.id', '=', 'c.medico_id')
            ->join('vida_salud.usuario as um', 'um.id', '=', 'm.usuario_id')
            ->join('vida_salud.especialidad as e', 'e.id', '=', 'c.especialidad_id')
            ->where('c.id', $citaId)
            ->select(
                'c.*',
                'e.nombre as especialidad',
                'p.id as paciente_id',
                'p.nombres as paciente_nombres',
                'p.apellidos as paciente_apellidos',
                'p.ci as paciente_ci',
                'p.telefono as paciente_telefono',
                'p.correo as paciente_correo',
                'm.id as medico_id',
                'um.nombres as medico_nombres',
                'um.apellidos as medico_apellidos',
                'um.correo as medico_correo',
                'm.telefono as medico_telefono'
            )
            ->selectRaw("concat_ws(' ', p.nombres, p.apellidos) as paciente_nombre")
            ->selectRaw("concat_ws(' ', um.nombres, um.apellidos) as medico_nombre")
            ->first();
    }

    public function patientAppointments(int $patientId): array
    {
        return DB::table('vida_salud.cita as c')
            ->join('vida_salud.medico as m', 'm.id', '=', 'c.medico_id')
            ->join('vida_salud.usuario as um', 'um.id', '=', 'm.usuario_id')
            ->join('vida_salud.especialidad as e', 'e.id', '=', 'c.especialidad_id')
            ->where('c.paciente_id', $patientId)
            ->whereIn('c.estado', ['Programada', 'Confirmada'])
            ->orderBy('c.inicio')
            ->select(
                'c.id',
                'c.codigo',
                'c.inicio',
                'c.fin',
                'c.estado',
                'e.nombre as especialidad'
            )
            ->selectRaw("concat_ws(' ', um.nombres, um.apellidos) as medico_nombre")
            ->get()
            ->all();
    }

    public function validateContact(string $medio, object $patient, object $doctor, string $destinatario): array
    {
        $contactoDestino = '';
        $contactoDoctor = null;

        if (in_array($destinatario, ['Paciente', 'Ambos'], true)) {
            if ($medio === 'Correo') {
                $email = trim((string) ($patient->paciente_correo ?? $patient->correo ?? ''));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages([
                        'medio' => 'Medio de contacto no disponible: El paciente no tiene registrado un correo electrónico válido. Actualice sus datos de contacto o seleccione SMS o WhatsApp.',
                    ]);
                }
                $contactoDestino = $email;
            } elseif (in_array($medio, ['SMS', 'WhatsApp'], true)) {
                $tel = trim((string) ($patient->paciente_telefono ?? $patient->telefono ?? ''));
                if ($tel === '') {
                    throw ValidationException::withMessages([
                        'medio' => "Medio de contacto no disponible: El paciente no tiene registrado un número telefónico para envíos por {$medio}. Actualice sus datos de contacto o seleccione otro medio.",
                    ]);
                }
                $contactoDestino = $tel;
            } else {
                $contactoDestino = 'Portal del Paciente / Notificación en línea';
            }
        }

        if (in_array($destinatario, ['Medico', 'Ambos'], true)) {
            if ($medio === 'Correo') {
                $emailDoc = trim((string) ($doctor->medico_correo ?? $doctor->correo ?? ''));
                if ($emailDoc === '' || ! filter_var($emailDoc, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages([
                        'medio' => 'Medio de contacto no disponible: El médico no tiene registrado un correo electrónico válido para este envío.',
                    ]);
                }
                if ($destinatario === 'Medico') {
                    $contactoDestino = $emailDoc;
                } else {
                    $contactoDoctor = $emailDoc;
                }
            } elseif (in_array($medio, ['SMS', 'WhatsApp'], true)) {
                $telDoc = trim((string) ($doctor->medico_telefono ?? $doctor->telefono ?? ''));
                if ($telDoc === '') {
                    throw ValidationException::withMessages([
                        'medio' => "Medio de contacto no disponible: El médico no tiene registrado un número telefónico para envíos por {$medio}.",
                    ]);
                }
                if ($destinatario === 'Medico') {
                    $contactoDestino = $telDoc;
                } else {
                    $contactoDoctor = $telDoc;
                }
            } else {
                if ($destinatario === 'Medico') {
                    $contactoDestino = 'Portal del Médico / Notificación en línea';
                } else {
                    $contactoDoctor = 'Portal del Médico / Notificación en línea';
                }
            }
        }

        return [$contactoDestino, $contactoDoctor];
    }

    public function defaultMessage(object $cita, string $destinatario): string
    {
        $fecha = Clinic::date($cita->inicio)->format('d/m/Y');
        $hora = Clinic::date($cita->inicio)->format('H:i');
        $paciente = trim(($cita->paciente_nombres ?? '').' '.($cita->paciente_apellidos ?? ''));
        $medico = trim(($cita->medico_nombres ?? '').' '.($cita->medico_apellidos ?? ''));
        $especialidad = $cita->especialidad ?? 'Consulta Médica';

        if ($destinatario === 'Medico') {
            return "Estimado(a) Dr(a). {$medico}, le recordamos su consulta médica de {$especialidad} con el paciente {$paciente} programada para el {$fecha} a las {$hora} en Clínica Vida Salud.";
        }

        if ($destinatario === 'Ambos') {
            return "Recordatorio de consulta de {$especialidad} en Clínica Vida Salud para el {$fecha} a las {$hora}. Paciente: {$paciente}. Médico: Dr(a). {$medico}. Por favor presentarse 15 minutos antes.";
        }

        return "Estimado(a) {$paciente}, le recordamos su cita médica de {$especialidad} con el Dr(a). {$medico} programada para el {$fecha} a las {$hora} en Clínica Vida Salud. Por favor presentarse 15 minutos antes de su turno.";
    }

    public function calculateScheduleTime(string $momentoTipo, object $cita, ?string $fechaPersonalizada = null): CarbonImmutable
    {
        $citaInicio = Clinic::date($cita->inicio);
        $ahora = CarbonImmutable::now('America/La_Paz');

        return match ($momentoTipo) {
            'inmediato' => $ahora,
            '24h_antes' => $citaInicio->subHours(24),
            '2h_antes' => $citaInicio->subHours(2),
            '1d_8am' => $citaInicio->subDays(1)->setTime(8, 0, 0),
            'personalizado' => $fechaPersonalizada ? Clinic::date($fechaPersonalizada) : $ahora,
            default => $ahora,
        };
    }

    public function create(User $user, array $data): int
    {
        $cita = $this->getAppointment((int) $data['cita_id']);
        abort_unless($cita, 404, 'La cita médica seleccionada no existe.');

        if (! in_array($cita->estado, ['Programada', 'Confirmada'], true)) {
            throw ValidationException::withMessages([
                'cita_id' => 'Solo se pueden registrar recordatorios para citas en estado Programada o Confirmada.',
            ]);
        }

        [$contactoDestino, $contactoDoctor] = $this->validateContact($data['medio'], $cita, $cita, $data['destinatario']);

        $fechaProgramada = $this->calculateScheduleTime(
            $data['momento_tipo'] ?? 'inmediato',
            $cita,
            $data['fecha_personalizada'] ?? null
        );

        $mensaje = trim((string) ($data['mensaje'] ?? ''));
        if ($mensaje === '') {
            $mensaje = $this->defaultMessage($cita, $data['destinatario']);
        }

        return Clinic::transaction($user, function () use ($user, $cita, $data, $contactoDestino, $contactoDoctor, $fechaProgramada, $mensaje) {
            $id = DB::table('vida_salud.recordatorio')->insertGetId([
                'cita_id' => $cita->id,
                'paciente_id' => $cita->paciente_id,
                'medico_id' => $cita->medico_id,
                'destinatario' => $data['destinatario'],
                'medio' => $data['medio'],
                'contacto_destino' => $contactoDestino,
                'contacto_doctor' => $contactoDoctor,
                'fecha_programada' => $fechaProgramada,
                'estado' => 'Programado',
                'mensaje' => $mensaje,
                'registrado_por' => $user->id,
                'creado_en' => CarbonImmutable::now('America/La_Paz'),
                'actualizado_en' => CarbonImmutable::now('America/La_Paz'),
            ]);

            DB::table('vida_salud.historial_recordatorio')->insert([
                'recordatorio_id' => $id,
                'usuario_id' => $user->id,
                'accion' => 'Registrado',
                'estado_anterior' => null,
                'estado_nuevo' => 'Programado',
                'detalle' => "Recordatorio registrado para {$data['destinatario']} por {$data['medio']}. Programado para: ".$fechaProgramada->format('d/m/Y H:i'),
                'fecha' => CarbonImmutable::now('America/La_Paz'),
            ]);

            if (($data['momento_tipo'] ?? '') === 'inmediato') {
                $simulateFail = ! empty($data['simular_fallo']);
                $this->send($user, $id, $simulateFail);
            }

            return $id;
        });
    }

    public function update(User $user, int $id, array $data): void
    {
        $row = $this->find($id);

        if (! in_array($row->estado, ['Programado', 'Fallido'], true)) {
            throw ValidationException::withMessages([
                'operacion' => 'Solo se pueden modificar recordatorios en estado Programado o Fallido.',
            ]);
        }

        $cita = $this->getAppointment($row->cita_id);
        abort_unless($cita, 404, 'La cita médica asociada no existe.');

        $destinatario = $data['destinatario'] ?? $row->destinatario;
        $medio = $data['medio'] ?? $row->medio;

        [$contactoDestino, $contactoDoctor] = $this->validateContact($medio, $cita, $cita, $destinatario);

        $fechaProgramada = ! empty($data['fecha_programada'])
            ? Clinic::date($data['fecha_programada'])
            : Clinic::date($row->fecha_programada);

        $mensaje = trim((string) ($data['mensaje'] ?? $row->mensaje));

        Clinic::transaction($user, function () use ($user, $row, $destinatario, $medio, $contactoDestino, $contactoDoctor, $fechaProgramada, $mensaje) {
            $nuevoEstado = $row->estado === 'Fallido' ? 'Programado' : $row->estado;

            DB::table('vida_salud.recordatorio')->where('id', $row->id)->update([
                'destinatario' => $destinatario,
                'medio' => $medio,
                'contacto_destino' => $contactoDestino,
                'contacto_doctor' => $contactoDoctor,
                'fecha_programada' => $fechaProgramada,
                'mensaje' => $mensaje,
                'estado' => $nuevoEstado,
                'error_envio' => $nuevoEstado === 'Programado' ? null : $row->error_envio,
                'actualizado_en' => CarbonImmutable::now('America/La_Paz'),
            ]);

            DB::table('vida_salud.historial_recordatorio')->insert([
                'recordatorio_id' => $row->id,
                'usuario_id' => $user->id,
                'accion' => 'Modificado',
                'estado_anterior' => $row->estado,
                'estado_nuevo' => $nuevoEstado,
                'detalle' => "Recordatorio modificado: Medio={$medio}, Destinatario={$destinatario}, Fecha programada=".$fechaProgramada->format('d/m/Y H:i'),
                'fecha' => CarbonImmutable::now('America/La_Paz'),
            ]);
        });
    }

    public function cancel(User $user, int $id, string $motivo): void
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw ValidationException::withMessages([
                'motivo_cancelacion' => 'Debe ingresar un motivo para cancelar el recordatorio.',
            ]);
        }

        $row = $this->find($id);

        if (! in_array($row->estado, ['Programado', 'Fallido'], true)) {
            throw ValidationException::withMessages([
                'operacion' => 'Solo se pueden cancelar recordatorios en estado Programado o Fallido.',
            ]);
        }

        Clinic::transaction($user, function () use ($user, $row, $motivo) {
            DB::table('vida_salud.recordatorio')->where('id', $row->id)->update([
                'estado' => 'Cancelado',
                'motivo_cancelacion' => $motivo,
                'actualizado_en' => CarbonImmutable::now('America/La_Paz'),
            ]);

            DB::table('vida_salud.historial_recordatorio')->insert([
                'recordatorio_id' => $row->id,
                'usuario_id' => $user->id,
                'accion' => 'Cancelado',
                'estado_anterior' => $row->estado,
                'estado_nuevo' => 'Cancelado',
                'detalle' => "Recordatorio cancelado. Motivo: {$motivo}",
                'fecha' => CarbonImmutable::now('America/La_Paz'),
            ]);
        });
    }

    public function send(User $user, int $id, bool $simulateFail = false): bool
    {
        $row = $this->find($id);

        if ($row->estado === 'Cancelado') {
            throw ValidationException::withMessages([
                'operacion' => 'No se puede enviar un recordatorio cancelado.',
            ]);
        }

        return Clinic::transaction($user, function () use ($user, $row, $simulateFail) {
            $intentos = $row->intentos + 1;
            $ahora = CarbonImmutable::now('America/La_Paz');

            if ($simulateFail) {
                $errorMsg = "Fallo en la pasarela de {$row->medio}: El servicio de entrega no respondió o el contacto {$row->contacto_destino} no se encuentra disponible.";

                DB::table('vida_salud.recordatorio')->where('id', $row->id)->update([
                    'estado' => 'Fallido',
                    'error_envio' => $errorMsg,
                    'intentos' => $intentos,
                    'actualizado_en' => $ahora,
                ]);

                DB::table('vida_salud.historial_recordatorio')->insert([
                    'recordatorio_id' => $row->id,
                    'usuario_id' => $user->id,
                    'accion' => 'FalloEnvio',
                    'estado_anterior' => $row->estado,
                    'estado_nuevo' => 'Fallido',
                    'detalle' => "Error en intento de envío #{$intentos}: {$errorMsg}",
                    'fecha' => $ahora,
                ]);

                return false;
            }

            DB::table('vida_salud.recordatorio')->where('id', $row->id)->update([
                'estado' => 'Enviado',
                'fecha_envio' => $ahora,
                'intentos' => $intentos,
                'error_envio' => null,
                'actualizado_en' => $ahora,
            ]);

            DB::table('vida_salud.historial_recordatorio')->insert([
                'recordatorio_id' => $row->id,
                'usuario_id' => $user->id,
                'accion' => 'Enviado',
                'estado_anterior' => $row->estado,
                'estado_nuevo' => 'Enviado',
                'detalle' => "Recordatorio enviado exitosamente a {$row->contacto_destino} por {$row->medio} (intento #{$intentos}).",
                'fecha' => $ahora,
            ]);

            return true;
        });
    }

    public function processScheduled(?User $user = null): array
    {
        $actor = $user ?? User::where('activo', true)->first();
        $ahora = CarbonImmutable::now('America/La_Paz');

        $pending = DB::table('vida_salud.recordatorio')
            ->where('estado', 'Programado')
            ->where('fecha_programada', '<=', $ahora)
            ->get();

        $enviados = 0;
        $fallidos = 0;

        foreach ($pending as $rec) {
            try {
                $cita = $this->getAppointment($rec->cita_id);
                if ($cita && $cita->estado === 'Cancelada') {
                    $this->cancel($actor, $rec->id, 'Cancelado automáticamente al procesar porque la cita médica asociada fue cancelada.');

                    continue;
                }

                $ok = $this->send($actor, $rec->id, false);
                if ($ok) {
                    $enviados++;
                } else {
                    $fallidos++;
                }
            } catch (\Throwable $e) {
                $fallidos++;
            }
        }

        return [
            'total' => count($pending),
            'enviados' => $enviados,
            'fallidos' => $fallidos,
        ];
    }

    public function syncWithAppointmentChange(int $citaId, string $action, ?string $newInicio = null, ?string $motivo = null, ?User $user = null): void
    {
        $actor = $user ?? User::where('activo', true)->first();
        $reminders = DB::table('vida_salud.recordatorio')
            ->where('cita_id', $citaId)
            ->whereIn('estado', ['Programado', 'Fallido'])
            ->get();

        if ($reminders->isEmpty()) {
            return;
        }

        $cita = $this->getAppointment($citaId);

        foreach ($reminders as $rec) {
            if ($action === 'cancelar') {
                $detalleCancel = 'Cancelación automática por cancelación de la cita médica.'.($motivo ? ' Motivo cita: '.$motivo : '');
                $this->cancel($actor, $rec->id, $detalleCancel);
            } elseif ($action === 'reprogramar' && $cita) {
                $nuevoInicio = $newInicio ? Clinic::date($newInicio) : Clinic::date($cita->inicio);
                $diferenciaSegundos = $nuevoInicio->getTimestamp() - Clinic::date($cita->inicio)->getTimestamp();
                $nuevaFechaProgramada = Clinic::date($rec->fecha_programada)->addSeconds($diferenciaSegundos);

                if ($nuevaFechaProgramada->isPast()) {
                    $nuevaFechaProgramada = CarbonImmutable::now('America/La_Paz')->addMinutes(5);
                }

                $nuevoMensaje = $this->defaultMessage($cita, $rec->destinatario);

                DB::table('vida_salud.recordatorio')->where('id', $rec->id)->update([
                    'fecha_programada' => $nuevaFechaProgramada,
                    'mensaje' => $nuevoMensaje,
                    'actualizado_en' => CarbonImmutable::now('America/La_Paz'),
                ]);

                DB::table('vida_salud.historial_recordatorio')->insert([
                    'recordatorio_id' => $rec->id,
                    'usuario_id' => $actor->id,
                    'accion' => 'SincronizadoCita',
                    'estado_anterior' => $rec->estado,
                    'estado_nuevo' => $rec->estado,
                    'detalle' => 'Recordatorio actualizado automáticamente tras reprogramación de la cita médica al '.$nuevoInicio->format('d/m/Y H:i'),
                    'fecha' => CarbonImmutable::now('America/La_Paz'),
                ]);
            }
        }
    }
}
