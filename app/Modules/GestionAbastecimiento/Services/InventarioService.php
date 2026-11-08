<?php

namespace App\Modules\GestionAbastecimiento\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    public const TYPES = ['Entrada' => 'Entrada', 'Salida' => 'Salida', 'AjusteEntrada' => 'Ajuste de entrada', 'AjusteSalida' => 'Ajuste de salida'];

    public function today(): string
    {
        return CarbonImmutable::now('America/La_Paz')->toDateString();
    }

    public function balances(): Builder
    {
        return DB::table('movimiento_inventario')->select('lote_id')->selectRaw("sum(CASE WHEN tipo IN ('Entrada','AjusteEntrada') THEN cantidad::bigint ELSE -cantidad::bigint END) as saldo")->groupBy('lote_id');
    }

    public function lots(): Builder
    {
        return DB::table('lote as l')->join('producto as p', 'p.id', '=', 'l.producto_id')->leftJoinSub($this->balances(), 's', 's.lote_id', '=', 'l.id')
            ->select('l.*', 'p.nombre as producto', 'p.tipo', 'p.unidad_medida', 'p.activo as producto_activo')->selectRaw('coalesce(s.saldo,0) as saldo')
            ->selectRaw("CASE WHEN p.activo AND l.estado='Disponible' AND (l.fecha_vencimiento>=?::date OR (l.fecha_vencimiento IS NULL AND p.tipo='Insumo')) THEN coalesce(s.saldo,0) ELSE 0 END as disponible", [$this->today()]);
    }

    public function products(): Builder
    {
        return DB::table('producto as p')->leftJoinSub($this->lots(), 'e', 'e.producto_id', '=', 'p.id')->select('p.id', 'p.nombre', 'p.tipo', 'p.unidad_medida', 'p.activo')
            ->selectRaw('coalesce(sum(e.saldo),0) as saldo, coalesce(sum(e.disponible),0) as disponible')->groupBy('p.id', 'p.nombre', 'p.tipo', 'p.unidad_medida', 'p.activo');
    }

    public function balance(int $lot): int
    {
        return (int) ($this->balances()->where('lote_id', $lot)->first()?->saldo ?? 0);
    }

    /** Historial completo antes de filtrar: los saldos incluyen movimientos anteriores al rango. */
    public function ledger(): Builder
    {
        $signed = "CASE WHEN m.tipo IN ('Entrada','AjusteEntrada') THEN m.cantidad::bigint ELSE -m.cantidad::bigint END";
        $ledger = DB::table('movimiento_inventario as m')->join('lote as l', 'l.id', '=', 'm.lote_id')->join('producto as p', 'p.id', '=', 'm.producto_id')->join('usuario as u', 'u.id', '=', 'm.usuario_id')
            ->select('m.*', 'l.numero_lote', 'p.nombre as producto', 'p.unidad_medida')->selectRaw("concat_ws(' ',u.nombres,u.apellidos) as responsable")
            ->selectRaw("sum({$signed}) OVER (PARTITION BY m.lote_id ORDER BY m.fecha,m.id ROWS UNBOUNDED PRECEDING) as saldo_lote")
            ->selectRaw("sum({$signed}) OVER (PARTITION BY m.producto_id ORDER BY m.fecha,m.id ROWS UNBOUNDED PRECEDING) as saldo_producto");

        return DB::query()->fromSub($ledger, 'k');
    }

    /** Todas las escrituras de fases posteriores deberán tomar producto y luego lote. */
    public function lockLot(int $product, int $lot): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Los bloqueos de inventario requieren una transacción.');
        }
        $p = DB::table('producto')->where('id', $product)->lockForUpdate()->first() ?? abort(404);
        $l = DB::table('lote')->where('id', $lot)->where('producto_id', $product)->lockForUpdate()->first() ?? abort(404);

        return [$p, $l];
    }

    public function ordinaryAllowed(object $product, object $lot): bool
    {
        return $product->activo && $lot->estado === 'Disponible' && ($lot->fecha_vencimiento !== null
            ? $lot->fecha_vencimiento >= $this->today() : $product->tipo === 'Insumo');
    }

    public function manual(User $user, array $data): int
    {
        Clinic::authorize($user, 'inventario.gestionar');
        [$product, $lot] = $this->lockLot((int) $data['producto_id'], (int) $data['lote_id']);
        if (in_array($data['tipo'], ['Entrada', 'Salida'], true) && ! $this->ordinaryAllowed($product, $lot)) {
            Clinic::fail('El movimiento ordinario requiere producto activo y lote disponible y vigente. Para corregir el saldo físico utiliza un ajuste autorizado.');
        }
        // La consulta se ejecuta DESPUÉS de los bloqueos; no bloqueamos solo movimientos existentes.
        $balance = $this->balance($lot->id);
        if (in_array($data['tipo'], ['Salida', 'AjusteSalida'], true) && (int) $data['cantidad'] > $balance) {
            Clinic::fail('Existencias insuficientes. Saldo físico disponible para la salida: '.$balance.' unidades.');
        }

        return DB::table('movimiento_inventario')->insertGetId(['producto_id' => $product->id, 'lote_id' => $lot->id,
            'usuario_id' => $user->id, 'tipo' => $data['tipo'], 'cantidad' => (int) $data['cantidad'], 'motivo' => trim($data['motivo'])]);
    }
}
