<?php

namespace App\Modules\GestionAbastecimiento\Controllers;

use App\Modules\GestionAbastecimiento\Services\VentaService;
use App\Modules\GestionFinanciera\Services\ConfiguracionFinancieraService;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends FlujoController
{
    public function index(Request $request)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        $filters = $this->validated($request, ['q' => 'nullable|string|max:100', 'estado' => ['nullable', Rule::in(['Pendiente', 'Completada', 'PendienteResolucion', 'Cancelada'])], 'page' => 'nullable|integer|min:1']);
        $rows = DB::table('venta as v')->join('paciente as p', 'p.id', '=', 'v.paciente_id')->select('v.*')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente")
            ->when(! empty($filters['q']), fn ($q) => $q->whereRaw("concat_ws(' ',p.nombres,p.apellidos,p.ci) ILIKE ?", ['%'.$filters['q'].'%']))->when(! empty($filters['estado']), fn ($q) => $q->where('v.estado', $filters['estado']))->orderByDesc('v.id')->paginate(20)->withQueryString();

        return view('abastecimiento.ventas', compact('rows', 'filters'));
    }

    public function create(Request $request)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        $this->validated($request, ['q' => 'nullable|string|max:100']);

        return $this->entry($request);
    }

    private function entry(Request $request)
    {
        $lines = array_slice((array) $request->input('lineas', []), 0, 49);
        $lines[] = [];
        $patients = DB::table('paciente')->where('activo', true)->when($request->input('q'), fn ($q) => $q->whereRaw("concat_ws(' ',nombres,apellidos,ci) ILIKE ?", ['%'.$request->input('q').'%']))->orderBy('apellidos')->limit(200)->pluck(DB::raw("concat_ws(' ',nombres,apellidos,ci) as label"), 'id')->all();
        if ($request->input('paciente_id')) {
            $selected = DB::table('paciente')->where('id', $request->input('paciente_id'))->where('activo', true)->first();
            if ($selected) {
                $patients[$selected->id] = $selected->nombres.' '.$selected->apellidos;
            }
        }
        $options = DB::table('producto')->where('activo', true)->where('tipo', 'Medicamento')->whereNotNull('precio_venta')->orderBy('nombre')->pluck(DB::raw("nombre || ' — ' || precio_venta::text || ' BOB' as label"), 'id')->all();

        return view('abastecimiento.documento-form', ['title' => 'Registrar venta', 'action' => route('supply.sales.store'), 'operation' => 'venta', 'lines' => $lines, 'options' => $options, 'patients' => $patients, 'values' => $request->only('paciente_id', 'metodo', 'recibido'), 'purchase' => false, 'key' => $request->input('clave') ?: OperacionWeb::token()]);
    }

    public function store(Request $request, VentaService $service)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        if ($request->input('paso') === 'agregar') {
            $this->validated($request, ['q' => 'nullable|string|max:100', 'paciente_id' => 'nullable|integer|min:1', 'metodo' => ['nullable', Rule::in(['Efectivo', 'PayPal'])], 'recibido' => 'nullable|string|max:13', 'lineas' => 'nullable|array|max:49', 'lineas.*' => 'array', 'lineas.*.producto_id' => 'nullable|integer|min:1', 'lineas.*.cantidad' => 'nullable|integer|min:1|max:2147483647']);

            return $this->entry($request);
        }
        $request->merge(['lineas' => array_values(array_filter((array) $request->input('lineas', []), fn ($l) => is_array($l) && ! empty($l['producto_id'])))]);
        $data = $this->validated($request, ['paciente_id' => 'required|integer|min:1', 'lineas' => 'required|array|min:1|max:50', 'lineas.*.producto_id' => 'required|integer|min:1', 'lineas.*.cantidad' => 'required|integer|min:1|max:2147483647',
            'lineas.*.precio_esperado' => ['nullable', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'], 'metodo' => ['required', Rule::in(['Efectivo', 'PayPal'])],
            'recibido' => ['nullable', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'], 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        if ($request->input('metodo') === 'PayPal' && $request->input('paso') === 'guardar') {
            $data += $this->validated($request, ['tasa_esperada' => 'required|string|max:19', 'usd_esperado' => 'required|string|max:13']);
        }
        if ($data['paso'] === 'revisar') {
            $patient = DB::table('paciente')->where('id', $data['paciente_id'])->where('activo', true)->first() ?? abort(422, 'Selecciona paciente activo.');
            $lines = $service->lines($data['lineas']);
            $data['lineas'] = array_map(fn ($l) => array_diff_key($l, ['nombre' => true, 'precio_unitario' => true]), $lines);
            unset($data['paso']);
            $summary = ['Paciente' => $patient->nombres.' '.$patient->apellidos, 'Medicamentos' => collect($lines)->map(fn ($l) => $l['nombre'].' × '.$l['cantidad'].' a '.$l['precio_unitario'].' BOB')->join("\n"), 'Total BOB' => Importe::total($lines), 'Método' => $data['metodo'], 'Recibido' => $data['recibido'] ?? '—'];
            if ($data['metodo'] === 'PayPal') {
                $quote = app(ConfiguracionFinancieraService::class)->quote(Importe::total($lines));
                $data += ['tasa_esperada' => $quote['bob_por_usd'], 'usd_esperado' => $quote['monto_pasarela']];
                $summary += ['BOB por USD' => $quote['bob_por_usd'], 'Total USD' => $quote['monto_pasarela']];
            }

            return $this->review($request, 'Confirmar venta', url()->current(), $data, $summary, 'venta');
        }
        $id = OperacionWeb::run($request, 'venta', fn () => $service->create($request->user(), $data));

        return redirect()->route('supply.sales.show', $id)->with('success', 'Venta registrada.');
    }

    public function show(Request $request, int $id)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        $row = DB::table('venta as v')->join('paciente as p', 'p.id', '=', 'v.paciente_id')->where('v.id', $id)->select('v.*')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente")->first() ?? abort(404);
        $details = DB::table('detalle_venta as d')->join('producto as p', 'p.id', '=', 'd.producto_id')->where('d.venta_id', $id)->select('d.*', 'p.nombre')->get();
        $payments = DB::table('pago')->where('venta_id', $id)->orderByDesc('id')->get();
        $lots = DB::table('detalle_venta_lote as a')->join('detalle_venta as d', 'd.id', '=', 'a.detalle_venta_id')->join('lote as l', 'l.id', '=', 'a.lote_id')->where('d.venta_id', $id)->select('a.*', 'l.numero_lote')->get();

        return view('abastecimiento.venta', compact('row', 'details', 'payments', 'lots'));
    }

    public function retry(Request $request, int $id, VentaService $service)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        abort_unless($request->user()->roleName() === 'Administrador', 403);
        OperacionWeb::run($request, 'entregar_venta.'.$id, fn () => $service->deliver($id) ? $id : $id);

        return redirect()->route('supply.sales.show', $id)->with('success', 'Entrega completada.');
    }
}
