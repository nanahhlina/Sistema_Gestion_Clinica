<?php

namespace App\Modules\Shared\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OperacionWeb
{
    public static function token(): string
    {
        return (string) Str::uuid();
    }

    /** La clave firmada no contiene datos clínicos; no permite cambiar de operación ni usuario. */
    public static function signature(User $user, string $operation, string $key): string
    {
        return hash_hmac('sha256', $user->id.'|'.$operation.'|'.$key, config('app.key'));
    }

    public static function run(Request $request, string $operation, Closure $action): int
    {
        $data = Validator::make($request->only('clave', 'firma'), ['clave' => 'required|uuid', 'firma' => 'required|string'])->validate();
        abort_unless(hash_equals(self::signature($request->user(), $operation, $data['clave']), $data['firma']), 422, 'Formulario inválido. Abre nuevamente la operación.');

        return Clinic::transaction($request->user(), function () use ($request, $operation, $data, $action) {
            // Serializa reintentos de la misma cuenta, incluso entre distintos procesos HTTP.
            DB::select('select pg_advisory_xact_lock(?)', [(int) $request->user()->id]);
            $previous = DB::table('operacion_web')->where('usuario_id', $request->user()->id)->where('clave', $data['clave'])->first();
            if ($previous) {
                abort_unless($previous->operacion === $operation, 422);

                return (int) $previous->recurso_id;
            }
            $id = (int) $action();
            DB::table('operacion_web')->insert(['usuario_id' => $request->user()->id, 'clave' => $data['clave'], 'operacion' => $operation, 'recurso_id' => $id]);

            return $id;
        });
    }
}
