<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('vida_salud.rol')->where('nombre', 'Recepcionista')->value('id');
        if (! $roleId) {
            return;
        }
        foreach (['medicos.gestionar', 'especialidades.gestionar'] as $code) {
            DB::table('vida_salud.permiso')->insertOrIgnore(['codigo' => $code, 'descripcion' => $code === 'medicos.gestionar' ? 'Gestionar medicos' : 'Gestionar especialidades']);
            $permissionId = DB::table('vida_salud.permiso')->where('codigo', $code)->value('id');
            DB::table('vida_salud.rol_permiso')->insertOrIgnore(['rol_id' => $roleId, 'permiso_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        // Una reversión no debe retirar permisos que administración haya ratificado.
        // Los accesos pueden ajustarse desde la gestión de roles y permisos.
    }
};
