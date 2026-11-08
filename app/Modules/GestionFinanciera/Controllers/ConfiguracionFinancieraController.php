<?php

namespace App\Modules\GestionFinanciera\Controllers;

use App\Modules\GestionFinanciera\Services\ConfiguracionFinancieraService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConfiguracionFinancieraController extends FlujoController
{
    public function index(Request $request)
    {
        Clinic::authorize($request->user(), 'finanzas.configurar');
        $this->validated($request, ['page' => 'nullable|integer|min:1']);
        $current = DB::table('configuracion_financiera')->where('id', 1)->first();
        $history = DB::table('bitacora as b')->leftJoin('usuario as u', 'u.id', '=', 'b.usuario_id')
            ->where('b.entidad', 'configuracion_financiera')->where('b.accion', 'TIPO_CAMBIO')
            ->select('b.*')->selectRaw("concat_ws(' ',u.nombres,u.apellidos) as responsable")
            ->orderByDesc('b.id')->paginate(20)->withQueryString();

        return view('finanzas.configuracion', compact('current', 'history'));
    }

    public function edit(Request $request)
    {
        Clinic::authorize($request->user(), 'finanzas.configurar');
        $current = DB::table('configuracion_financiera')->where('id', 1)->first();

        return $this->form($request, 'Configurar tipo de cambio', route('finance.configuration.save'), [
            'bob_por_usd' => ['label' => 'Bolivianos por USD (hasta ocho decimales)', 'required' => true, 'max' => 19],
            'motivo' => ['label' => 'Motivo del cambio', 'type' => 'textarea', 'required' => true, 'max' => 1000],
            'version' => ['type' => 'hidden'],
        ], ['bob_por_usd' => $current->bob_por_usd ?? '', 'version' => $current->version ?? 0], 'tipo_cambio');
    }

    public function save(Request $request, ConfiguracionFinancieraService $service)
    {
        Clinic::authorize($request->user(), 'finanzas.configurar');
        $data = $this->validated($request, ['bob_por_usd' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,8})?$/D', 'not_regex:/^0(?:\.0+)?$/D'],
            'motivo' => ['required', 'string', 'max:1000', 'regex:/\S/u'], 'version' => 'required|integer|min:0', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar tipo de cambio', route('finance.configuration.save'), $data, ['BOB por USD' => $data['bob_por_usd'], 'Motivo' => $data['motivo']], 'tipo_cambio');
        }
        OperacionWeb::run($request, 'tipo_cambio', fn () => $service->save($request->user(), $data));

        return redirect()->route('finance.configuration')->with('success', 'Tipo de cambio actualizado. Las cotizaciones anteriores se conservan.');
    }
}
