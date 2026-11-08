<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureFinancialPhaseReady
{
    public function handle(Request $request, Closure $next)
    {
        $ready = DB::table('migrations')->where('migration', '2026_11_07_000006_configure_financial_inventory_permissions_and_audit')->exists()
            && DB::selectOne("SELECT to_regclass('vida_salud.operacion_web') AS name")->name;
        if (! $ready) {
            $message = 'Esta función requiere completar la actualización autorizada de la base de datos.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : response()->view('workflows.invalid', ['messages' => [$message]], 503);
        }

        return $next($request);
    }
}
