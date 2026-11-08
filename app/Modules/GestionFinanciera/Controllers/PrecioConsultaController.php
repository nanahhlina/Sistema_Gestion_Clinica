<?php

namespace App\Modules\GestionFinanciera\Controllers;

use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrecioConsultaController extends FlujoController
{
    private function authorizePrice(Request $request): void
    {
        Clinic::authorize($request->user(), 'pagos.registrar');
        abort_if($request->user()->isPatient() || $request->user()->isDoctor(), 403);
    }

    private function version(object $row): string
    {
        return hash('sha256', json_encode([$row->precio_consulta, $row->estado]));
    }

    public function edit(Request $request, int $id)
    {
        $this->authorizePrice($request);
        $row = DB::table('cita')->where('id', $id)->first() ?? abort(404);

        return $this->form($request, 'Definir precio de consulta', route('appointments.price.save', $id), [
            'precio_consulta' => ['label' => 'Precio de consulta (BOB; cero significa sin cargo)', 'required' => true, 'max' => 13],
            'version' => ['type' => 'hidden'],
        ], ['precio_consulta' => $row->precio_consulta, 'version' => $this->version($row)], 'precio.'.$id);
    }

    public function save(Request $request, int $id)
    {
        $this->authorizePrice($request);
        $data = $this->validated($request, ['precio_consulta' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D'], 'version' => 'required|string|size:64', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $row = DB::table('cita')->where('id', $id)->first() ?? abort(404);
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Confirmar precio', route('appointments.price.save', $id), $data, ['Cita' => $row->codigo, 'Precio BOB' => Importe::decimal($data['precio_consulta'])], 'precio.'.$id);
        }
        OperacionWeb::run($request, 'precio.'.$id, function () use ($request, $id, $data) {
            $row = DB::table('cita')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if (! hash_equals($this->version($row), $data['version'])) {
                Clinic::fail('La cita cambió. Consulta el precio nuevamente.');
            }
            if (! in_array($row->estado, ['Programada', 'Confirmada', 'Atendida'], true)
                || DB::table('pago')->where('cita_id', $id)->whereIn('estado', ['Pendiente', 'Completado'])->exists()) {
                Clinic::fail('No se puede modificar el precio de una cita finalizada o con un cobro pendiente o completado.');
            }
            DB::table('cita')->where('id', $id)->update(['precio_consulta' => Importe::decimal($data['precio_consulta']), 'modificada_por' => $request->user()->id]);

            return $id;
        });

        return redirect()->route('appointments.show', $id)->with('success', 'Precio de consulta actualizado.');
    }
}
