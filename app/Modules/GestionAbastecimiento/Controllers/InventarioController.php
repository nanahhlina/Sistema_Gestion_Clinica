<?php

namespace App\Modules\GestionAbastecimiento\Controllers;

use App\Modules\GestionAbastecimiento\Services\InventarioService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarioController extends FlujoController
{
    public function index(Request $request, InventarioService $service)
    {
        Clinic::authorize($request->user(), 'inventario.consultar');
        $filters = $this->validated($request, ['q' => 'nullable|string|max:150', 'tipo' => ['nullable', Rule::in(['Medicamento', 'Insumo'])], 'activo' => ['nullable', Rule::in(['0', '1'])], 'page' => 'nullable|integer|min:1']);
        $rows = $service->products()->when(! empty($filters['q']), fn ($q) => $q->where('p.nombre', 'ilike', '%'.$filters['q'].'%'))
            ->when(! empty($filters['tipo']), fn ($q) => $q->where('p.tipo', $filters['tipo']))
            ->when(isset($filters['activo']), fn ($q) => $q->where('p.activo', (bool) $filters['activo']))->orderBy('p.nombre')->orderBy('p.id')->paginate(20)->withQueryString();

        return view('abastecimiento.inventario', compact('rows', 'filters'));
    }

    public function kardex(Request $request, InventarioService $service)
    {
        Clinic::authorize($request->user(), 'inventario.consultar');
        $filters = $this->validated($request, ['producto_id' => 'nullable|integer|min:1', 'lote_id' => 'nullable|integer|min:1',
            'desde' => 'nullable|date_format:Y-m-d', 'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'tipo' => ['nullable', Rule::in(array_keys(InventarioService::TYPES))], 'page' => 'nullable|integer|min:1']);
        $query = $service->ledger()->when(! empty($filters['producto_id']), fn ($q) => $q->where('k.producto_id', $filters['producto_id']))
            ->when(! empty($filters['lote_id']), fn ($q) => $q->where('k.lote_id', $filters['lote_id']))
            ->when(! empty($filters['tipo']), fn ($q) => $q->where('k.tipo', $filters['tipo']))
            ->when(! empty($filters['desde']), fn ($q) => $q->where('k.fecha', '>=', Clinic::date($filters['desde'])->startOfDay()))
            ->when(! empty($filters['hasta']), fn ($q) => $q->where('k.fecha', '<', Clinic::date($filters['hasta'])->addDay()->startOfDay()));
        $rows = $query->orderByDesc('k.fecha')->orderByDesc('k.id')->paginate(20)->withQueryString();

        return view('abastecimiento.kardex', compact('rows', 'filters'));
    }

    public function show(Request $request, int $product, InventarioService $service)
    {
        Clinic::authorize($request->user(), 'inventario.consultar');
        $this->validated($request, ['page' => 'nullable|integer|min:1']);
        $p = DB::table('producto')->find($product) ?? abort(404);
        $rows = $service->lots()->where('p.id', $product)->orderBy('l.numero_lote')->orderBy('l.id')->paginate(20)->withQueryString();

        return view('abastecimiento.lotes', ['rows' => $rows, 'filters' => ['producto_id' => $product], 'today' => $service->today(), 'product' => $p]);
    }

    public function create(Request $request, int $lot, InventarioService $service)
    {
        Clinic::authorize($request->user(), 'inventario.gestionar');
        $row = $service->lots()->where('l.id', $lot)->first() ?? abort(404);

        return $this->form($request, 'Registrar movimiento de inventario', route('supply.movements.store', $lot), [
            'producto_id' => ['type' => 'hidden'], 'tipo' => ['label' => 'Tipo de movimiento', 'options' => InventarioService::TYPES, 'required' => true],
            'cantidad' => ['label' => 'Cantidad de unidades enteras', 'required' => true, 'max' => 10],
            'motivo' => ['label' => 'Motivo (obligatorio)', 'type' => 'textarea', 'required' => true, 'max' => 1000],
        ], ['producto_id' => $row->producto_id], 'movimiento.'.$lot, ['message' => $row->producto.' · Lote '.$row->numero_lote.' · Saldo físico: '.$row->saldo.' '.$row->unidad_medida.'. Los ajustes permiten corregir saldos físicos de lotes bloqueados/vencidos o productos inactivos; no los habilitan para venta.']);
    }

    public function store(Request $request, int $lot, InventarioService $service)
    {
        Clinic::authorize($request->user(), 'inventario.gestionar');
        $data = $this->validated($request, ['producto_id' => 'required|integer|min:1', 'tipo' => ['required', Rule::in(array_keys(InventarioService::TYPES))],
            'cantidad' => 'required|integer|min:1|max:2147483647', 'motivo' => ['required', 'string', 'max:1000', 'regex:/\S/u'],
            'usuario_id' => 'prohibited', 'fecha' => 'prohibited', 'detalle_compra_id' => 'prohibited', 'detalle_venta_lote_id' => 'prohibited',
            'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $row = $service->lots()->where('l.id', $lot)->where('l.producto_id', $data['producto_id'])->first() ?? abort(404);
        $data['lote_id'] = $lot;
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar movimiento', url()->current(), $data, ['Producto' => $row->producto, 'Lote' => $row->numero_lote,
                'Tipo' => InventarioService::TYPES[$data['tipo']], 'Cantidad' => $data['cantidad'].' '.$row->unidad_medida, 'Motivo' => $data['motivo']], 'movimiento.'.$lot);
        }
        OperacionWeb::run($request, 'movimiento.'.$lot, fn () => $service->manual($request->user(), $data));

        return redirect()->route('supply.kardex', ['lote_id' => $lot])->with('success', 'Movimiento registrado. El historial se conserva sin modificaciones.');
    }
}
