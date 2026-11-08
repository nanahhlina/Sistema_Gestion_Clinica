<?php

namespace App\Modules\GestionCitasAgendaMedica\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultaAgendaService
{
    public const STATES = ['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'];

    /** Un médico conserva el alcance propio aunque también reciba permisos administrativos. */
    public function doctorScope(User $user): ?int
    {
        Clinic::authorize($user, 'agenda.consultar');
        abort_if($user->isPatient(), 403);
        if ($user->isDoctor()) {
            $id = DB::table('medico')->where('usuario_id', $user->id)->value('id');
            abort_unless($id, 403, 'La cuenta no tiene un perfil médico.');

            return (int) $id;
        }
        abort_unless($user->hasPermission('pacientes.gestionar') || $user->hasPermission('medicos.gestionar'), 403);

        return null;
    }

    public function query(bool $includeReason = false): Builder
    {
        $query = DB::table('cita as c')
            ->join('paciente as p', 'p.id', '=', 'c.paciente_id')
            ->join('medico as m', 'm.id', '=', 'c.medico_id')
            ->join('usuario as u', 'u.id', '=', 'm.usuario_id')
            ->join('rol as r', 'r.id', '=', 'u.rol_id')
            ->join('especialidad as e', 'e.id', '=', 'c.especialidad_id')
            ->select('c.id', 'c.codigo', 'c.inicio', 'c.fin', 'c.estado', 'c.creada_en', 'c.paciente_id', 'c.medico_id', 'c.precio_consulta', 'e.nombre as especialidad')
            ->selectRaw("concat_ws(' ', p.nombres, p.apellidos) as paciente, concat_ws(' ', u.nombres, u.apellidos) as medico")
            ->selectRaw('(m.activo AND u.activo AND r.activo) as medico_habilitado');
        if ($includeReason) {
            $query->addSelect('c.motivo_consulta');
        }

        return $query;
    }

    public function enabledDoctors(Builder $query): void
    {
        $query->where('m.activo', true)->where('u.activo', true)->where('r.activo', true);
    }

    public function filter(Builder $query, array $filters): void
    {
        if (! empty($filters['desde'])) {
            $query->where('c.inicio', '>=', Clinic::date($filters['desde'])->startOfDay());
        }
        if (! empty($filters['hasta'])) {
            $query->where('c.inicio', '<', Clinic::date($filters['hasta'])->addDay()->startOfDay());
        }
        if (! empty($filters['estado'])) {
            $query->where('c.estado', $filters['estado']);
        }
        if (! empty($filters['medico_id'])) {
            $query->where('c.medico_id', $filters['medico_id']);
        }
    }

    public function filters(Request $request): array
    {
        return $request->validate([
            'desde' => ['nullable', 'date', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
            'estado' => ['nullable', Rule::in(self::STATES)],
            'medico_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'vista' => ['sometimes', Rule::in(['agenda', 'historial'])],
        ]);
    }
}
