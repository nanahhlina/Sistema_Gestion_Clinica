<?php

namespace App\Modules\GestionAbastecimiento\Controllers;

use App\Modules\GestionAbastecimiento\Services\CatalogoAbastecimientoService;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Controllers\FlujoController;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

abstract class CatalogoAbastecimientoController extends FlujoController
{
    protected string $kind;

    public function index(Request $request, CatalogoAbastecimientoService $service)
    {
        $kind = $this->kind;
        $definition = $service->definition($kind);
        Clinic::authorize($request->user(), $definition['permission']);
        $filters = $this->validated($request, ['q' => 'nullable|string|max:150', 'activo' => ['nullable', Rule::in(['0', '1'])], 'tipo' => ['nullable', Rule::in(['Medicamento', 'Insumo'])], 'page' => 'nullable|integer|min:1']);
        $query = DB::table($definition['table']);
        if (! empty($filters['q'])) {
            $query->where(function ($q) use ($filters, $kind) {
                $q->where('nombre', 'ilike', '%'.$filters['q'].'%');
                if ($kind === 'proveedores') {
                    $q->orWhere('nit', 'ilike', '%'.$filters['q'].'%');
                }
            });
        }
        if (isset($filters['activo'])) {
            $query->where('activo', (bool) $filters['activo']);
        }
        if ($kind === 'productos' && ! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }
        $rows = $query->orderBy('nombre')->orderBy('id')->paginate(20)->withQueryString();

        return view('abastecimiento.catalogo', compact('kind', 'definition', 'filters', 'rows'));
    }

    public function edit(Request $request, CatalogoAbastecimientoService $service, ?int $id = null)
    {
        $definition = $service->definition($this->kind);
        Clinic::authorize($request->user(), $definition['permission']);
        $row = $id ? DB::table($definition['table'])->where('id', $id)->first() ?? abort(404) : null;

        return $this->form($request, ($id ? 'Editar ' : 'Registrar ').mb_strtolower($definition['title']), route('supply.'.$this->kind.'.save', $id ? ['id' => $id] : []),
            $definition['fields'] + ['version' => ['type' => 'hidden']],
            ($row ? (array) $row : ['activo' => 1]) + ['version' => $row ? $service->version($this->kind, $row) : 'nuevo'], $this->kind.'.'.($id ?? 'nuevo'));
    }

    public function save(Request $request, CatalogoAbastecimientoService $service, ?int $id = null)
    {
        $definition = $service->definition($this->kind);
        Clinic::authorize($request->user(), $definition['permission']);
        if ($id) {
            abort_unless(DB::table($definition['table'])->where('id', $id)->exists(), 404);
        }
        $data = $this->validated($request, $service->rules($this->kind) + ['version' => 'required|string|max:64', 'paso' => ['required', Rule::in(['revisar', 'guardar'])]]);
        $operation = $this->kind.'.'.($id ?? 'nuevo');
        if ($data['paso'] === 'revisar') {
            unset($data['paso']);
            $summary = [];
            foreach ($definition['fields'] as $field => $config) {
                $value = $data[$field] ?? null;
                $summary[$config['label']] = isset($config['options']) ? ($config['options'][$value] ?? '—') : ($value ?? '—');
            }

            return $this->review($request, 'Confirmar '.$definition['title'], url()->current(), $data, $summary, $operation);
        }
        OperacionWeb::run($request, $operation, fn () => $service->save($request->user(), $this->kind, $id, $data));

        return redirect()->route('supply.'.$this->kind.'.index')->with('success', 'Registro guardado. Los datos históricos se conservan.');
    }
}
