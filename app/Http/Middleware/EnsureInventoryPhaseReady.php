<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureInventoryPhaseReady extends EnsureFinancialPhaseReady
{
    public function handle(Request $request, Closure $next)
    {
        return parent::handle($request, function (Request $request) use ($next) {
            if (! DB::table('migrations')->where('migration', '2026_11_07_000007_protect_inventory_traceability')->exists()) {
                $message = 'El inventario requiere completar la actualización autorizada de su trazabilidad.';

                return $request->expectsJson() ? response()->json(['message' => $message], 503)
                    : response()->view('workflows.invalid', ['messages' => [$message]], 503);
            }

            return $next($request);
        });
    }
}
