<?php

namespace App\Modules\GestionAbastecimiento\Controllers;

use App\Modules\GestionAbastecimiento\Services\InventarioService;
use App\Modules\GestionAbastecimiento\Services\LoteService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoteController extends FlujoController
{
    public function index(Request $request, InventarioService $inventory)
    {
        Clinic::authorize($request->user(), 'lotes.gestionar');
        $filters = $this->validated($request, ['q' => 'nullable|string|max:150', 'producto_id' => 'nullable|integer|min:1',
            'estado' => ['nullable', Rule::in(['Disponible', 'Bloqueado'])], 'vigencia' => ['nullable', Rule::in(['vigente', 'vencido', 'sin_fecha'])], 'page' => 'nullable|integer|min:1']);
        $query = $inventory->lots()->when(! empty($filters['q']), fn ($q) => $q->where(fn ($q) => $q->where('p.nombre', 'ilike', '%'.$filters['q'].'%')->orWhere('l.numero_lote', 'ilike', '%'.$filters['q'].'%')))
            ->when(! empty($filters['producto_id']), fn ($q) => $q->where('l.producto_id', $filters['producto_id']))
            ->when(! empty($filters['estado']), fn ($q) => $q->where('l.estado', $filters['estado']));
        match ($filters['vigencia'] ?? '') {
            'vigente' => $query->where(fn ($q) => $q->where('l.fecha_vencimiento', '>=', $inventory->today())->orWhere(fn ($q) => $q->whereNull('l.fecha_vencimiento')->where('p.tipo', 'Insumo'))),
            'vencido' => $query->where('l.fecha_vencimiento', '<', $inventory->today()),
            'sin_fecha' => $query->whereNull('l.fecha_vencimiento'),
            default => null,
        };
        $rows = $query->orderBy('p.nombre')->orderBy('l.numero_lote')->orderBy('l.id')->paginate(20)->withQueryString();

        return view('abastecimiento.lotes', compact('rows', 'filters') + ['today' => $inventory->today()]);
    }

    public function edit(Request $request, LoteService $service, ?int $id = null)
    {
        Clinic::authorize($request->user(), 'lotes.gestionar');
        $lot = $id ? DB::table('lote')->find($id) ?? abort(404) : null;
        $this->validated($request, ['producto_id' => 'nullable|integer|min:1']);

        return $this->form($request, $id ? 'Editar lote' : 'Registrar lote', route('supply.lots.save', $id ? ['id' => $id] : []), [
            'producto_id' => ['label' => 'Producto', 'options' => $service->choices($lot->producto_id ?? null), 'required' => true],
            'numero_lote' => ['label' => 'Número de lote', 'required' => true, 'max' => 80],
            'fecha_vencimiento' => ['label' => 'Fecha de vencimiento (obligatoria en medicamentos)', 'type' => 'date'],
            'estado' => ['label' => 'Estado', 'options' => ['Disponible' => 'Disponible', 'Bloqueado' => 'Bloqueado'], 'required' => true],
            'motivo' => ['label' => 'Motivo del registro o cambio', 'type' => 'textarea', 'required' => true, 'max' => 1000],
            'version' => ['type' => 'hidden'],
        ], ($lot ? (array) $lot : ['producto_id' => $request->input('producto_id'), 'estado' => 'Disponible']) + ['version' => $lot ? $service->version($lot) : 'nuevo'], 'lote.'.($id ?? 'nuevo'),
            ['message' => 'Registrar un lote no añade existencias. Después de su primer movimiento, solo podrás cambiar su estado. Los lotes vencidos se conservan para trazabilidad y no admiten movimientos ordinarios.']);
    }

    public function save(Request $request, LoteService $service, ?int $id = null)
    {
        Clinic::authorize($request->user(), 'lotes.gestionar');
        if ($id) {
            abort_unless(DB::table('lote')->where('id', $id)->exists(), 404);
        }
        $data = $this->validated($request, ['producto_id' => 'required|integer|min:1', 'numero_lote' => ['required', 'string', 'max:80', 'regex:/\S/u'],
            'fecha_vencimiento' => 'nullable|date_format:Y-m-d', 'estado' => ['required', Rule::in(['Disponible', 'Bloqueado'])],
            'motivo' => ['required', 'string', 'max:1000', 'regex:/\S/u'], 'version' => 'required|string|max:64', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $product = DB::table('producto')->find($data['producto_id']) ?? abort(404);
        if ($product->tipo === 'Medicamento' && empty($data['fecha_vencimiento'])) {
            Clinic::fail('Los medicamentos requieren fecha de vencimiento.', 'fecha_vencimiento');
        }
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar lote', url()->current(), $data, ['Producto' => $product->nombre, 'Número' => mb_strtoupper(trim($data['numero_lote'])),
                'Vencimiento' => $data['fecha_vencimiento'] ?? 'Sin fecha (insumo)', 'Estado' => $data['estado'], 'Motivo' => $data['motivo']], 'lote.'.($id ?? 'nuevo'));
        }
        OperacionWeb::run($request, 'lote.'.($id ?? 'nuevo'), fn () => $service->save($request->user(), $id, $data));

        return redirect()->route('supply.lots.index', ['producto_id' => $data['producto_id']])->with('success', 'Lote guardado. Las existencias no han cambiado.');
    }
}
