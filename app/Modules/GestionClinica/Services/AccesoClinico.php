<?php

namespace App\Modules\GestionClinica\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AccesoClinico
{
    public function doctor(User $user, string $permission): int
    {
        abort_unless($user->isDoctor(), 403);
        Clinic::authorize($user, $permission);

        return Clinic::doctorId($user);
    }

    public function scope(Builder $query, int $doctor, string $column = 'p.id'): Builder
    {
        return $query->whereExists(fn ($q) => $q->selectRaw('1')->from('cita as vinculo')->whereColumn('vinculo.paciente_id', $column)->where('vinculo.medico_id', $doctor)->whereIn('vinculo.estado', ['Programada', 'Confirmada', 'Atendida']));
    }

    public function patient(User $user, int $id, string $permission = 'historia.consultar'): object
    {
        $query = DB::table('paciente as p')->where('p.id', $id);
        if ($user->isPatient()) {
            Clinic::authorize($user, 'perfil.propio');
            $query->where('p.id', Clinic::patientId($user));
        } else {
            $this->scope($query, $this->doctor($user, $permission));
        }

        return $query->first() ?? abort(404);
    }

    public function consultations(User $user, int $patient): array
    {
        $doctor = $this->doctor($user, 'atencion.registrar');

        return DB::table('consulta as co')->join('cita as c', 'c.id', '=', 'co.cita_id')->where('c.paciente_id', $patient)->where('c.medico_id', $doctor)->where('co.registrada_por', $user->id)->orderByDesc('co.fecha_atencion')->pluck('co.fecha_atencion', 'co.id')->all();
    }

    public function ownConsultation(User $user, int $patient, int $id, string $permission): object
    {
        $doctor = $this->doctor($user, $permission);

        return DB::table('consulta as co')->join('cita as c', 'c.id', '=', 'co.cita_id')->where('co.id', $id)->where('c.paciente_id', $patient)->where('c.medico_id', $doctor)->where('co.registrada_por', $user->id)->select('co.*')->first() ?? abort(404);
    }

    public function ownDiagnosis(User $user, int $patient, int $id): object
    {
        $doctor = $this->doctor($user, 'atencion.registrar');

        return DB::table('diagnostico as d')->join('consulta as co', 'co.id', '=', 'd.consulta_id')->join('cita as c', 'c.id', '=', 'co.cita_id')
            ->where('d.id', $id)->where('c.paciente_id', $patient)->where('c.medico_id', $doctor)->where('co.registrada_por', $user->id)->select('d.*')->first() ?? abort(404);
    }
}
