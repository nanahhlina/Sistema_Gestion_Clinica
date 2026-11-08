<?php

namespace App\Modules\GestionLaboratorio\Controllers;

use App\Modules\GestionLaboratorio\Services\InformePrivado;
use App\Modules\GestionLaboratorio\Services\LaboratorioService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResultadoLabController extends FlujoController
{
    public function index(Request $request, LaboratorioService $lab)
    {
        abort_if($request->user()->isPatient(), 403);
        $data = $this->validated($request, ['q' => 'nullable|string|max:100', 'orden_id' => 'nullable|integer|min:1', 'desde' => 'nullable|date_format:Y-m-d', 'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')], 'estado' => ['nullable', Rule::in(['Pendiente', 'En proceso', 'Completada', 'Cancelada'])], 'page' => 'nullable|integer|min:1']);
        $q = $lab->orders($request->user())->select('o.id', 'o.fecha_solicitud', 'o.estado')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente");
        if (! empty($data['q'])) {
            $q->whereRaw("concat_ws(' ',p.nombres,p.apellidos,p.ci) ILIKE ?", ['%'.$data['q'].'%']);
        }
        if (! empty($data['orden_id'])) {
            $q->where('o.id', $data['orden_id']);
        }
        if (! empty($data['estado'])) {
            $q->where('o.estado', $data['estado']);
        }
        if (! empty($data['desde'])) {
            $q->where('o.fecha_solicitud', '>=', Clinic::date($data['desde'])->startOfDay());
        }
        if (! empty($data['hasta'])) {
            $q->where('o.fecha_solicitud', '<', Clinic::date($data['hasta'])->addDay()->startOfDay());
        }
        $rows = $q->orderByDesc('o.fecha_solicitud')->orderByDesc('o.id')->paginate(20)->withQueryString();

        return view('laboratory.index', compact('rows'));
    }

    public function show(Request $request, int $id, LaboratorioService $lab)
    {
        abort_if($request->user()->isPatient(), 403);
        $order = $lab->orders($request->user())->where('o.id', $id)->select('o.*')->selectRaw("concat_ws(' ',p.nombres,p.apellidos) as paciente")->first() ?? abort(404);
        $items = DB::table('orden_analisis as d')->join('analisis_laboratorio as a', 'a.id', '=', 'd.analisis_id')->leftJoin('resultado_laboratorio as r', 'r.orden_analisis_id', '=', 'd.id')->where('d.orden_id', $id)->select('d.id', 'a.nombre', 'r.id as resultado_id')->orderBy('d.id')->get();
        $results = $lab->results($request->user())->where('o.id', $id)->orderBy('r.id')->get();

        return view('laboratory.order', compact('order', 'items', 'results'));
    }

    public function create(Request $request, int $item, LaboratorioService $lab)
    {
        $row = $lab->item($request->user(), $item);

        return $this->form($request, 'Registrar resultado: '.$row->nombre, route('results.store', $item), [
            'resultado' => ['label' => 'Resultado', 'type' => 'textarea', 'required' => true],
            'observaciones' => ['label' => 'Observaciones', 'type' => 'textarea'],
            'archivo' => ['label' => 'Informe opcional: PDF, JPG o PNG, máximo 10 MB', 'type' => 'file'],
        ], [], 'resultado.'.$item);
    }

    public function store(Request $request, int $item, LaboratorioService $lab, InformePrivado $files)
    {
        $lab->processor($request->user());
        $data = $this->validated($request, ['resultado' => 'required|string|max:10000', 'observaciones' => 'nullable|string|max:10000', 'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', 'archivo_token' => 'nullable|string|max:4096', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $operation = 'resultado.'.$item;
        if ($data['paso'] === 'revisar') {
            $row = $lab->item($request->user(), $item);
            if ($request->hasFile('archivo')) {
                $data['archivo_token'] = $files->stage($request->user(), $request->file('archivo'));
            }
            unset($data['archivo'],$data['paso']);

            return $this->review($request, 'Revisar resultado', url()->current(), $data, ['Análisis' => $row->nombre, 'Resultado' => $data['resultado'], 'Observaciones' => $data['observaciones'] ?? '—', 'Informe' => empty($data['archivo_token']) ? 'Sin informe' : 'Adjunto privado; disponible durante 24 horas'], $operation);
        }
        abort_if($request->hasFile('archivo'), 422, 'Revisa el informe antes de confirmar.');
        $temporary = $files->decode($request->user(), $data['archivo_token'] ?? null);
        $created = null;
        try {
            $id = OperacionWeb::run($request, $operation, function () use ($lab, $request, $item, $data, $files, $temporary, &$created) {
                $row = $lab->item($request->user(), $item);
                DB::table('orden_laboratorio')->where('id', $row->orden_id)->lockForUpdate()->first();
                $lab->item($request->user(), $item);
                if ($temporary) {
                    $created = $files->copy($temporary);
                }

                return DB::table('resultado_laboratorio')->insertGetId(['orden_analisis_id' => $item, 'laboratorista_id' => $request->user()->id, 'resultado' => trim($data['resultado']), 'observaciones' => $data['observaciones'] ?? null, 'archivo_referencia' => $created]);
            });
        } catch (\Throwable $error) {
            if ($created) {
                Storage::disk('local')->delete($created);
            }
            throw $error;
        }
        if ($temporary) {
            Storage::disk('local')->delete($temporary);
        }

        return redirect()->route('results.show', $id)->with('success', 'Resultado registrado. Aún no está publicado para el paciente.');
    }

    public function result(Request $request, int $id, LaboratorioService $lab)
    {
        $row = $lab->results($request->user())->where('r.id', $id)->first() ?? abort(404);

        return view('laboratory.result', compact('row') + ['key' => OperacionWeb::token()]);
    }

    public function publish(Request $request, int $id, LaboratorioService $lab)
    {
        $lab->processor($request->user());
        $data = $this->validated($request, ['publicar' => 'required|boolean']);
        $operation = 'publicacion.'.$id.'.'.(int) $data['publicar'];
        OperacionWeb::run($request, $operation, function () use ($id, $lab, $request, $data) {
            abort_unless($lab->results($request->user())->where('r.id', $id)->lockForUpdate()->first(), 404);
            DB::table('resultado_laboratorio')->where('id', $id)->update(['publicado_por' => $data['publicar'] ? $request->user()->id : null, 'publicado_en' => $data['publicar'] ? now() : null]);

            return $id;
        });

        return redirect()->route('results.show', $id)->with('success', $data['publicar'] ? 'Resultado publicado para el paciente.' : 'Publicación retirada.');
    }

    public function download(Request $request, int $id, LaboratorioService $lab, InformePrivado $files)
    {
        $row = $lab->results($request->user())->where('r.id', $id)->first() ?? abort(404);

        return $files->download($row->archivo_referencia ?? '');
    }
}
