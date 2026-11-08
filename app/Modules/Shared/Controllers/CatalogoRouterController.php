<?php

namespace App\Modules\Shared\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Modules\AdministracionSeguridad\Controllers\RolController;
use App\Modules\AdministracionSeguridad\Controllers\UsuarioController;
use App\Modules\GestionMedicosEspecialidades\Controllers\EspecialidadController;
use App\Modules\GestionMedicosEspecialidades\Controllers\HorarioMedicoController;
use App\Modules\GestionMedicosEspecialidades\Controllers\MedicoController;
use App\Modules\GestionPacientes\Controllers\PacienteController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Adaptador de URLs existentes; la gestion reside en controladores de cada paquete. */
class CatalogoRouterController extends Controller
{
    public const CONTROLLERS = [
        'pacientes' => PacienteController::class,
        'usuarios' => UsuarioController::class,
        'roles' => RolController::class,
        'medicos' => MedicoController::class,
        'especialidades' => EspecialidadController::class,
        'horarios' => HorarioMedicoController::class,
    ];

    private function controller(string $module): CatalogoController
    {
        abort_unless(isset(self::CONTROLLERS[$module]), 404);

        return app(self::CONTROLLERS[$module]);
    }

    public function index(Request $request, string $module): View
    {
        return $this->controller($module)->index($request);
    }

    public function create(Request $request, string $module): View
    {
        return $this->controller($module)->create($request);
    }

    public function edit(Request $request, string $module, int $id): View
    {
        return $this->controller($module)->edit($request, $id);
    }

    public function store(CatalogRequest $request, string $module): RedirectResponse
    {
        return $this->controller($module)->store($request);
    }

    public function update(CatalogRequest $request, string $module, int $id): RedirectResponse
    {
        return $this->controller($module)->update($request, $id);
    }
}
