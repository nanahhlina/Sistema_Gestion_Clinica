<?php

namespace App\Modules\Shared\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Modules\Shared\Clinic;
use App\Modules\Shared\Services\CatalogoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class CatalogoController extends Controller
{
    protected const MODULO = '';

    public function __construct(protected CatalogoService $catalog) {}

    public function index(Request $request): View
    {
        $module = static::MODULO;
        $this->catalog->read($request->user(), $module);
        $definition = $this->catalog->definition($module);
        $columns = $definition['columns'];
        $canWrite = $request->user()->hasPermission($definition['permission']);
        $query = $this->catalog->query($module);
        $rules = ['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']];
        if ($module === 'horarios') {
            $rules += ['medico_id' => ['nullable', 'integer', 'exists:medico,id'], 'dia_semana' => ['nullable', 'integer', 'between:1,7']];
        }
        $filters = $request->validate($rules);
        $doctors = $module === 'horarios' ? Clinic::doctors(includeInactive: true) : [];
        if ($module === 'medicos') {
            $this->searchDoctors($query, (string) $request->query('q', ''));
        } elseif ($module === 'horarios') {
            if (! empty($filters['medico_id'])) {
                $query->where('t.medico_id', $filters['medico_id']);
            }
            if (! empty($filters['dia_semana'])) {
                $query->where('t.dia_semana', $filters['dia_semana']);
            }
            $this->searchSchedules($query, (string) ($filters['q'] ?? ''));
        } elseif ($search = $request->query('q')) {
            $query->where(function ($q) use ($columns, $search) {
                foreach (array_keys($columns) as $field) {
                    if (in_array($field, ['activo', 'dia', 'medico', 'rol', 'duracion_minutos'], true)) {
                        continue;
                    } if ($field === 'nombre' && in_array('registro_profesional', array_keys($columns), true)) {
                        continue;
                    } $q->orWhere('t.'.$field, 'ilike', '%'.$search.'%');
                }
            });
        }
        $rows = $query->orderByDesc('t.id')->paginate(15)->appends($filters);
        $hasActions = $canWrite || ($module === 'medicos' && $request->user()->hasPermission('agenda.consultar'));

        return view('catalog.index', compact('module', 'definition', 'columns', 'canWrite', 'hasActions', 'rows', 'filters', 'doctors'));
    }

    private function searchSchedules(Builder $query, string $search): void
    {
        $search = trim(preg_replace('/\s+/u', ' ', $search));
        if ($search === '') {
            return;
        }
        $pattern = '%'.$search.'%';
        $query->where(function (Builder $query) use ($pattern): void {
            $query->whereRaw("regexp_replace(concat_ws(' ', u.nombres, u.apellidos), '[[:space:]]+', ' ', 'g') ILIKE ?", [$pattern])
                ->orWhere('t.hora_inicio', 'ilike', $pattern)->orWhere('t.hora_fin', 'ilike', $pattern);
        });
    }

    private function searchDoctors(Builder $query, string $search): void
    {
        $search = trim(preg_replace('/\s+/u', ' ', $search));
        if ($search === '') {
            return;
        }
        $pattern = '%'.$search.'%';
        $query->where(function (Builder $query) use ($pattern): void {
            foreach (['u.nombres', 'u.apellidos', 'u.correo', 't.registro_profesional', 't.telefono'] as $field) {
                $query->orWhere($field, 'ilike', $pattern);
            }
            $query->orWhereRaw("regexp_replace(concat_ws(' ', u.nombres, u.apellidos), '[[:space:]]+', ' ', 'g') ILIKE ?", [$pattern])
                ->orWhereExists(fn (Builder $specialty) => $specialty->selectRaw('1')
                    ->from('medico_especialidad as me')->join('especialidad as e', 'e.id', '=', 'me.especialidad_id')
                    ->whereColumn('me.medico_id', 't.id')->where('me.activo', true)
                    ->whereRaw("regexp_replace(e.nombre, '[[:space:]]+', ' ', 'g') ILIKE ?", [$pattern]));
        });
    }

    public function create(Request $request): View
    {
        return $this->form($request, static::MODULO, null);
    }

    public function edit(Request $request, int $id): View
    {
        return $this->form($request, static::MODULO, $id);
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

    public function store(CatalogRequest $request): RedirectResponse
    {
        $module = static::MODULO;
        $this->catalog->save($request->user(), $module, $request->validated(), null);

        return redirect()->route('catalog.index', $module)->with('success', 'Registro creado correctamente.');
    }

    public function update(CatalogRequest $request, int $id): RedirectResponse
    {
        $module = static::MODULO;
        $this->catalog->save($request->user(), $module, $request->validated(), $id);

        return redirect()->route('catalog.index', $module)->with('success', 'Cambios guardados correctamente.');
    }
}
