<?php

namespace App\Modules\Shared\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CatalogoService
{
    public const PROTECTED_ROLES = ['Administrador', 'Recepcionista', 'Medico', 'Laboratorista', 'Paciente'];

    public function definition(string $module): array
    {
        $value = config('clinic.catalogs.'.$module);
        abort_unless($value, 404);

        return $value;
    }

    public function read(User $user, string $module): void
    {
        $definition = $this->definition($module);
        if (in_array($module, ['medicos', 'especialidades', 'horarios'], true) && $user->hasPermission('pacientes.gestionar')) {
            return;
        }
        Clinic::authorize($user, $definition['permission']);
    }

    public function query(string $module): Builder
    {
        $table = $this->definition($module)['table'];

        return match ($module) {
            'medicos' => DB::table('medico as t')->join('usuario as u', 'u.id', '=', 't.usuario_id')
                ->select('t.*', 'u.nombres', 'u.apellidos', 'u.correo', DB::raw("u.nombres || ' ' || u.apellidos as nombre"))
                ->selectRaw("(SELECT string_agg(e.nombre, ', ' ORDER BY e.nombre) FROM medico_especialidad me JOIN especialidad e ON e.id = me.especialidad_id WHERE me.medico_id = t.id AND me.activo) as especialidades"),
            'horarios' => DB::table('horario as t')->join('medico as m', 'm.id', '=', 't.medico_id')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->select('t.*', DB::raw("u.nombres || ' ' || u.apellidos as medico"), DB::raw("CASE t.dia_semana WHEN 1 THEN 'Lunes' WHEN 2 THEN 'Martes' WHEN 3 THEN 'Miércoles' WHEN 4 THEN 'Jueves' WHEN 5 THEN 'Viernes' WHEN 6 THEN 'Sábado' ELSE 'Domingo' END as dia")),
            'usuarios' => DB::table('usuario as t')->join('rol as r', 'r.id', '=', 't.rol_id')->select('t.id', 't.rol_id', 't.paciente_id', 't.nombre_usuario', 't.nombres', 't.apellidos', 't.correo', 't.activo', 'r.nombre as rol'),
            default => DB::table($table.' as t')->select('t.*'),
        };
    }

    public function options(string $module, ?int $id = null): array
    {
        return match ($module) {
            'pacientes' => [],
            'medicos' => $this->doctorOptions($id),
            'horarios' => ['medico_id' => Clinic::doctors()],
            'usuarios' => ['rol_id' => DB::table('rol')->where('activo', true)->pluck('nombre', 'id')->all()],
            'roles' => ['permiso_ids' => DB::table('permiso')->orderBy('codigo')->pluck('codigo', 'id')->all()],
            default => [],
        };
    }

    private function doctorOptions(?int $id): array
    {
        $currentUser = $id ? DB::table('medico')->where('id', $id)->value('usuario_id') : null;
        $query = DB::table('usuario as u')->join('rol as r', 'r.id', '=', 'u.rol_id')->where('r.nombre', 'Medico');
        if ($currentUser) {
            $query->where('u.id', $currentUser);
        } else {
            $query->where('u.activo', true)->where('r.activo', true)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('medico as m')->whereColumn('m.usuario_id', 'u.id'));
        }
        $accounts = $query->orderBy('u.apellidos')->get(['u.id', 'u.nombres', 'u.apellidos', 'u.correo']);
        $specialties = DB::table('especialidad as e')->where(function (Builder $query) use ($id): void {
            $query->where('e.activo', true);
            if ($id) {
                $query->orWhereExists(fn (Builder $link) => $link->selectRaw('1')
                    ->from('medico_especialidad as me')->whereColumn('me.especialidad_id', 'e.id')
                    ->where('me.medico_id', $id)->where('me.activo', true));
            }
        })->orderBy('e.nombre')->get(['e.id', 'e.nombre', 'e.activo']);

        return [
            'usuario_id' => $accounts->mapWithKeys(fn ($account) => [$account->id => $account->nombres.' '.$account->apellidos])->all(),
            'datos_cuentas' => $accounts->keyBy('id')->all(),
            'especialidad_ids' => $specialties->mapWithKeys(fn ($specialty) => [
                $specialty->id => $specialty->nombre.($specialty->activo ? '' : ' (inactiva)'),
            ])->all(),
        ];
    }

    public function roleForDeletion(User $actor, int $id): object
    {
        Clinic::authorize($actor, 'roles.gestionar');
        $role = DB::table('rol')->where('id', $id)->first();
        abort_unless($role, 404);

        return $role;
    }

    public function deleteRole(User $actor, int $id): void
    {
        Clinic::authorize($actor, 'roles.gestionar');
        Clinic::transaction($actor, function () use ($actor, $id): void {
            $role = DB::table('rol')->where('id', $id)->lockForUpdate()->first();
            abort_unless($role, 404);
            if (in_array($role->nombre, self::PROTECTED_ROLES, true) || (int) $actor->rol_id === $id) {
                Clinic::fail('Este rol está protegido y no puede eliminarse.');
            }
            if (DB::table('usuario')->where('rol_id', $id)->exists()) {
                Clinic::fail('El rol tiene usuarios asignados. Reasígnalos antes de eliminarlo.');
            }
            DB::table('rol_permiso')->where('rol_id', $id)->delete();
            DB::table('rol')->where('id', $id)->delete();
        });
    }

    public function save(User $actor, string $module, array $data, ?int $id): int
    {
        $definition = $this->definition($module);
        Clinic::authorize($actor, $definition['permission']);

        return Clinic::transaction($actor, function () use ($actor, $module, $definition, $data, $id) {
            $record = $id ? DB::table($definition['table'])->where('id', $id)->lockForUpdate()->first() : null;
            abort_if($id && ! $record, 404);
            $specialties = $data['especialidad_ids'] ?? [];
            $permissions = $data['permiso_ids'] ?? [];
            unset($data['especialidad_ids'], $data['permiso_ids']);
            if ($module === 'usuarios') {
                $role = DB::table('rol')->where('id', $data['rol_id'])->first();
                if (! $role->activo) {
                    Clinic::fail('Selecciona un rol activo.', 'rol_id');
                }
                if ($id === $actor->id && (! $data['activo'] || $data['rol_id'] != $actor->rol_id)) {
                    Clinic::fail('No puedes desactivar tu cuenta ni cambiar tu propio rol.');
                }
                if ($record && DB::table('medico')->where('usuario_id', $id)->exists() && $role->nombre !== 'Medico') {
                    Clinic::fail('La cuenta tiene un perfil médico; conserva su rol.', 'rol_id');
                }
                if (! empty($data['password'])) {
                    $data['password_hash'] = Hash::make($data['password']);
                } unset($data['password']);
                if ($record?->paciente_id && $role->nombre !== 'Paciente') {
                    Clinic::fail('Desvincula primero el paciente antes de cambiar el rol de esta cuenta.', 'rol_id');
                }
                // La identidad clínica se modifica exclusivamente en su operación explícita.
                unset($data['paciente_id'], $data['paciente_verificado_por'], $data['paciente_verificado_en']);
            }
            if ($module === 'roles' && $record) {
                if (in_array($record->nombre, self::PROTECTED_ROLES, true) && $data['nombre'] !== $record->nombre) {
                    Clinic::fail('Los roles del sistema conservan su nombre.', 'nombre');
                }
                if ((int) $id === (int) $actor->rol_id) {
                    $required = DB::table('permiso')->whereIn('codigo', ['roles.gestionar', 'usuarios.gestionar'])->pluck('id')->all();
                    if (! $data['activo'] || array_diff($required, $permissions)) {
                        Clinic::fail('Conserva activo tu rol y sus permisos de administración.');
                    }
                }
            }
            if ($module === 'medicos') {
                $user = User::query()->lockForUpdate()->findOrFail($data['usuario_id']);
                if ($user->roleName() !== 'Medico' || (! $user->isActive() && (! $record || $data['activo']))) {
                    Clinic::fail('Selecciona una cuenta médica activa.', 'usuario_id');
                }
                if ($record && (int) $record->usuario_id !== (int) $data['usuario_id']) {
                    Clinic::fail('La cuenta profesional no puede trasladarse a otro usuario.', 'usuario_id');
                }
                $specialties = array_map('intval', $specialties);
                $currentSpecialties = $record ? DB::table('medico_especialidad')
                    ->where('medico_id', $id)->where('activo', true)->lockForUpdate()
                    ->pluck('especialidad_id')->map(fn ($value) => (int) $value)->all() : [];
                $selectedSpecialties = DB::table('especialidad')->whereIn('id', $specialties)
                    ->orderBy('id')->sharedLock()->get(['id', 'activo']);
                if ($selectedSpecialties->count() !== count($specialties)) {
                    Clinic::fail('Una especialidad seleccionada ya no existe.', 'especialidad_ids');
                }
                foreach ($selectedSpecialties as $specialty) {
                    if (! $specialty->activo && ! in_array((int) $specialty->id, $currentSpecialties, true)) {
                        Clinic::fail('Solo puedes conservar especialidades inactivas que ya estén vinculadas al médico.', 'especialidad_ids');
                    }
                }
                if ((! $record || (! $record->activo && $data['activo']))
                    && ! $selectedSpecialties->contains(fn ($specialty) => $specialty->activo)) {
                    Clinic::fail('Para crear o reactivar el perfil, selecciona al menos una especialidad activa.', 'especialidad_ids');
                }
                DB::table('usuario')->where('id', $user->id)->update([
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'correo' => $data['correo'] ?? null,
                ]);
                unset($data['nombres'], $data['apellidos'], $data['correo']);
            }
            if ($module === 'especialidades' || $module === 'roles') {
                $q = DB::table($definition['table'])->whereRaw('lower(btrim(nombre)) = ?', [mb_strtolower(trim($data['nombre']))]);
                if ($id) {
                    $q->where('id', '<>', $id);
                } if ($q->exists()) {
                    Clinic::fail('Ya existe un nombre equivalente.', 'nombre');
                }
            }
            if ($module === 'horarios') {
                $data['hora_inicio'] = substr($data['hora_inicio'], 0, 5);
                $data['hora_fin'] = substr($data['hora_fin'], 0, 5);
            }
            if ($id) {
                DB::table($definition['table'])->where('id', $id)->update($data);
            } else {
                $id = DB::table($definition['table'])->insertGetId($data);
            }
            if ($module === 'medicos') {
                DB::table('medico')->where('id', $id)->lockForUpdate()->first();
                DB::table('medico_especialidad')->where('medico_id', $id)->where('activo', true)
                    ->whereNotIn('especialidad_id', $specialties)->update(['activo' => false]);
                foreach (array_diff($specialties, $currentSpecialties) as $specialtyId) {
                    DB::table('medico_especialidad')->updateOrInsert(['medico_id' => $id, 'especialidad_id' => $specialtyId], ['activo' => true]);
                }
            }
            if ($module === 'roles') {
                DB::table('rol_permiso')->where('rol_id', $id)->delete();
                foreach ($permissions as $permissionId) {
                    DB::table('rol_permiso')->insert(['rol_id' => $id, 'permiso_id' => $permissionId]);
                }
            }

            return $id;
        });
    }
}
