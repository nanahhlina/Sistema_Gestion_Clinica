<?php

namespace App\Modules\Auditoria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        Clinic::authorize($request->user(), 'bitacora.consultar');
        $data = $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde'], 'entidad' => ['nullable', 'string', 'max:80']]);
        $q = DB::table('bitacora as b')->leftJoin('usuario as u', 'u.id', '=', 'b.usuario_id')->select('b.*', 'u.nombre_usuario as actor');
        if (! empty($data['desde'])) {
            $q->where('b.fecha', '>=', Clinic::date($data['desde'])->startOfDay());
        }if (! empty($data['hasta'])) {
            $q->where('b.fecha', '<', Clinic::date($data['hasta'])->addDay()->startOfDay());
        }if (! empty($data['entidad'])) {
            $q->where('b.entidad', $data['entidad']);
        }
        $rows = $q->orderByDesc('b.fecha')->orderByDesc('b.id')->paginate(30)->withQueryString();

        return view('auditoria.index', compact('rows'));
    }
}
