<?php

namespace App\Modules\GestionAbastecimiento\Services;

use App\Models\User;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function lines(array $lines): array
    {
        $result = [];
        foreach ($lines as $line) {
            $id = (int) $line['producto_id'];
            if (isset($result[$id])) {
                Clinic::fail('Selecciona cada producto una sola vez.');
            }
            $product = DB::table('producto')->where('id', $id)->where('activo', true)->first() ?? abort(422, 'Selecciona productos activos.');
            $result[$id] = ['producto_id' => $id, 'cantidad' => (int) $line['cantidad'], 'precio_unitario' => Importe::decimal((string) $line['precio_unitario']), 'nombre' => $product->nombre];
        }

        return array_values($result);
    }

    public function create(User $user, array $data): int
    {
        Clinic::authorize($user, 'compras.gestionar');
        abort_unless(DB::table('proveedor')->where('id', $data['proveedor_id'])->where('activo', true)->lockForUpdate()->first(), 422, 'Selecciona un proveedor activo.');
        DB::table('producto')->whereIn('id', array_column($data['lineas'], 'producto_id'))->orderBy('id')->lockForUpdate()->get();
        $lines = $this->lines($data['lineas']);
        $id = DB::table('compra')->insertGetId(['proveedor_id' => $data['proveedor_id'], 'registrada_por' => $user->id, 'total' => Importe::total($lines)]);
        foreach ($lines as $line) {
            unset($line['nombre']);
            DB::table('detalle_compra')->insert(['compra_id' => $id] + $line);
        }

        return $id;
    }

    public function details(int $id)
    {
        return DB::table('detalle_compra as d')->join('producto as p', 'p.id', '=', 'd.producto_id')->where('d.compra_id', $id)->select('d.*', 'p.nombre', 'p.unidad_medida')
            ->selectRaw("coalesce((SELECT sum(m.cantidad) FROM movimiento_inventario m WHERE m.detalle_compra_id=d.id AND m.tipo='Entrada'),0) as recibida")->orderBy('d.id')->get();
    }

    public function receive(User $user, int $id, array $lines): int
    {
        Clinic::authorize($user, 'compras.gestionar');
        $purchase = DB::table('compra')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
        if (! in_array($purchase->estado, ['Registrada', 'Parcial'], true)) {
            Clinic::fail('La compra ya está recibida o cancelada.');
        }
        $details = $this->details($id)->keyBy('id');
        $products = DB::table('producto')->whereIn('id', $details->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        DB::table('lote')->whereIn('producto_id', $products->keys())->orderBy('id')->lockForUpdate()->get();
        $received = [];
        foreach ($lines as $line) {
            $detail = $details->get((int) $line['detalle_id']) ?? abort(404);
            $received[$detail->id] = ($received[$detail->id] ?? 0) + (int) $line['cantidad'];
            if ($received[$detail->id] > $detail->cantidad - $detail->recibida) {
                Clinic::fail('La cantidad recibida supera la pendiente del producto '.$detail->nombre.'.');
            }
            $number = mb_strtoupper(trim($line['numero_lote']));
            $product = $products[$detail->producto_id];
            $date = $line['fecha_vencimiento'] ?? null;
            if (($product->tipo === 'Medicamento' && ! $date) || ($date && $date < app(InventarioService::class)->today())) {
                Clinic::fail('La recepción requiere vencimiento vigente; es obligatorio en medicamentos.');
            }
            $lot = DB::table('lote')->where('producto_id', $product->id)->whereRaw('upper(btrim(numero_lote))=?', [$number])->first();
            if ($lot && ($lot->estado !== 'Disponible' || $lot->fecha_vencimiento !== $date)) {
                Clinic::fail('El lote está bloqueado o su vencimiento no coincide.');
            }
            DB::select("SELECT set_config('app.motivo_lote',?,true)", ['Recepción de compra #'.$id]);
            $lotId = $lot->id ?? DB::table('lote')->insertGetId(['producto_id' => $product->id, 'numero_lote' => $number, 'fecha_vencimiento' => $date]);
            DB::table('movimiento_inventario')->insert(['producto_id' => $product->id, 'lote_id' => $lotId, 'usuario_id' => $user->id,
                'detalle_compra_id' => $detail->id, 'tipo' => 'Entrada', 'cantidad' => (int) $line['cantidad'], 'motivo' => 'Recepción compra #'.$id]);
        }
        $complete = $this->details($id)->every(fn ($d) => (int) $d->cantidad === (int) $d->recibida);
        DB::table('compra')->where('id', $id)->update(['estado' => $complete ? 'Recibida' : 'Parcial']);

        return $id;
    }
}
