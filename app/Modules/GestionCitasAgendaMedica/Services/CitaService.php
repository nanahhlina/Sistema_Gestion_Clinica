<?php

namespace App\Modules\GestionCitasAgendaMedica\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class CitaService
{
    public function manage(User $user): void
    {
        Clinic::authorize($user, 'citas.gestionar');
        abort_if($user->isPatient() || $user->isDoctor(), 403);
    }

    public function patient(User $user): int
    {
        Clinic::authorize($user, 'citas.propias');
        $id = Clinic::patientId($user);
        abort_unless(DB::table('paciente')->where('id', $id)->where('activo', true)->exists(), 403, 'El paciente está inactivo. Contacta con recepción.');

        return $id;
    }

    public function read(User $user, int $id): object
    {
        $query = (new ConsultaAgendaService)->query()->where('c.id', $id);
        if ($user->isPatient()) {
            $query->where('c.paciente_id', $this->patient($user));
        } elseif ($user->isDoctor()) {
            Clinic::authorize($user, 'agenda.consultar');
            $query->where('c.medico_id', Clinic::doctorId($user));
        } else {
            $this->manage($user);
        }

        return $query->first() ?? abort(404);
    }

    public function doctor(int $doctor, int $specialty): object
    {
        $row = DB::table('medico as m')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('rol as r', 'r.id', '=', 'u.rol_id')
            ->join('medico_especialidad as me', 'me.medico_id', '=', 'm.id')->join('especialidad as e', 'e.id', '=', 'me.especialidad_id')
            ->where('m.id', $doctor)->where('e.id', $specialty)->where('m.activo', true)->where('u.activo', true)->where('r.activo', true)->where('r.nombre', 'Medico')->where('me.activo', true)->where('e.activo', true)
            ->selectRaw("m.id, concat_ws(' ',u.nombres,u.apellidos) as nombre, e.nombre as especialidad")->first();
        if (! $row) {
            Clinic::fail('Médico o especialidad no habilitados.', 'medico_id');
        }

        return $row;
    }

    public function choices(int $specialty): array
    {
        return DB::table('medico as m')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('rol as r', 'r.id', '=', 'u.rol_id')->join('medico_especialidad as me', 'me.medico_id', '=', 'm.id')
            ->where('me.especialidad_id', $specialty)->where('me.activo', true)->where('m.activo', true)->where('u.activo', true)->where('r.activo', true)->where('r.nombre', 'Medico')
            ->orderBy('u.apellidos')->pluck(DB::raw("concat_ws(' ',u.nombres,u.apellidos) as label"), 'm.id')->all();
    }

    public function slots(int $doctor, string $date, int $patient, int $except = 0): array
    {
        // La disponibilidad de reprogramación puede ignorar la cita que se está editando.
        $rows = DB::select(<<<'SQL'
            SELECT b.inicio, b.inicio + make_interval(mins=>h.duracion_minutos) as fin
            FROM horario h JOIN medico m ON m.id=h.medico_id JOIN usuario u ON u.id=m.usuario_id JOIN rol r ON r.id=u.rol_id
            CROSS JOIN LATERAL generate_series((?::date+h.hora_inicio) AT TIME ZONE 'America/La_Paz',
                ((?::date+h.hora_fin) AT TIME ZONE 'America/La_Paz')-make_interval(mins=>h.duracion_minutos),make_interval(mins=>h.duracion_minutos)) b(inicio)
            WHERE h.medico_id=? AND h.dia_semana=extract(isodow FROM ?::date) AND h.activo AND m.activo AND u.activo AND r.activo AND r.nombre='Medico'
                AND b.inicio>current_timestamp AND NOT EXISTS(SELECT 1 FROM cita c
                    WHERE c.id<>? AND (c.medico_id=h.medico_id OR c.paciente_id=?) AND c.estado IN ('Programada','Confirmada','Atendida')
                    AND tstzrange(c.inicio,c.fin,'[)') && tstzrange(b.inicio,b.inicio+make_interval(mins=>h.duracion_minutos),'[)')) ORDER BY b.inicio
            SQL, [$date, $date, $doctor, $date, $except, $patient]);
        $options = [];
        foreach ($rows as $row) {
            $start = Clinic::date($row->inicio);
            $options[$start->toIso8601String()] = $start->format('d/m/Y H:i').'–'.Clinic::date($row->fin)->format('H:i');
        }

        return $options;
    }

    public function version(object $row): string
    {
        return hash('sha256', json_encode([$row->inicio, $row->fin, $row->estado]));
    }

    public function lock(int $id, string $version): object
    {
        $row = DB::table('cita')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
        if (! hash_equals($this->version($row), $version)) {
            Clinic::fail('La cita cambió durante la operación. Consulta sus datos nuevamente.');
        }

        return $row;
    }
}
