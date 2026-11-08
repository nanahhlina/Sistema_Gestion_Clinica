<?php

namespace App\Modules\GestionFinanciera\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class PagoService
{
    public function authorize(User $user, object $payment): void
    {
        Clinic::authorize($user, $payment->cita_id ? 'pagos.registrar' : 'ventas.gestionar');
        abort_if($user->isPatient() || $user->isDoctor(), 403);
    }

    public function destination(?int $appointment, ?int $sale): object
    {
        if (($appointment === null) === ($sale === null)) {
            Clinic::fail('Selecciona un único destino para el pago.');
        }
        $row = DB::table($appointment ? 'cita' : 'venta')->where('id', $appointment ?? $sale)->lockForUpdate()->first() ?? abort(404);
        if ($appointment) {
            if (! in_array($row->estado, ['Programada', 'Confirmada', 'Atendida'], true) || $row->precio_consulta === null) {
                Clinic::fail('La cita requiere estado permitido y precio definido.');
            }
        } elseif ($row->estado !== 'Pendiente') {
            Clinic::fail('La venta ya fue finalizada o necesita resolución.');
        }

        return $row;
    }

    public function create(User $user, ?int $appointment, ?int $sale, string $method, ?string $received, ?string $expectedRate = null, ?string $expectedUsd = null): int
    {
        Clinic::authorize($user, $appointment ? 'pagos.registrar' : 'ventas.gestionar');
        $row = $this->destination($appointment, $sale);
        $amount = Importe::decimal((string) ($appointment ? $row->precio_consulta : $row->total));
        $field = $appointment ? 'cita_id' : 'venta_id';
        $target = $appointment ?? $sale;
        if (DB::table('pago')->where($field, $target)->whereIn('estado', ['Pendiente', 'Completado'])->exists()) {
            Clinic::fail('Ya existe un pago pendiente o completado para este registro.');
        }
        $data = [$field => $target, 'registrado_por' => $user->id, 'monto' => $amount, 'moneda' => 'BOB', 'metodo' => $method];
        if ($method === 'Efectivo') {
            $received = Importe::decimal($received ?? '');
            if (Importe::centavos($received) < Importe::centavos($amount)) {
                Clinic::fail('El efectivo recibido no cubre el importe total.');
            }
            $data += ['estado' => 'Completado', 'fecha_confirmacion' => DB::raw('clock_timestamp()'), 'recibido_efectivo' => $received, 'cambio_efectivo' => Importe::fromCents(Importe::centavos($received) - Importe::centavos($amount))];
        } elseif ($method === 'PayPal') {
            $merchant = (string) config('services.paypal.merchant_id');
            if (! config('services.paypal.enabled') || ! config('services.paypal.client_id') || ! config('services.paypal.client_secret') || $merchant === '') {
                Clinic::fail('PayPal Sandbox no está configurado. Puedes utilizar efectivo.');
            }
            DB::table('configuracion_financiera')->where('id', 1)->sharedLock()->first();
            $quote = app(ConfiguracionFinancieraService::class)->quote($amount);
            if ($expectedRate !== null && ($expectedRate !== $quote['bob_por_usd'] || $expectedUsd !== $quote['monto_pasarela'])) {
                Clinic::fail('La cotización cambió. Revisa nuevamente el importe en USD.');
            }
            $data += $quote + ['destinatario_paypal' => $merchant];
        } else {
            Clinic::fail('Método de pago inválido.');
        }

        return DB::table('pago')->insertGetId($data);
    }

    public function requireRefundAdmin(User $user): void
    {
        Clinic::authorize($user, 'pagos.reembolsar');
        abort_unless($user->roleName() === 'Administrador', 403);
    }

    /** Bloquea destino antes del pago; comparte orden con entrega y cobro. */
    public function refundable(int $id): object
    {
        $peek = DB::table('pago')->find($id) ?? abort(404);
        $target = DB::table($peek->cita_id ? 'cita' : 'venta')->where('id', $peek->cita_id ?? $peek->venta_id)->lockForUpdate()->first() ?? abort(404);
        $p = DB::table('pago')->where('id', $id)->lockForUpdate()->first();
        if ($p->estado !== 'Completado' || ($p->cita_id ? $target->estado !== 'Cancelada' : $target->estado !== 'PendienteResolucion')) {
            Clinic::fail('La devolución exige pago completado y cita cancelada o venta sin entrega pendiente de resolución.');
        }

        return $p;
    }

    public function finishRefund(object $p, int $actor, string $reason): void
    {
        DB::table('pago')->where('id', $p->id)->update(['estado' => 'Reembolsado', 'reembolsado_por' => $actor, 'reembolsado_en' => DB::raw('clock_timestamp()'), 'motivo_reembolso' => trim($reason)]);
        if ($p->venta_id) {
            DB::table('venta')->where('id', $p->venta_id)->update(['estado' => 'Cancelada']);
        }
    }

    public function cashRefund(User $user, int $id, string $reason): int
    {
        $this->requireRefundAdmin($user);
        $p = $this->refundable($id);
        if ($p->metodo !== 'Efectivo') {
            Clinic::fail('Este pago requiere reembolso verificado mediante PayPal.');
        }
        $this->finishRefund($p, $user->id, $reason);

        return $id;
    }
}
