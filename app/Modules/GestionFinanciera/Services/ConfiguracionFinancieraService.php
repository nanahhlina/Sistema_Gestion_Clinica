<?php

namespace App\Modules\GestionFinanciera\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class ConfiguracionFinancieraService
{
    public function save(User $user, array $data): int
    {
        Clinic::authorize($user, 'finanzas.configurar');
        // Una fila aún inexistente también necesita exclusión entre dos altas iniciales.
        DB::select('SELECT pg_advisory_xact_lock(210026,1)');
        $current = DB::table('configuracion_financiera')->where('id', 1)->lockForUpdate()->first();
        if ((int) ($current->version ?? 0) !== (int) $data['version']) {
            Clinic::fail('El tipo de cambio cambió durante la operación. Consulta su valor nuevamente.');
        }
        $values = ['bob_por_usd' => $data['bob_por_usd'], 'motivo' => trim($data['motivo']), 'actualizada_por' => $user->id, 'version' => (int) $data['version'] + 1];
        if ($current) {
            DB::table('configuracion_financiera')->where('id', 1)->update($values);
        } else {
            DB::table('configuracion_financiera')->insert(['id' => 1] + $values);
        }

        return 1;
    }

    /** La división y el redondeo usan numeric de PostgreSQL, nunca float de PHP. */
    public function quote(string $amount): array
    {
        $amount = Importe::decimal($amount);
        $row = DB::selectOne('SELECT bob_por_usd::text AS tasa, round(?::numeric/bob_por_usd,2)::text AS usd, clock_timestamp() AS fecha FROM configuracion_financiera WHERE id=1', [$amount]);
        if (! $row || $row->usd === '0.00') {
            Clinic::fail('Configura una tasa válida y un importe que permita cobrar al menos USD 0.01.');
        }
        if (strlen(explode('.', $row->usd)[0]) > 10) {
            Clinic::fail('El importe convertido supera el límite permitido.');
        }

        return ['monto' => $amount, 'moneda' => 'BOB', 'monto_pasarela' => $row->usd, 'moneda_pasarela' => 'USD', 'bob_por_usd' => $row->tasa, 'cotizada_en' => $row->fecha];
    }
}
