<?php

namespace App\Modules\Shared;

use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Clinic
{
    public static function authorize(User $user, string $permission): void
    {
        abort_unless($user->hasPermission($permission), 403, 'No tienes autorización para esta operación.');
    }

    public static function fail(string $message, string $field = 'operacion'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /**
     * El contexto de auditoría solo vive durante la transacción. Los disparadores
     * PostgreSQL registran actor e IP sin crear eventos duplicados en PHP.
     */
    public static function transaction(User $user, Closure $callback): mixed
    {
        return DB::transaction(function () use ($user, $callback) {
            DB::select("SELECT set_config('app.usuario_id', ?, true), set_config('app.direccion_ip', ?, true)", [(string) $user->id, request()->ip() ?? '127.0.0.1']);

            return $callback();
        }, 3);
    }

    /** Obtiene el vínculo verificado de la sesión, nunca un ID enviado por el navegador. */
    public static function patientId(User $user): int
    {
        abort_unless($user->isPatient() && $user->paciente_id && $user->paciente_verificado_en, 403, 'La cuenta necesita un vínculo verificado por recepción.');

        return (int) $user->paciente_id;
    }

    public static function doctorId(User $user): int
    {
        $id = DB::table('medico')->where('usuario_id', $user->id)->where('activo', true)->value('id');
        abort_unless($id, 403, 'No tienes un perfil médico activo.');

        return (int) $id;
    }

    public static function date(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, 'America/La_Paz')->setTimezone('America/La_Paz');
    }

    public static function doctors(bool $includeInactive = false): array
    {
        return DB::table('medico as m')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->join('rol as r', 'r.id', '=', 'u.rol_id')
            ->when(! $includeInactive, fn ($query) => $query->where('m.activo', true)->where('u.activo', true)->where('r.activo', true))
            ->orderBy('u.apellidos')->orderBy('u.nombres')->orderBy('m.id')
            ->pluck(DB::raw("u.nombres || ' ' || u.apellidos || CASE WHEN m.activo AND u.activo AND r.activo THEN '' ELSE ' (inactivo)' END as label"), 'm.id')->all();
    }

    public static function specialties(): array
    {
        return DB::table('especialidad')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all();
    }
}
