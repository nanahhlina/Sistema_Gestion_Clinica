<?php

namespace App\Modules\GestionAbastecimiento\Services;

use App\Models\User;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\GestionFinanciera\Services\PagoService;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    public function lines(array $lines, bool $confirm = false): array
    {
        $result = [];
        foreach ($lines as $l) {
            $id = (int) $l['producto_id'];
            if (isset($result[$id])) {
                Clinic::fail('Selecciona cada medicamento una sola vez.');
            }
            $p = DB::table('producto')->where('id', $id)->where('tipo', 'Medicamento')->where('activo', true)->whereNotNull('precio_venta')->first() ?? abort(422, 'Selecciona medicamentos activos con precio definido.');
            if ($confirm && (! isset($l['precio_esperado']) || Importe::decimal((string) $l['precio_esperado']) !== $p->precio_venta)) {
                Clinic::fail('El precio cambió. Revisa nuevamente la venta.');
            }
            $result[$id] = ['producto_id' => $id, 'cantidad' => (int) $l['cantidad'], 'precio_unitario' => $p->precio_venta, 'precio_esperado' => $p->precio_venta, 'nombre' => $p->nombre];
        }

        return array_values($result);
    }

    public function allocations(array $lines): array
    {
        $ids = array_column($lines, 'producto_id');
        $products = DB::table('producto')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $lots = DB::table('lote')->whereIn('producto_id', $ids)->orderBy('id')->lockForUpdate()->get()->sortBy(fn ($l) => ($l->fecha_vencimiento ?? '9999-12-31').str_pad((string) $l->id, 20, '0', STR_PAD_LEFT));
        $inventory = app(InventarioService::class);
        $allocations = [];
        foreach ($lines as $l) {
            $p = $products->get($l['producto_id']);
            if (! $p || ! $p->activo || $p->tipo !== 'Medicamento') {
                Clinic::fail('El medicamento está inactivo o no está disponible.');
            }
            $remaining = (int) $l['cantidad'];
            foreach ($lots as $lot) {
                if ($lot->producto_id !== $p->id || ! $inventory->ordinaryAllowed($p, $lot)) {
                    continue;
                }
                $take = min($remaining, max(0, $inventory->balance($lot->id)));
                if ($take <= 0) {
                    continue;
                }
                $allocations[] = ['producto_id' => $p->id, 'lote_id' => $lot->id, 'cantidad' => $take];
                $remaining -= $take;
                if ($remaining === 0) {
                    break;
                }
            }if ($remaining > 0) {
                Clinic::fail('Existencias insuficientes de '.$p->nombre.'. Revisa las cantidades.');
            }
        }

        return $allocations;
    }

    public function create(User $user, array $data): int
    {
        Clinic::authorize($user, 'ventas.gestionar');
        abort_unless(DB::table('paciente')->where('id', $data['paciente_id'])->where('activo', true)->lockForUpdate()->first(), 422, 'Selecciona un paciente activo.');
        DB::table('producto')->whereIn('id', array_column($data['lineas'], 'producto_id'))->orderBy('id')->lockForUpdate()->get();
        $lines = $this->lines($data['lineas'], true);
        $this->allocations($lines);
        $id = DB::table('venta')->insertGetId(['paciente_id' => $data['paciente_id'], 'registrada_por' => $user->id, 'total' => Importe::total($lines)]);
        foreach ($lines as $l) {
            unset($l['nombre'],$l['precio_esperado']);
            DB::table('detalle_venta')->insert(['venta_id' => $id] + $l);
        }
        $payment = app(PagoService::class)->create($user, null, $id, $data['metodo'], $data['recibido'] ?? null, $data['tasa_esperada'] ?? null, $data['usd_esperado'] ?? null);
        if ($data['metodo'] === 'Efectivo') {
            $this->deliver($id);
        }

        return $id;
    }

    public function deliver(int $id): bool
    {
        $sale = DB::table('venta')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
        if ($sale->estado === 'Completada') {
            return true;
        }
        if (! in_array($sale->estado, ['Pendiente', 'PendienteResolucion'], true)) {
            Clinic::fail('La venta no admite entrega.');
        }
        $p = DB::table('pago')->where('venta_id', $id)->where('estado', 'Completado')->first();
        if (! $p) {
            Clinic::fail('La venta necesita pago completado.');
        }
        $refund = DB::table('transaccion_paypal')->where('pago_id', $p->id)->first();
        if ($refund?->reembolso_solicitado_en && ! in_array($refund->estado_reembolso, ['FAILED', 'CANCELLED'], true)) {
            Clinic::fail('La venta tiene un reembolso solicitado y no admite entrega.');
        }
        $lines = DB::table('detalle_venta')->where('venta_id', $id)->orderBy('producto_id')->get();
        $allocations = $this->allocations($lines->map(fn ($l) => (array) $l)->all());
        $byProduct = $lines->keyBy('producto_id');
        // Bloqueo del pago al final, después de productos/lotes; ningún proceso de API retiene bloqueos al llamar a PayPal.
        DB::table('pago')->where('id', $p->id)->lockForUpdate()->first();
        foreach ($allocations as $a) {
            $detail = $byProduct[$a['producto_id']];
            $link = DB::table('detalle_venta_lote')->insertGetId(['detalle_venta_id' => $detail->id] + $a);
            DB::table('movimiento_inventario')->insert(['usuario_id' => $sale->registrada_por, 'detalle_venta_lote_id' => $link, 'tipo' => 'Salida', 'motivo' => 'Entrega venta #'.$id] + $a);
        }
        DB::table('venta')->where('id', $id)->update(['estado' => 'Completada']);

        return true;
    }

    public function resolveDelivery(User $actor, int $id): bool
    {
        return Clinic::transaction($actor, function () use ($id) {
            try {
                return DB::transaction(fn () => $this->deliver($id));
            } catch (ValidationException $e) {
                DB::table('venta')->where('id', $id)->whereIn('estado', ['Pendiente', 'PendienteResolucion'])->update(['estado' => 'PendienteResolucion']);

                return false;
            }
        });
    }
}
