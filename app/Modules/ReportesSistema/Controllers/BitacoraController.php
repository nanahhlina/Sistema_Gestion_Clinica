<?php

namespace App\Modules\ReportesSistema\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        Clinic::authorize($request->user(), 'bitacora.consultar');
        $filters = $this->filters($request);
        $query = $this->query();

        if (! empty($filters['desde'])) {
            $query->where('b.fecha', '>=', Clinic::date($filters['desde'])->startOfDay());
        }
        if (! empty($filters['hasta'])) {
            $query->where('b.fecha', '<', Clinic::date($filters['hasta'])->addDay()->startOfDay());
        }
        if (isset($filters['entidad'])) {
            $query->where('b.entidad', $filters['entidad']);
        }
        if (($filters['usuario_id'] ?? null) === 'sistema') {
            $query->whereNull('b.usuario_id');
        } elseif (! empty($filters['usuario_id'])) {
            $query->where('b.usuario_id', $filters['usuario_id']);
        }
        if (isset($filters['accion'])) {
            $query->where('b.accion', $filters['accion']);
        }
        $rows = $query->orderByDesc('b.fecha')->orderByDesc('b.id')->paginate(30)->appends($filters);
        $users = ['sistema' => 'Sistema (sin usuario)'] + DB::table('usuario')->orderBy('nombre_usuario')->pluck('nombre_usuario', 'id')->all();
        $actions = $this->actions();

        return view('auditoria.index', compact('rows', 'filters', 'users', 'actions'));
    }

    public function show(Request $request, int $id): View
    {
        Clinic::authorize($request->user(), 'bitacora.consultar');
        $filters = $this->filters($request);
        $record = $this->query()->where('b.id', $id)->first();
        abort_unless($record, 404);
        $backUrl = route('auditoria.index', $filters);

        return view('auditoria.show', compact('record', 'backUrl'));
    }

    private function query(): Builder
    {
        return DB::table('bitacora as b')
            ->leftJoin('usuario as u', 'u.id', '=', 'b.usuario_id')
            ->select('b.*', 'u.nombre_usuario as actor');
    }

    private function actions(): array
    {
        return DB::table('bitacora')->distinct()->orderBy('accion')->pluck('accion', 'accion')->all();
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'desde' => ['nullable', 'date', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
            'entidad' => ['nullable', 'string', 'max:80'],
            'usuario_id' => ['nullable', Rule::when($request->input('usuario_id') === 'sistema', ['in:sistema'], ['integer', 'exists:usuario,id'])],
            'accion' => ['nullable', 'string', 'max:80', Rule::in(array_keys($this->actions()))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
