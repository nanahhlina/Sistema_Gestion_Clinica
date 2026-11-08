<?php

namespace App\Modules\GestionFinanciera\Services;

use App\Models\User;
use App\Modules\GestionAbastecimiento\Services\VentaService;
use App\Modules\Shared\Clinic;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayPalService
{
    public function __construct(private PayPalGateway $gateway, private PagoService $payments) {}

    private function actor(object $p): User
    {
        return User::findOrFail($p->registrado_por);
    }

    private function claim(int $id, User $actor, bool $pendingOnly = false): ?object
    {
        return Clinic::transaction($actor, function () use ($id, $pendingOnly) {
            $p = DB::table('pago')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if ($pendingOnly && $p->estado !== 'Pendiente') {
                return null;
            }
            if ($p->metodo !== 'PayPal' || ! in_array($p->estado, ['Pendiente', 'Completado'], true)) {
                return null;
            }
            $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
            if ($p->estado === 'Completado' && (! $t?->reembolso_solicitado_en || in_array($t->estado_reembolso, ['COMPLETED', 'FAILED', 'CANCELLED'], true))) {
                return null;
            }
            if ($p->bloqueo_hasta && CarbonImmutable::parse($p->bloqueo_hasta)->isFuture()) {
                return null;
            }
            $owner = (string) Str::uuid();
            $version = $p->version_operacion + 1;
            DB::table('pago')->where('id', $id)->update(['propietario' => $owner, 'version_operacion' => $version, 'bloqueo_hasta' => DB::raw("clock_timestamp()+interval '3 minutes'"), 'ultima_verificacion_en' => DB::raw('clock_timestamp()'), 'operacion_actual' => $p->estado === 'Completado' ? 'Reembolsar' : 'Conciliar', 'resultado_incierto' => true]);

            return DB::table('pago')->find($id);
        });
    }

    private function owned(object $claim, User $actor, \Closure $action): bool
    {
        return Clinic::transaction($actor, function () use ($claim, $action) {
            $p = DB::table('pago')->where('id', $claim->id)->lockForUpdate()->first();
            if (! $p || $p->propietario !== $claim->propietario || $p->version_operacion !== $claim->version_operacion || ! $p->bloqueo_hasta || ! CarbonImmutable::parse($p->bloqueo_hasta)->isFuture()) {
                return false;
            }
            $action($p);

            return true;
        });
    }

    private function release(object $claim, User $actor, bool $uncertain = false, array $values = []): bool
    {
        return $this->owned($claim, $actor, fn () => DB::table('pago')->where('id', $claim->id)->update($values + ['propietario' => null, 'bloqueo_hasta' => null, 'resultado_incierto' => $uncertain]));
    }

    private function retryWindow(?string $started): void
    {
        // Ventana conservadora inferior a la retención de idempotencia del endpoint.
        if ($started && CarbonImmutable::parse($started)->lessThan(CarbonImmutable::now()->subMinutes(5))) {
            throw new PasarelaException('Resultado incierto fuera de la ventana de reintento automático. Requiere conciliación administrativa; no iniciar otro cobro.');
        }
    }

    private function identifier(mixed $id): string
    {
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9-]{1,100}$/D', $id)) {
            throw new PasarelaException('Identificador externo inválido.');
        }

        return $id;
    }

    private function money(array $amount, object $p): void
    {
        if (($amount['currency_code'] ?? '') !== 'USD' || ! is_string($amount['value'] ?? null) || ! preg_match('/^\d{1,10}\.\d{2}$/D', $amount['value']) || $amount['value'] !== $p->monto_pasarela) {
            throw new PasarelaException('Importe o moneda no coinciden con la cotización confirmada.');
        }
    }

    private function validateOrder(array $order, object $p, string $id): array
    {
        if (($order['id'] ?? '') !== $id || ($order['intent'] ?? '') !== 'CAPTURE' || count($order['purchase_units'] ?? []) !== 1) {
            throw new PasarelaException('Orden externa incompatible.');
        }
        $unit = $order['purchase_units'][0];
        if (($unit['custom_id'] ?? '') !== (string) $p->id || ($unit['reference_id'] ?? '') !== (string) $p->id || ($unit['invoice_id'] ?? '') !== 'VS-SB-'.$p->clave_pasarela || ($unit['payee']['merchant_id'] ?? '') !== $p->destinatario_paypal) {
            throw new PasarelaException('La orden no corresponde al pago y destinatario esperados.');
        }
        $this->money($unit['amount'] ?? [], $p);

        return $unit;
    }

    public function prepareRefund(User $actor, int $id, string $reason): int
    {
        $this->payments->requireRefundAdmin($actor);
        $p = $this->payments->refundable($id);
        if ($p->metodo !== 'PayPal') {
            Clinic::fail('El pago no corresponde a PayPal.');
        }
        $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
        if (! $t?->paypal_capture_id) {
            Clinic::fail('No existe captura verificada para devolver.');
        }
        if (! $t->reembolso_solicitado_en) {
            DB::table('transaccion_paypal')->where('id', $t->id)->update(['reembolso_solicitado_en' => DB::raw('clock_timestamp()'), 'reembolso_solicitado_por' => $actor->id, 'motivo_reembolso' => trim($reason), 'estado_reembolso' => 'SOLICITADO', 'fecha_actualizacion' => DB::raw('clock_timestamp()')]);
        }

        return $id;
    }

    public function process(int $id, ?User $operator = null, bool $cancel = false, bool $pendingOnly = false): string
    {
        if (! $this->gateway->configured()) {
            return 'PayPal Sandbox no está configurado; no se realizó ninguna llamada externa.';
        }
        $peek = DB::table('pago')->find($id) ?? abort(404);
        $actor = $operator ?? $this->actor($peek);
        if ($operator) {
            $this->payments->authorize($operator, $peek);
        }
        $p = $this->claim($id, $actor, $pendingOnly);
        if (! $p) {
            return 'Pago ya verificado o atendido por otro proceso.';
        }
        try {
            $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
            if ($p->estado === 'Completado') {
                return $this->refund($p, $t, $actor);
            }
            if (! $t) {
                if ($cancel && $p->creacion_paypal_iniciada_en === null) {
                    $this->release($p, $actor, false, ['estado' => 'Cancelado']);

                    return 'Intento cancelado sin orden externa.';
                }
                $this->retryWindow($p->creacion_paypal_iniciada_en);
                if (! $this->owned($p, $actor, fn () => DB::table('pago')->where('id', $id)->update(['operacion_actual' => 'Crear', 'creacion_paypal_iniciada_en' => DB::raw('coalesce(creacion_paypal_iniciada_en,clock_timestamp())')]))) {
                    return 'Otro proceso conserva la operación.';
                }
                $created = $this->gateway->create($p);
                $orderId = $this->identifier($created['id'] ?? null);
                if (! $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->insert(['pago_id' => $id, 'paypal_order_id' => $orderId, 'estado' => 'VERIFICANDO']))) {
                    return 'La orden será recuperada por conciliación.';
                }
                $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
            }
            $order = $this->gateway->order($t->paypal_order_id);
            $unit = $this->validateOrder($order, $p, $t->paypal_order_id);
            $caps = $unit['payments']['captures'] ?? [];
            if (count($caps) > 1) {
                throw new PasarelaException('La orden contiene múltiples capturas inesperadas.');
            }
            if ($cancel && ! $caps && ! $t->captura_solicitada_en && in_array($order['status'] ?? '', ['CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED', 'APPROVED', 'VOIDED'], true)) {
                $this->release($p, $actor, false, ['estado' => 'Cancelado']);

                return 'Intento cancelado sin captura; una aprobación tardía no será capturada.';
            }
            if (! $caps && ($order['status'] ?? '') === 'APPROVED') {
                $this->retryWindow($t->captura_solicitada_en);
                if (! $this->owned($p, $actor, function () use ($id, $t) {
                    DB::table('pago')->where('id', $id)->update(['operacion_actual' => 'Capturar']);
                    DB::table('transaccion_paypal')->where('id', $t->id)->update(['captura_solicitada_en' => DB::raw('coalesce(captura_solicitada_en,clock_timestamp())')]);
                })) {
                    return 'Otro proceso conserva la captura.';
                }
                $this->gateway->capture($p, $t->paypal_order_id);
                $order = $this->gateway->order($t->paypal_order_id);
                $unit = $this->validateOrder($order, $p, $t->paypal_order_id);
                $caps = $unit['payments']['captures'] ?? [];
                if (count($caps) > 1) {
                    throw new PasarelaException('La orden contiene múltiples capturas inesperadas.');
                }
            }
            if (count($caps) === 1) {
                $captureId = $this->identifier($caps[0]['id'] ?? null);
                $capture = $this->gateway->captureDetails($captureId);
                if (($capture['id'] ?? '') !== $captureId || ($capture['supplementary_data']['related_ids']['order_id'] ?? '') !== $t->paypal_order_id) {
                    throw new PasarelaException('La captura pertenece a otra orden.');
                }
                $this->money($capture['amount'] ?? [], $p);
                $this->money($caps[0]['amount'] ?? [], $p);
                if ($t->paypal_capture_id && $t->paypal_capture_id !== $captureId) {
                    throw new PasarelaException('Identificador de captura distinto del registrado.');
                }
                if (! $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->where('id', $t->id)->update(['paypal_capture_id' => $captureId, 'estado' => (string) ($capture['status'] ?? 'PENDING'), 'fecha_actualizacion' => DB::raw('clock_timestamp()')]))) {
                    return 'Captura en conciliación por otro proceso.';
                }
                if (($capture['status'] ?? '') === 'COMPLETED' && ($caps[0]['status'] ?? '') === 'COMPLETED' && ($order['status'] ?? '') === 'COMPLETED') {
                    if (! $this->release($p, $actor, false, ['estado' => 'Completado', 'fecha_confirmacion' => DB::raw('clock_timestamp()')])) {
                        return 'Captura en conciliación por otro proceso.';
                    }
                    $current = DB::table('pago')->find($id);
                    if ($current->estado === 'Completado' && $current->venta_id) {
                        app(VentaService::class)->resolveDelivery($this->actor($current), $current->venta_id);
                    }

                    return 'Pago completado y verificado en el servidor.';
                }
                if (in_array($capture['status'] ?? '', ['DECLINED', 'FAILED'], true)) {
                    $this->release($p, $actor, false, ['estado' => 'Fallido']);

                    return 'PayPal rechazó la captura.';
                }
                $this->release($p, $actor, true);

                return 'Captura pendiente de verificación. No iniciar otro cobro.';
            }
            if (in_array($order['status'] ?? '', ['VOIDED', 'DECLINED'], true)) {
                $this->release($p, $actor, false, ['estado' => 'Cancelado']);

                return 'Orden cancelada por PayPal.';
            }
            $approval = $this->gateway->approval($order);
            $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->where('id', $t->id)->update(['approval_url' => $approval, 'estado' => in_array($order['status'] ?? '', ['CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED', 'APPROVED', 'COMPLETED'], true) ? $order['status'] : 'VERIFICANDO', 'fecha_actualizacion' => DB::raw('clock_timestamp()')]));
            $this->release($p, $actor, (bool) $t->captura_solicitada_en);

            return 'Pendiente de aprobación o verificación en PayPal Sandbox.';
        } catch (RechazoPayPalException $e) {
            try {
                $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
                if ($p->estado === 'Pendiente' && $t) {
                    $order = $this->gateway->order($t->paypal_order_id);
                    $unit = $this->validateOrder($order, $p, $t->paypal_order_id);
                    if (empty($unit['payments']['captures'])) {
                        $this->release($p, $actor, false, ['estado' => 'Fallido']);

                        return 'PayPal rechazó el pago sin captura. Puedes iniciar otro intento o utilizar efectivo.';
                    }
                }
            } catch (PasarelaException) {
            }
            $this->release($p, $actor, true);

            return 'Rechazo pendiente de conciliación; no iniciar otro cobro.';
        } catch (PasarelaException $e) {
            $this->release($p, $actor, true);

            return $e->getMessage();
        }
    }

    private function refund(object $p, object $t, User $actor): string
    {
        if (! $t->paypal_capture_id || ! $t->reembolso_solicitado_por || ! $t->motivo_reembolso) {
            throw new PasarelaException('Falta solicitud administrativa verificable.');
        }
        $capture = $this->gateway->captureDetails($t->paypal_capture_id);
        if (($capture['id'] ?? '') !== $t->paypal_capture_id || ($capture['supplementary_data']['related_ids']['order_id'] ?? '') !== $t->paypal_order_id) {
            throw new PasarelaException('Captura de devolución incompatible.');
        }
        $this->money($capture['amount'] ?? [], $p);
        if (! $t->paypal_refund_id) {
            $this->retryWindow($t->reembolso_solicitado_en);
            if (! in_array($capture['status'] ?? '', ['COMPLETED', 'REFUNDED'], true)) {
                throw new PasarelaException('Captura no reembolsable o pendiente de conciliación.');
            }
            if (! $this->owned($p, $actor, fn () => DB::table('pago')->where('id', $p->id)->update(['operacion_actual' => 'Reembolsar']))) {
                return 'Otro proceso conserva el reembolso; no se envió otra solicitud.';
            }
            $created = $this->gateway->refund($p, $t->paypal_capture_id);
            $refundId = $this->identifier($created['id'] ?? null);
            if (! $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->where('id', $t->id)->update(['paypal_refund_id' => $refundId]))) {
                return 'Reembolso en conciliación por otro proceso.';
            }
            $t = DB::table('transaccion_paypal')->where('id', $t->id)->first();
        }
        $refund = $this->gateway->refundDetails($t->paypal_refund_id);
        if (($refund['id'] ?? '') !== $t->paypal_refund_id) {
            throw new PasarelaException('Identificador de devolución incompatible.');
        }$this->money($refund['amount'] ?? [], $p);
        $status = $refund['status'] ?? 'PENDING';
        if (! in_array($status, ['COMPLETED', 'PENDING', 'FAILED', 'CANCELLED'], true)) {
            throw new PasarelaException('Estado de devolución no reconocido.');
        }
        if ($status === 'COMPLETED') {
            Clinic::transaction($actor, function () use ($p, $t) {
                DB::table($p->cita_id ? 'cita' : 'venta')->where('id', $p->cita_id ?? $p->venta_id)->lockForUpdate()->first();
                $current = DB::table('pago')->where('id', $p->id)->lockForUpdate()->first();
                if ($current->propietario !== $p->propietario || $current->version_operacion !== $p->version_operacion || ! CarbonImmutable::parse($current->bloqueo_hasta)->isFuture()) {
                    return;
                }
                $eligible = $this->payments->refundable($p->id);
                $this->payments->finishRefund($eligible, $t->reembolso_solicitado_por, $t->motivo_reembolso);
                DB::table('transaccion_paypal')->where('id', $t->id)->update(['estado_reembolso' => 'COMPLETED', 'reembolso_confirmado_en' => DB::raw('clock_timestamp()'), 'fecha_actualizacion' => DB::raw('clock_timestamp()')]);
                DB::table('pago')->where('id', $p->id)->update(['propietario' => null, 'bloqueo_hasta' => null, 'resultado_incierto' => false]);
            });

            return 'Reembolso completo verificado.';
        }
        $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->where('id', $t->id)->update(['estado_reembolso' => $status, 'fecha_actualizacion' => DB::raw('clock_timestamp()')]));
        $this->release($p, $actor, $status === 'PENDING');

        return 'Devolución '.$status.'; conserva el pago original hasta confirmación.';
    }

    public function associate(User $actor, int $id, ?string $orderId, ?string $refundId): string
    {
        $this->payments->requireRefundAdmin($actor);
        if (! $this->gateway->configured()) {
            Clinic::fail('Configura PayPal Sandbox antes de conciliar identificadores.');
        }
        $p = $this->claim($id, $actor);
        if (! $p) {
            return 'Pago finalizado o atendido por otro proceso.';
        }
        try {
            $t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
            if ($orderId) {
                $orderId = $this->identifier($orderId);
                $order = $this->gateway->order($orderId);
                $this->validateOrder($order, $p, $orderId);
                if ($t && $t->paypal_order_id !== $orderId) {
                    throw new PasarelaException('El pago ya tiene otra orden registrada.');
                }
                if (! $t) {
                    if (! $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->insert(['pago_id' => $id, 'paypal_order_id' => $orderId, 'estado' => 'VERIFICANDO']))) {
                        return 'Otro proceso conserva la conciliación.';
                    }$t = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
                }
            }
            if ($refundId) {
                if (! $t?->paypal_capture_id || ! $t->reembolso_solicitado_en) {
                    throw new PasarelaException('Falta captura o solicitud administrativa de reembolso.');
                }
                $refundId = $this->identifier($refundId);
                $refund = $this->gateway->refundDetails($refundId);
                $this->money($refund['amount'] ?? [], $p);
                $up = collect($refund['links'] ?? [])->firstWhere('rel', 'up');
                if (($refund['id'] ?? '') !== $refundId || ($up['href'] ?? '') !== 'https://api-m.sandbox.paypal.com/v2/payments/captures/'.$t->paypal_capture_id || ($t->paypal_refund_id && $t->paypal_refund_id !== $refundId)) {
                    throw new PasarelaException('La devolución externa no corresponde a la captura registrada.');
                }
                $this->owned($p, $actor, fn () => DB::table('transaccion_paypal')->where('id', $t->id)->update(['paypal_refund_id' => $refundId]));
            }
            $this->release($p, $actor, true);

            return 'Identificadores verificados. Ejecuta verificar para conciliar su estado.';
        } catch (PasarelaException $e) {
            $this->release($p, $actor, true);

            return $e->getMessage();
        }
    }
}
