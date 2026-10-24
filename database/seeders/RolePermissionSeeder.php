<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $bootstrapPermissions = ! DB::table('vida_salud.permiso')->where('codigo', 'perfil.propio')->exists();
            $permissions = [
                'usuarios.gestionar' => 'Gestionar cuentas de usuario',
                'roles.gestionar' => 'Gestionar roles y permisos',
                'pacientes.gestionar' => 'Gestionar datos administrativos de pacientes',
                'medicos.gestionar' => 'Gestionar medicos',
                'especialidades.gestionar' => 'Gestionar especialidades',
                'horarios.gestionar' => 'Gestionar horarios medicos',
                'analisis.gestionar' => 'Gestionar el catalogo de analisis',
                'citas.gestionar' => 'Gestionar citas de pacientes',
                'agenda.consultar' => 'Consultar la agenda en el ambito autorizado',
                'atencion.registrar' => 'Registrar atencion medica',
                'historia.consultar' => 'Consultar la historia clinica autorizada',
                'laboratorio.solicitar' => 'Solicitar analisis de laboratorio',
                'laboratorio.consultar' => 'Consultar ordenes y resultados autorizados',
                'laboratorio.procesar' => 'Registrar y corregir resultados de laboratorio',
                'reportes.administrativos' => 'Consultar reportes administrativos',
                'reportes.clinicos' => 'Consultar reportes clinicos propios',
                'reportes.laboratorio' => 'Consultar reportes de laboratorio',
                'bitacora.consultar' => 'Consultar la bitacora de auditoria',
                'citas.propias' => 'Consultar y gestionar las citas propias autorizadas',
                'perfil.propio' => 'Consultar el perfil y resultados publicados propios',
            ];

            foreach ($permissions as $code => $description) {
                DB::table('vida_salud.permiso')->updateOrInsert(['codigo' => $code], ['descripcion' => $description]);
            }

            $roles = [
                'Administrador' => ['usuarios.gestionar', 'roles.gestionar', 'pacientes.gestionar', 'citas.gestionar', 'agenda.consultar', 'medicos.gestionar', 'especialidades.gestionar',
                    'horarios.gestionar', 'analisis.gestionar', 'reportes.administrativos', 'bitacora.consultar'],
                'Recepcionista' => ['pacientes.gestionar', 'citas.gestionar', 'agenda.consultar', 'reportes.administrativos'],
                'Medico' => ['agenda.consultar', 'atencion.registrar', 'historia.consultar', 'laboratorio.solicitar', 'laboratorio.consultar', 'reportes.clinicos'],
                'Laboratorista' => ['laboratorio.consultar', 'laboratorio.procesar', 'reportes.laboratorio'],
                'Paciente' => ['citas.propias', 'perfil.propio'],
            ];

            $permissionIds = DB::table('vida_salud.permiso')->pluck('id', 'codigo');

            foreach ($roles as $name => $codes) {
                $roleExists = DB::table('vida_salud.rol')->where('nombre', $name)->exists();
                DB::table('vida_salud.rol')->insertOrIgnore(['nombre' => $name, 'descripcion' => 'Rol '.$name]);
                if ($roleExists && ! $bootstrapPermissions) {
                    continue;
                }
                $roleId = (int) DB::table('vida_salud.rol')->where('nombre', $name)->value('id');
                $assignedIds = array_map(fn (string $code): int => (int) $permissionIds[$code], $codes);

                DB::table('vida_salud.rol_permiso')->where('rol_id', $roleId)
                    ->whereIn('permiso_id', $permissionIds->values())
                    ->whereNotIn('permiso_id', $assignedIds)->delete();

                foreach ($assignedIds as $permissionId) {
                    DB::table('vida_salud.rol_permiso')->insertOrIgnore(['rol_id' => $roleId, 'permiso_id' => $permissionId]);
                }
            }
        });
    }
}
