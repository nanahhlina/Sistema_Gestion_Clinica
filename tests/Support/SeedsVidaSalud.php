<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait SeedsVidaSalud
{
    protected function seedVidaSalud(): void
    {
        $this->seed();
    }

    protected function account(string $login): User
    {
        return User::query()->where('nombre_usuario', $login)->firstOrFail();
    }

    /** @return array{paciente_id: int, medico_id: int, especialidad_id: int, inicio: string, motivo_consulta: string} */
    protected function appointmentPayload(): array
    {
        $doctor = DB::table('medico')->where('usuario_id', $this->account('medico')->getKey())->first();
        $specialty = DB::table('especialidad')->where('nombre', 'Medicina General')->first();
        $start = Carbon::now('America/La_Paz')->addWeeks(2)->startOfWeek()->setTime(9, 0);

        DB::table('horario')->where('medico_id', $doctor->id)->delete();
        DB::table('horario')->insert([
            'medico_id' => $doctor->id,
            'dia_semana' => 1,
            'hora_inicio' => '09:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);

        return [
            'paciente_id' => (int) DB::table('paciente')->where('ci', 'TEST-001')->value('id'),
            'medico_id' => (int) $doctor->id,
            'especialidad_id' => (int) $specialty->id,
            'inicio' => $start->format('Y-m-d\TH:i:sP'),
            'motivo_consulta' => 'Control preventivo',
        ];
    }

    protected function otherPatient(): int
    {
        return (int) DB::table('paciente')->insertGetId([
            'ci' => 'TEST-002', 'nombres' => 'Beatriz', 'apellidos' => 'Prueba',
            'fecha_nacimiento' => '1992-04-15', 'telefono' => '70000002', 'activo' => true,
        ]);
    }

    protected function otherDoctor(): int
    {
        $doctor = $this->account('medico');
        $userId = DB::table('usuario')->insertGetId([
            'rol_id' => $doctor->rol_id, 'nombre_usuario' => 'medico.dos',
            'password_hash' => $doctor->password_hash, 'nombres' => 'Doctora', 'apellidos' => 'Dos',
        ]);

        return (int) DB::table('medico')->insertGetId([
            'usuario_id' => $userId, 'registro_profesional' => 'MED-TEST-02',
        ]);
    }

    /** @param array{paciente_id: int, medico_id: int, especialidad_id: int, inicio: string, motivo_consulta: string} $payload */
    protected function existingAppointment(array $payload): int
    {
        return (int) DB::table('cita')->insertGetId([
            ...$payload,
            'fin' => Carbon::parse($payload['inicio'])->addMinutes(30)->toIso8601String(),
            'estado' => 'Programada',
            'creada_por' => $this->account('recepcion')->getKey(),
            'modificada_por' => $this->account('recepcion')->getKey(),
        ]);
    }

    /**
     * Importa únicamente el estado histórico previo a la acción HTTP. PostgreSQL
     * no permite retroceder su reloj; todas las acciones se prueban con triggers activos.
     */
    protected function historicalAppointment(?int $patientId = null, int $hour = 9): int
    {
        TestDatabaseSafety::assertApplication($this->app);
        $payload = $this->appointmentPayload();
        $payload['paciente_id'] = $patientId ?? $payload['paciente_id'];
        $payload['inicio'] = Carbon::parse($payload['inicio'])->subWeeks(3)->setTime($hour, 0)->toIso8601String();

        return DB::transaction(function () use ($payload): int {
            $previousRole = DB::selectOne('SHOW session_replication_role')->session_replication_role;
            DB::statement('SET LOCAL session_replication_role = replica');

            try {
                return $this->existingAppointment($payload);
            } finally {
                DB::selectOne("select set_config('session_replication_role', ?, true)", [$previousRole]);
            }
        });
    }

    protected function historicalConsultation(?int $patientId = null, int $hour = 9): int
    {
        $appointmentId = $this->historicalAppointment($patientId, $hour);

        return (int) DB::table('consulta')->insertGetId([
            'cita_id' => $appointmentId, 'fecha_atencion' => Carbon::now('America/La_Paz')->subMinute(),
            'notas_clinicas' => 'NOTA-INTERNA-NO-PUBLICAR', 'registrada_por' => $this->account('medico')->getKey(),
        ]);
    }

    /** @return array{order: int, items: array<int, int>} */
    protected function laboratoryOrder(int $analysisCount = 1): array
    {
        $consultationId = $this->historicalConsultation();

        return DB::transaction(function () use ($consultationId, $analysisCount): array {
            $orderId = (int) DB::table('orden_laboratorio')->insertGetId([
                'consulta_id' => $consultationId, 'solicitada_por' => $this->account('medico')->getKey(), 'estado' => 'Pendiente',
            ]);
            $items = [];
            foreach (DB::table('analisis_laboratorio')->where('activo', true)->orderBy('id')->limit($analysisCount)->pluck('id') as $analysisId) {
                $items[] = (int) DB::table('orden_analisis')->insertGetId(['orden_id' => $orderId, 'analisis_id' => $analysisId]);
            }

            return ['order' => $orderId, 'items' => $items];
        });
    }
}
