<?php

namespace App\Modules\Catalogos\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Modules\Catalogos\Services\CatalogService;
use App\Modules\Shared\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function index(Request $request, string $module): View
    {
        $this->catalog->read($request->user(), $module);
        $definition = $this->catalog->definition($module);
        $columns = $definition['columns'];
        $canWrite = $request->user()->hasPermission($definition['permission']);
        $query = $this->catalog->query($module);
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($columns, $search) {
                foreach (array_keys($columns) as $field) {
                    if (in_array($field, ['activo', 'dia', 'medico', 'rol'], true)) {
                        continue;
                    } if ($field === 'nombre' && in_array('registro_profesional', array_keys($columns), true)) {
                        continue;
                    } $q->orWhere('t.'.$field, 'ilike', '%'.$search.'%');
                }
            });
        }
        $rows = $query->orderByDesc('t.id')->paginate(15)->withQueryString();

        return view('catalog.index', compact('module', 'definition', 'columns', 'canWrite', 'rows'));
    }

    public function create(Request $request, string $module): View
    {
        return $this->form($request, $module, null);
    }

    public function edit(Request $request, string $module, int $id): View
    {
        return $this->form($request, $module, $id);
    }

    private function form(Request $request, string $module, ?int $id): View
    {
        $definition = $this->catalog->definition($module);
        Clinic::authorize($request->user(), $definition['permission']);
        $record = $id ? $this->catalog->query($module)->where('t.id', $id)->first() : null;
        abort_if($id && ! $record, 404);
        if ($record && $module === 'medicos') {
            $record->especialidad_ids = DB::table('medico_especialidad')->where('medico_id', $id)->where('activo', true)->pluck('especialidad_id')->all();
        }
        if ($record && $module === 'roles') {
            $record->permiso_ids = DB::table('rol_permiso')->where('rol_id', $id)->pluck('permiso_id')->all();
        }
        if ($record && $module === 'horarios') {
            $record->hora_inicio = substr($record->hora_inicio, 0, 5);
            $record->hora_fin = substr($record->hora_fin, 0, 5);
        }
        $options = $this->catalog->options($module, $id);

        return view('catalog.form', compact('definition', 'module', 'record', 'options'));
    }

    public function store(CatalogRequest $request, string $module): RedirectResponse
    {
        $this->catalog->save($request->user(), $module, $request->validated(), null);

        return redirect()->route('catalog.index', $module)->with('success', 'Registro creado correctamente.');
    }

    public function update(CatalogRequest $request, string $module, int $id): RedirectResponse
    {
        $this->catalog->save($request->user(), $module, $request->validated(), $id);

        return redirect()->route('catalog.index', $module)->with('success', 'Cambios guardados correctamente.');
    }
}
