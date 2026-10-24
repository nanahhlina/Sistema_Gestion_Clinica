<?php

namespace App\Modules\Inicio\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $modules = [];
        $stats = [];
        $add = function (string $title, string $description, string $icon, string $url) use (&$modules) {
            $modules[] = compact('title', 'description', 'icon', 'url');
        };

        if ($user->hasPermission('pacientes.gestionar')) {
            $add('Pacientes', 'Registro de pacientes', 'users', route('catalog.index', 'pacientes'));
            $stats['Pacientes activos'] = DB::table('paciente')->where('activo', true)->count();
        }

        foreach (['medicos', 'especialidades', 'horarios', 'usuarios', 'roles'] as $module) {
            $def = config('clinic.catalogs.'.$module);
            if (! $def) {
                continue;
            }
            if ($user->hasPermission($def['permission']) || (in_array($module, ['medicos', 'especialidades', 'horarios'], true) && $user->hasPermission('pacientes.gestionar'))) {
                $add($def['title'], match ($module) {
                    'medicos' => 'Equipo médico',
                    'especialidades' => 'Catálogo de especialidades',
                    'horarios' => 'Disponibilidad semanal',
                    'usuarios' => 'Acceso al sistema',
                    default => 'Permisos por operación',
                }, match ($module) {
                    'medicos' => 'doctor',
                    'usuarios', 'roles' => 'user',
                    'horarios' => 'calendar',
                    default => 'file',
                }, route('catalog.index', $module));
            }
        }

        if ($user->hasPermission('bitacora.consultar')) {
            $add('Bitácora', 'Actividad y trazabilidad', 'shield', route('auditoria.index'));
        }

        return view('home', compact('modules', 'stats'));
    }
}
