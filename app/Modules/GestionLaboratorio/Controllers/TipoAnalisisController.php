<?php

namespace App\Modules\GestionLaboratorio\Controllers;

use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TipoAnalisisController extends FlujoController
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->roleName() === 'Administrador', 403);
        Clinic::authorize($request->user(), 'analisis.gestionar');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $this->validated($request, ['page' => 'nullable|integer|min:1']);
        $rows = DB::table('analisis_laboratorio')->orderBy('nombre')->paginate(20);

        return view('laboratory.types', compact('rows'));
    }

    public function edit(Request $request, ?int $id = null)
    {
        $this->authorizeAdmin($request);
        $row = $id ? (DB::table('analisis_laboratorio')->where('id', $id)->first() ?? abort(404)) : null;

        return $this->form($request, $id ? 'Editar tipo de análisis' : 'Nuevo tipo de análisis', route('analysis-types.save', $id ? ['id' => $id] : []), [
            'nombre' => ['label' => 'Nombre', 'required' => true, 'max' => 120],
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 2000],
            'activo' => ['label' => 'Estado', 'options' => ['1' => 'Activo', '0' => 'Inactivo'], 'required' => true],
        ], $row ? (array) $row : ['activo' => 1], 'analisis.'.($id ?? 'nuevo'));
    }

    public function save(Request $request, ?int $id = null)
    {
        $this->authorizeAdmin($request);
        if ($id) {
            abort_unless(DB::table('analisis_laboratorio')->where('id', $id)->exists(), 404);
        }
        $data = $this->validated($request, ['nombre' => 'required|string|max:120', 'descripcion' => 'nullable|string|max:2000', 'activo' => 'required|boolean', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $operation = 'analisis.'.($id ?? 'nuevo');
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);

            return $this->review($request, 'Revisar tipo de análisis', url()->current(), $data, ['Nombre' => $data['nombre'], 'Descripción' => $data['descripcion'] ?? '—', 'Estado' => $data['activo'] ? 'Activo' : 'Inactivo'], $operation);
        }
        OperacionWeb::run($request, $operation, function () use ($data, $id) {
            unset($data['paso']);
            $data['nombre'] = trim($data['nombre']);
            if (DB::table('analisis_laboratorio')->whereRaw('lower(btrim(nombre))=lower(btrim(?))', [$data['nombre']])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists()) {
                Clinic::fail('Ya existe un análisis con ese nombre.');
            }
            if ($id) {
                DB::table('analisis_laboratorio')->where('id', $id)->update($data);

                return $id;
            }

            return DB::table('analisis_laboratorio')->insertGetId($data);
        });

        return redirect()->route('analysis-types.index')->with('success', 'Tipo de análisis guardado.');
    }
}
