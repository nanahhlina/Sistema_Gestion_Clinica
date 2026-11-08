<?php

namespace App\Modules\GestionFinanciera\Controllers;

use App\Modules\GestionFinanciera\Services\PagoService;
use App\Modules\GestionFinanciera\Services\PayPalService;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PayPalController extends FlujoController
{
    private function customerPayment(Request $request, int $id): object
    {
        $payment = DB::table('pago')->where('id', $id)->where('metodo', 'PayPal')->first() ?? abort(404);
        if ($request->filled('token')) {
            $order = DB::table('transaccion_paypal')->where('pago_id', $id)->value('paypal_order_id');
            abort_unless(is_string($request->query('token')) && is_string($order) && hash_equals($order, $request->query('token')), 403);
        }

        return $payment;
    }

    private function customerUrl(string $route, Request $request, int $id): string
    {
        return URL::temporarySignedRoute($route, CarbonImmutable::createFromTimestamp((int) $request->query('expires')), ['id' => $id]);
    }

    public function customerReturn(Request $request, int $id)
    {
        abort_unless(URL::hasValidSignature($request, true, ['token', 'PayerID']), 403);
        $row = $this->customerPayment($request, $id);
        if ($row->estado === 'Completado') {
            return redirect()->to($this->customerUrl('finance.paypal.customer.receipt', $request, $id));
        }

        return view('finanzas.cliente-paypal', ['row' => $row, 'verifyUrl' => $this->customerUrl('finance.paypal.customer.verify', $request, $id), 'autoVerify' => $row->estado === 'Pendiente', 'cancelled' => false]);
    }

    public function customerCancel(Request $request, int $id)
    {
        abort_unless(URL::hasValidSignature($request, true, ['token', 'PayerID']), 403);
        $row = $this->customerPayment($request, $id);

        return view('finanzas.cliente-paypal', ['row' => $row, 'verifyUrl' => $this->customerUrl('finance.paypal.customer.verify', $request, $id), 'autoVerify' => false, 'cancelled' => true]);
    }

    public function customerVerify(Request $request, int $id, PayPalService $paypal)
    {
        $row = $this->customerPayment($request, $id);
        if ($row->estado === 'Pendiente') {
            $paypal->process($id, null, false, true);
        }

        return redirect()->to($this->customerUrl('finance.paypal.customer.receipt', $request, $id));
    }

    public function customerReceipt(Request $request, int $id)
    {
        $row = $this->customerPayment($request, $id);

        return view('finanzas.cliente-paypal', ['row' => $row, 'verifyUrl' => $this->customerUrl('finance.paypal.customer.verify', $request, $id), 'autoVerify' => false, 'cancelled' => false]);
    }

    public function process(Request $request, int $id, PagoService $payments, PayPalService $paypal)
    {
        $p = DB::table('pago')->find($id) ?? abort(404);
        $payments->authorize($request->user(), $p);

        return redirect()->route('finance.payments.show', $id)->with('warning', $paypal->process($id, $request->user()));
    }

    public function cancel(Request $request, int $id, PagoService $payments, PayPalService $paypal)
    {
        $p = DB::table('pago')->find($id) ?? abort(404);
        $payments->authorize($request->user(), $p);

        return redirect()->route('finance.payments.show', $id)->with('warning', $paypal->process($id, $request->user(), true));
    }

    public function returned(Request $request, int $id, PagoService $payments)
    {
        $p = DB::table('pago')->find($id) ?? abort(404);
        $payments->authorize($request->user(), $p);

        return redirect()->route('finance.payments.show', $id)->with('warning', 'El retorno no confirma un pago. Usa verificar o espera la conciliación del servidor.');
    }

    public function reconcileForm(Request $request, int $id, PagoService $payments)
    {
        $payments->requireRefundAdmin($request->user());
        abort_unless(DB::table('pago')->where('id', $id)->where('metodo', 'PayPal')->exists(), 404);

        return $this->form($request, 'Conciliar identificador externo conocido', route('finance.paypal.associate', $id), [
            'order_id' => ['label' => 'Order ID de Sandbox (si falta la orden local)', 'max' => 100],
            'refund_id' => ['label' => 'Refund ID de Sandbox (si falta el reembolso local)', 'max' => 100],
        ], [], 'conciliar.'.$id, ['message' => 'Solo para una respuesta perdida fuera de la ventana automática. Consulta el identificador en la cuenta Sandbox autorizada; el servidor verificará destino, referencia e importe antes de vincularlo. No registra un cobro nuevo.']);
    }

    public function associate(Request $request, int $id, PagoService $payments, PayPalService $paypal)
    {
        $payments->requireRefundAdmin($request->user());
        $data = $this->validated($request, ['order_id' => 'nullable|required_without:refund_id|string|max:100|regex:/^[A-Za-z0-9-]+$/D', 'refund_id' => 'nullable|required_without:order_id|string|max:100|regex:/^[A-Za-z0-9-]+$/D', 'paso' => 'required|in:revisar,guardar', 'clave' => 'required|uuid', 'firma' => 'required|string']);
        if ($data['paso'] === 'revisar') {
            unset($data['paso'],$data['clave'],$data['firma']);

            return $this->review($request, 'Confirmar verificación de identificadores', url()->current(), $data, ['Order ID' => $data['order_id'] ?? 'Sin cambio', 'Refund ID' => $data['refund_id'] ?? 'Sin cambio'], 'conciliar.'.$id);
        }
        abort_unless(hash_equals(OperacionWeb::signature($request->user(), 'conciliar.'.$id, $data['clave']), $data['firma']), 422);

        return redirect()->route('finance.payments.show', $id)->with('warning', $paypal->associate($request->user(), $id, $data['order_id'] ?? null, $data['refund_id'] ?? null));
    }
}
