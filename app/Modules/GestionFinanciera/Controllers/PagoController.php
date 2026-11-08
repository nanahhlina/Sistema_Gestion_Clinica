<?php

namespace App\Modules\GestionFinanciera\Controllers;

use App\Modules\GestionAbastecimiento\Services\VentaService;
use App\Modules\GestionFinanciera\Services\ConfiguracionFinancieraService;
use App\Modules\GestionFinanciera\Services\PagoService;
use App\Modules\GestionFinanciera\Services\PayPalService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagoController extends FlujoController
{
    public function index(Request $request)
    {
        Clinic::authorize($request->user(), 'pagos.registrar');
        $filters = $this->validated($request, ['q' => 'nullable|string|max:100', 'fecha' => 'nullable|date_format:Y-m-d', 'page' => 'nullable|integer|min:1']);
        $rows = DB::table('cita as c')->join('paciente as p', 'p.id', '=', 'c.paciente_id')->select('c.id', 'c.codigo', 'c.inicio', 'c.estado', 'c.precio_consulta')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente")
            ->when(! empty($filters['q']), fn ($q) => $q->whereRaw("concat_ws(' ',c.codigo,p.nombres,p.apellidos,p.ci) ILIKE ?", ['%'.$filters['q'].'%']))
            ->when(! empty($filters['fecha']), fn ($q) => $q->where('c.inicio', '>=', Clinic::date($filters['fecha'])->startOfDay())->where('c.inicio', '<', Clinic::date($filters['fecha'])->addDay()->startOfDay()))->orderByDesc('c.inicio')->orderByDesc('c.id')->paginate(20)->withQueryString();

        return view('finanzas.cobros', compact('rows', 'filters'));
    }

    public function create(Request $request, int $id)
    {
        Clinic::authorize($request->user(), 'pagos.registrar');
        $row = DB::table('cita')->find($id) ?? abort(404);
        $payments = DB::table('pago')->where('cita_id', $id)->get();

        return $this->form($request, 'Registrar pago de consulta', route('finance.payments.store', $id), [
            'metodo' => ['label' => 'Método', 'options' => ['Efectivo' => 'Efectivo', 'PayPal' => 'PayPal Sandbox'], 'required' => true],
            'recibido' => ['label' => 'Efectivo recibido BOB (solo efectivo)'], 'precio_esperado' => ['type' => 'hidden'],
        ], ['metodo' => 'Efectivo', 'precio_esperado' => $row->precio_consulta ?? 'sin_precio'], 'pago_cita.'.$id, ['message' => 'Cita '.$row->codigo.' · Precio BOB: '.($row->precio_consulta ?? 'Sin definir').' · Pagos registrados: '.$payments->map(fn ($p) => '#'.$p->id.' '.$p->estado.' '.$p->monto.' BOB')->join('; ')]);
    }

    public function store(Request $request, int $id, PagoService $service)
    {
        Clinic::authorize($request->user(), 'pagos.registrar');
        $data = $this->validated($request, ['metodo' => ['required', Rule::in(['Efectivo', 'PayPal'])], 'recibido' => ['nullable', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'],
            'precio_esperado' => 'required|string|max:30', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        if ($data['metodo'] === 'PayPal' && $data['paso'] === 'guardar') {
            $data += $this->validated($request, ['tasa_esperada' => 'required|string|max:19', 'usd_esperado' => 'required|string|max:13']);
        }
        $row = DB::table('cita')->find($id) ?? abort(404);
        if ($data['paso'] === 'revisar') {
            if ($row->precio_consulta === null) {
                Clinic::fail('Debe definirse el precio antes de registrar el pago.');
            }unset($data['paso']);
            $summary = ['Cita' => $row->codigo, 'Total BOB' => $row->precio_consulta, 'Método' => $data['metodo'], 'Recibido' => $data['recibido'] ?? '—'];
            if ($data['metodo'] === 'PayPal') {
                $quote = app(ConfiguracionFinancieraService::class)->quote($row->precio_consulta);
                $data += ['tasa_esperada' => $quote['bob_por_usd'], 'usd_esperado' => $quote['monto_pasarela']];
                $summary += ['BOB por USD' => $quote['bob_por_usd'], 'Total USD' => $quote['monto_pasarela']];
            }

            return $this->review($request, 'Confirmar pago de consulta', url()->current(), $data, $summary, 'pago_cita.'.$id);
        }
        $payment = OperacionWeb::run($request, 'pago_cita.'.$id, function () use ($request, $id, $data, $service) {
            $row = DB::table('cita')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if ($row->precio_consulta !== $data['precio_esperado']) {
                Clinic::fail('El precio cambió o no está definido. Revisa nuevamente el pago.');
            }

            return $service->create($request->user(), $id, null, $data['metodo'], $data['recibido'] ?? null, $data['tasa_esperada'] ?? null, $data['usd_esperado'] ?? null);
        });

        return redirect()->route('finance.payments.show', $payment)->with('success', 'Pago registrado.');
    }

    public function show(Request $request, int $id, PagoService $service)
    {
        $row = DB::table('pago')->find($id) ?? abort(404);
        $service->authorize($request->user(), $row);
        $transaction = DB::table('transaccion_paypal')->where('pago_id', $id)->first();
        $destination = $row->cita_id
            ? DB::table('cita as c')->join('paciente as p', 'p.id', '=', 'c.paciente_id')->join('medico as m', 'm.id', '=', 'c.medico_id')->join('usuario as u', 'u.id', '=', 'm.usuario_id')->where('c.id', $row->cita_id)->select('c.codigo', 'c.inicio')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente, concat_ws(' ',u.nombres,u.apellidos) as medico")->first()
            : DB::table('venta as v')->join('paciente as p', 'p.id', '=', 'v.paciente_id')->where('v.id', $row->venta_id)->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente")->first();

        return view('finanzas.pago', compact('row', 'transaction', 'destination'));
    }

    public function refundForm(Request $request, int $id, PagoService $service)
    {
        $service->requireRefundAdmin($request->user());
        $p = DB::table('pago')->find($id) ?? abort(404);

        return $this->form($request, 'Devolución completa de pago', route('finance.payments.refund', $id), ['motivo' => ['label' => 'Motivo de devolución', 'type' => 'textarea', 'required' => true, 'max' => 1000]], [], 'devolucion.'.$id,
            ['message' => 'Pago #'.$id.' · '.$p->monto.' BOB · '.$p->metodo.'. En efectivo, confirma únicamente después de entregar físicamente el importe. El pago original se conserva.']);
    }

    public function refund(Request $request, int $id, PagoService $service)
    {
        $service->requireRefundAdmin($request->user());
        $data = $this->validated($request, ['motivo' => ['required', 'string', 'max:1000', 'regex:/\S/u'], 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $p = DB::table('pago')->find($id) ?? abort(404);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar devolución completa', url()->current(), $data, ['Pago' => '#'.$id, 'Total BOB' => $p->monto, 'Método' => $p->metodo, 'Motivo' => $data['motivo']], 'devolucion.'.$id);
        }
        if ($p->metodo === 'Efectivo') {
            OperacionWeb::run($request, 'devolucion.'.$id, fn () => $service->cashRefund($request->user(), $id, $data['motivo']));
        } else {
            $paypal = app(PayPalService::class);
            OperacionWeb::run($request, 'devolucion.'.$id, fn () => $paypal->prepareRefund($request->user(), $id, $data['motivo']));
            $message = $paypal->process($id, $request->user());

            return redirect()->route('finance.payments.show', $id)->with('warning', $message);
        }

        return redirect()->route('finance.payments.show', $id)->with('success', 'Devolución registrada o enviada a verificación.');
    }

    public function saleForm(Request $request, int $id)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        $sale = DB::table('venta')->find($id) ?? abort(404);

        return $this->form($request, 'Reintentar pago de venta', route('finance.sale.payment.store', $id), ['metodo' => ['label' => 'Método', 'options' => ['Efectivo' => 'Efectivo', 'PayPal' => 'PayPal Sandbox'], 'required' => true], 'recibido' => ['label' => 'Recibido en efectivo BOB']], ['metodo' => 'Efectivo'], 'pago_venta.'.$id, ['message' => 'Venta #'.$id.' · Total '.$sale->total.' BOB. Solo después de resolver el intento anterior.']);
    }

    public function saleStore(Request $request, int $id, PagoService $service)
    {
        Clinic::authorize($request->user(), 'ventas.gestionar');
        $data = $this->validated($request, ['metodo' => ['required', Rule::in(['Efectivo', 'PayPal'])], 'recibido' => 'nullable|string|max:13', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $sale = DB::table('venta')->find($id) ?? abort(404);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);
            $summary = ['Venta' => '#'.$id, 'Total BOB' => $sale->total, 'Método' => $data['metodo']];
            if ($data['metodo'] === 'PayPal') {
                $q = app(ConfiguracionFinancieraService::class)->quote($sale->total);
                $data += ['tasa_esperada' => $q['bob_por_usd'], 'usd_esperado' => $q['monto_pasarela']];
                $summary += ['BOB por USD' => $q['bob_por_usd'], 'USD' => $q['monto_pasarela']];
            }

            return $this->review($request, 'Confirmar nuevo intento', url()->current(), $data, $summary, 'pago_venta.'.$id);
        }
        if ($data['metodo'] === 'PayPal') {
            $data += $this->validated($request, ['tasa_esperada' => 'required|string|max:19', 'usd_esperado' => 'required|string|max:13']);
        }
        $p = OperacionWeb::run($request, 'pago_venta.'.$id, function () use ($service, $request, $id, $data) {
            $p = $service->create($request->user(), null, $id, $data['metodo'], $data['recibido'] ?? null, $data['tasa_esperada'] ?? null, $data['usd_esperado'] ?? null);
            if ($data['metodo'] === 'Efectivo') {
                app(VentaService::class)->deliver($id);
            }

            return $p;
        });

        return redirect()->route('finance.payments.show', $p);
    }
}
