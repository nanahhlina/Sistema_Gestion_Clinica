<?php

namespace App\Modules\GestionAbastecimiento\Controllers;

use App\Modules\GestionAbastecimiento\Services\CompraService;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompraController extends FlujoController
{
    public function index(Request $request)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');
        $filters = $this->validated($request, ['q' => 'nullable|string|max:150', 'estado' => ['nullable', Rule::in(['Registrada', 'Parcial', 'Recibida', 'Cancelada'])], 'page' => 'nullable|integer|min:1']);
        $rows = DB::table('compra as c')->join('proveedor as p', 'p.id', '=', 'c.proveedor_id')->select('c.*', 'p.nombre as proveedor')
            ->when(! empty($filters['q']), fn ($q) => $q->where('p.nombre', 'ilike', '%'.$filters['q'].'%'))->when(! empty($filters['estado']), fn ($q) => $q->where('c.estado', $filters['estado']))->orderByDesc('c.id')->paginate(20)->withQueryString();

        return view('abastecimiento.compras', compact('rows', 'filters'));
    }

    public function create(Request $request)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');

        return $this->entry($request);
    }

    private function entry(Request $request)
    {
        $lineas = array_slice((array) $request->input('lineas', []), 0, 49);
        $lineas[] = [];
        $options = DB::table('producto')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all();
        $suppliers = DB::table('proveedor')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all();

        return view('abastecimiento.documento-form', ['title' => 'Registrar compra', 'action' => route('supply.purchases.store'), 'operation' => 'compra', 'lines' => $lineas, 'options' => $options, 'suppliers' => $suppliers, 'values' => $request->only('proveedor_id'), 'purchase' => true, 'key' => $request->input('clave') ?: OperacionWeb::token()]);
    }

    public function store(Request $request, CompraService $service)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');
        if ($request->input('paso') === 'agregar') {
            $this->validated($request, ['proveedor_id' => 'nullable|integer|min:1', 'lineas' => 'nullable|array|max:49', 'lineas.*' => 'array', 'lineas.*.producto_id' => 'nullable|integer|min:1', 'lineas.*.cantidad' => 'nullable|integer|min:1|max:2147483647', 'lineas.*.precio_unitario' => 'nullable|string|max:13']);

            return $this->entry($request);
        }
        $input = array_values(array_filter((array) $request->input('lineas', []), fn ($l) => is_array($l) && ! empty($l['producto_id'])));
        $request->merge(['lineas' => $input]);
        $data = $this->validated($request, ['proveedor_id' => 'required|integer|min:1', 'lineas' => 'required|array|min:1|max:50', 'lineas.*.producto_id' => 'required|integer|min:1', 'lineas.*.cantidad' => 'required|integer|min:1|max:2147483647',
            'lineas.*.precio_unitario' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'], 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $lines = $service->lines($data['lineas']);
        if ($data['paso'] === 'revisar') {
            $provider = DB::table('proveedor')->where('id', $data['proveedor_id'])->where('activo', true)->first() ?? abort(422, 'Selecciona proveedor activo.');
            unset($data['paso']);

            return $this->review($request, 'Confirmar compra', url()->current(), $data, ['Proveedor' => $provider->nombre, 'Productos' => collect($lines)->map(fn ($l) => $l['nombre'].' × '.$l['cantidad'].' a '.$l['precio_unitario'].' BOB')->join("\n"), 'Total BOB' => Importe::total($lines)], 'compra');
        }
        $id = OperacionWeb::run($request, 'compra', fn () => $service->create($request->user(), $data));

        return redirect()->route('supply.purchases.show', $id)->with('success', 'Compra registrada.');
    }

    public function show(Request $request, int $id, CompraService $service)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');
        $row = DB::table('compra as c')->join('proveedor as p', 'p.id', '=', 'c.proveedor_id')->where('c.id', $id)->select('c.*', 'p.nombre as proveedor')->first() ?? abort(404);
        $details = $service->details($id);
        $lines = array_slice((array) $request->input('lineas', []), 0, 49);
        $lines[] = [];

        return view('abastecimiento.compra', compact('row', 'details', 'lines') + ['key' => OperacionWeb::token()]);
    }

    public function receive(Request $request, int $id, CompraService $service)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');
        if ($request->input('paso') === 'agregar') {
            $this->validated($request, ['lineas' => 'nullable|array|max:49', 'lineas.*' => 'array', 'lineas.*.detalle_id' => 'nullable|integer|min:1', 'lineas.*.cantidad' => 'nullable|integer|min:1|max:2147483647', 'lineas.*.numero_lote' => 'nullable|string|max:80', 'lineas.*.fecha_vencimiento' => 'nullable|date_format:Y-m-d']);

            return $this->show($request, $id, $service);
        }
        $request->merge(['lineas' => array_values(array_filter((array) $request->input('lineas', []), fn ($l) => is_array($l) && ! empty($l['detalle_id'])))]);
        $data = $this->validated($request, ['lineas' => 'required|array|min:1|max:50', 'lineas.*.detalle_id' => 'required|integer|min:1', 'lineas.*.cantidad' => 'required|integer|min:1|max:2147483647',
            'lineas.*.numero_lote' => ['required', 'string', 'max:80', 'regex:/\S/u'], 'lineas.*.fecha_vencimiento' => 'nullable|date_format:Y-m-d', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        abort_unless(DB::table('compra')->where('id', $id)->exists(), 404);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar recepción', url()->current(), $data, ['Compra' => '#'.$id, 'Recepción' => collect($data['lineas'])->map(fn ($l) => 'Detalle #'.$l['detalle_id'].' · '.$l['cantidad'].' unidades · lote '.$l['numero_lote'].' · '.($l['fecha_vencimiento'] ?? 'Sin vencimiento'))->join("\n")], 'recepcion_compra.'.$id);
        }
        OperacionWeb::run($request, 'recepcion_compra.'.$id, fn () => $service->receive($request->user(), $id, $data['lineas']));

        return redirect()->route('supply.purchases.show', $id)->with('success', 'Recepción registrada.');
    }

    public function cancel(Request $request, int $id)
    {
        Clinic::authorize($request->user(), 'compras.gestionar');
        OperacionWeb::run($request, 'cancelar_compra.'.$id, function () use ($id) {
            $row = DB::table('compra')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if ($row->estado !== 'Registrada' || DB::table('movimiento_inventario as m')->join('detalle_compra as d', 'd.id', '=', 'm.detalle_compra_id')->where('d.compra_id', $id)->exists()) {
                Clinic::fail('Solo se cancela una compra sin recepciones.');
            }
            DB::table('compra')->where('id', $id)->update(['estado' => 'Cancelada']);

            return $id;
        });

        return redirect()->route('supply.purchases.show', $id)->with('success', 'Compra cancelada.');
    }
}
