<?php

namespace App\Modules\AdministracionSeguridad\Controllers;

use App\Modules\Shared\Controllers\CatalogoController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RolController extends CatalogoController
{
    protected const MODULO = 'roles';

    public function confirmDeleteRole(Request $request, int $id): View
    {
        $role = $this->catalog->roleForDeletion($request->user(), $id);

        return view('catalog.delete-role', compact('role'));
    }

    public function destroyRole(Request $request, int $id): RedirectResponse
    {
        $this->catalog->deleteRole($request->user(), $id);

        return redirect()->route('catalog.index', 'roles')->with('success', 'Rol eliminado correctamente.');
    }
}
