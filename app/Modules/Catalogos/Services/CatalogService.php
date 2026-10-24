<?php

namespace App\Modules\Catalogos\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CatalogService
{
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
            'medicos' => DB::table('medico as t')->join('usuario as u', 'u.id', '=', 't.usuario_id')->select('t.*', DB::raw("u.nombres || ' ' || u.apellidos as nombre")),
            'horarios' => DB::table('horario as t')->join('medico as m', 'm.id', '=', 't.medico_id')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->select('t.*', DB::raw("u.nombres || ' ' || u.apellidos as medico"), DB::raw("CASE t.dia_semana WHEN 1 THEN 'Lunes' WHEN 2 THEN 'Martes' WHEN 3 THEN 'Miércoles' WHEN 4 THEN 'Jueves' WHEN 5 THEN 'Viernes' WHEN 6 THEN 'Sábado' ELSE 'Domingo' END as dia")),
            'usuarios' => DB::table('usuario as t')->join('rol as r', 'r.id', '=', 't.rol_id')->select('t.id', 't.rol_id', 't.paciente_id', 't.nombre_usuario', 't.nombres', 't.apellidos', 't.correo', 't.activo', 'r.nombre as rol'),
            default => DB::table($table.' as t')->select('t.*'),
        };
    }

    public function options(string $module, ?int $id = null): array
    {
        return match ($module) {
            'pacientes' => [],
            'medicos' => ['usuario_id' => DB::table('usuario as u')->join('rol as r', 'r.id', '=', 'u.rol_id')->where('r.nombre', 'Medico')->where('u.activo', true)->pluck(DB::raw("u.nombres || ' ' || u.apellidos as label"), 'u.id')->all(), 'especialidad_ids' => Clinic::specialties()],
            'horarios' => ['medico_id' => Clinic::doctors()],
            'usuarios' => ['rol_id' => DB::table('rol')->where('activo', true)->pluck('nombre', 'id')->all(), 'paciente_id' => DB::table('paciente')->where('activo', true)->pluck(DB::raw("nombres || ' ' || apellidos || ' · ' || coalesce(ci,'Sin CI') as label"), 'id')->all()],
            'roles' => ['permiso_ids' => DB::table('permiso')->orderBy('codigo')->pluck('codigo', 'id')->all()],
            default => [],
        };
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
                if (! empty($data['paciente_id'])) {
                    if ($role->nombre !== 'Paciente') {
                        Clinic::fail('Solo el rol Paciente puede vincularse a un paciente.', 'paciente_id');
                    }
                    $data['paciente_verificado_por'] = $actor->id;
                    $data['paciente_verificado_en'] = now();
                } else {
                    $data['paciente_id'] = null;
                    $data['paciente_verificado_por'] = null;
                    $data['paciente_verificado_en'] = null;
                }
            }
            if ($module === 'roles' && $record) {
                if (in_array($record->nombre, ['Administrador', 'Recepcionista', 'Medico', 'Laboratorista', 'Paciente'], true) && $data['nombre'] !== $record->nombre) {
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
                $user = User::findOrFail($data['usuario_id']);
                if ($user->roleName() !== 'Medico' || ! $user->isActive()) {
                    Clinic::fail('Selecciona una cuenta médica activa.', 'usuario_id');
                }
                if ($record && (int) $record->usuario_id !== (int) $data['usuario_id']) {
                    Clinic::fail('La cuenta profesional no puede trasladarse a otro usuario.', 'usuario_id');
                }
                foreach ($specialties as $specialtyId) {
                    if (! DB::table('especialidad')->where('id', $specialtyId)->where('activo', true)->exists()) {
                        Clinic::fail('Selecciona especialidades activas.', 'especialidad_ids');
                    }
                }
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
                $removed = DB::table('medico_especialidad')->where('medico_id', $id)->whereNotIn('especialidad_id', $specialties)->pluck('especialidad_id');
                DB::table('medico_especialidad')->where('medico_id', $id)->whereNotIn('especialidad_id', $specialties)->update(['activo' => false]);
                foreach ($specialties as $specialtyId) {
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
