<?php

namespace App\Modules\GestionLaboratorio\Services;

use App\Models\User;
use App\Modules\GestionClinica\Services\AccesoClinico;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class LaboratorioService
{
    public function processor(User $user): void
    {
        abort_unless($user->roleName() === 'Laboratorista', 403);
        Clinic::authorize($user, 'laboratorio.procesar');
    }

    public function orders(User $user)
    {
        $q = DB::table('orden_laboratorio as o')->join('consulta as co', 'co.id', '=', 'o.consulta_id')->join('cita as c', 'c.id', '=', 'co.cita_id')->join('paciente as p', 'p.id', '=', 'c.paciente_id');
        if ($user->isPatient()) {
            Clinic::authorize($user, 'perfil.propio');
            $q->where('c.paciente_id', Clinic::patientId($user));
        } elseif ($user->isDoctor()) {
            $doctor = (new AccesoClinico)->doctor($user, 'laboratorio.consultar');
            (new AccesoClinico)->scope($q, $doctor, 'c.paciente_id');
        } else {
            abort_unless($user->roleName() === 'Laboratorista', 403);
            Clinic::authorize($user, 'laboratorio.consultar');
        }

        return $q;
    }

    public function results(User $user)
    {
        $q = $this->orders($user)->join('orden_analisis as d', 'd.orden_id', '=', 'o.id')->join('analisis_laboratorio as a', 'a.id', '=', 'd.analisis_id')->join('resultado_laboratorio as r', 'r.orden_analisis_id', '=', 'd.id')
            ->select('r.id', 'r.resultado', 'r.observaciones', 'r.archivo_referencia', 'r.fecha_registro', 'r.publicado_en', 'r.publicado_por', 'o.id as orden_id', 'o.estado', 'c.paciente_id', 'a.nombre as analisis')
            ->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente");
        if ($user->isPatient()) {
            $q->whereNotNull('r.publicado_en');
        }

        return $q;
    }

    public function item(User $user, int $id): object
    {
        $this->processor($user);
        abort_unless(DB::table('orden_analisis')->where('id', $id)->exists(), 404);

        return DB::table('orden_analisis as d')->join('orden_laboratorio as o', 'o.id', '=', 'd.orden_id')->join('analisis_laboratorio as a', 'a.id', '=', 'd.analisis_id')->where('d.id', $id)->whereIn('o.estado', ['Pendiente', 'En proceso'])->whereNotExists(fn ($q) => $q->selectRaw('1')->from('resultado_laboratorio as r')->whereColumn('r.orden_analisis_id', 'd.id'))
            ->select('d.id', 'o.id as orden_id', 'a.nombre')->first() ?? abort(422, 'La orden no existe, está finalizada o no tiene ese análisis pendiente.');
    }
}
