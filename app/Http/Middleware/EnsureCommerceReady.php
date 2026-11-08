<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureCommerceReady extends EnsureInventoryPhaseReady
{
    public function handle(Request $request, Closure $next)
    {
        return parent::handle($request, function (Request $request) use ($next) {
            if (DB::table('migrations')->whereIn('migration', ['2026_11_07_000008_complete_payment_processing_support', '2026_11_07_000009_keep_paypal_reconciliation_fair'])->count() !== 2) {
                $message = 'Pagos y ventas requieren completar la actualización autorizada de la base de datos.';

                return $request->expectsJson() ? response()->json(['message' => $message], 503) : response()->view('workflows.invalid', ['messages' => [$message]], 503);
            }

            return $next($request);
        });
    }
}
