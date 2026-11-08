<?php

namespace App\Modules\GestionAbastecimiento\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class LoteService
{
    public function version(object $lot): string
    {
        return hash('sha256', json_encode([$lot->producto_id, $lot->numero_lote, $lot->fecha_vencimiento, $lot->estado]));
    }

    public function choices(?int $current = null): array
    {
        return DB::table('producto')->where(fn ($q) => $q->where('activo', true)->when($current, fn ($q) => $q->orWhere('id', $current)))
            ->orderBy('nombre')->pluck(DB::raw("nombre || ' (' || unidad_medida || ')' || CASE WHEN activo THEN '' ELSE ' — inactivo' END as label"), 'id')->all();
    }

    public function save(User $user, ?int $id, array $data): int
    {
        Clinic::authorize($user, 'lotes.gestionar');
        // Bloquea productos en orden incluso si se corrige la asociación de un lote sin movimientos.
        $previous = $id ? DB::table('lote')->where('id', $id)->first() ?? abort(404) : null;
        $productIds = array_unique([(int) $data['producto_id'], (int) ($previous->producto_id ?? $data['producto_id'])]);
        sort($productIds);
        $products = DB::table('producto')->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $product = $products->get((int) $data['producto_id']) ?? abort(404);
        $lot = $id ? DB::table('lote')->where('id', $id)->lockForUpdate()->first() ?? abort(404) : null;
        if ($lot && ! hash_equals($this->version($lot), $data['version'])) {
            Clinic::fail('El lote cambió durante la operación. Consulta sus datos nuevamente.');
        }
        $values = ['producto_id' => (int) $data['producto_id'], 'numero_lote' => mb_strtoupper(trim($data['numero_lote'])),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null, 'estado' => $data['estado']];
        $identityChanged = $lot && ($lot->producto_id !== $values['producto_id'] || $lot->numero_lote !== $values['numero_lote'] || $lot->fecha_vencimiento !== $values['fecha_vencimiento']);
        if ((! $lot || $identityChanged) && ! $product->activo) {
            Clinic::fail('Selecciona un producto activo para registrar o corregir los datos del lote.');
        }
        if ($product->tipo === 'Medicamento' && $values['fecha_vencimiento'] === null) {
            Clinic::fail('Los medicamentos requieren fecha de vencimiento.', 'fecha_vencimiento');
        }
        if ($identityChanged && DB::table('movimiento_inventario')->where('lote_id', $id)->exists()) {
            Clinic::fail('El producto, número y vencimiento se conservan después del primer movimiento. Puedes cambiar el estado con un motivo.');
        }
        if (DB::table('lote')->where('producto_id', $values['producto_id'])->whereRaw('upper(btrim(numero_lote))=?', [$values['numero_lote']])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists()) {
            Clinic::fail('Este número de lote ya existe para el producto.', 'numero_lote');
        }
        DB::select("SELECT set_config('app.motivo_lote',?,true)", [trim($data['motivo'])]);
        if ($lot) {
            DB::table('lote')->where('id', $id)->update($values);

            return $id;
        }

        return DB::table('lote')->insertGetId($values);
    }
}
