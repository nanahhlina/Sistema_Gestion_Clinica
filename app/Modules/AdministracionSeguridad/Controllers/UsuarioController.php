<?php

namespace App\Modules\AdministracionSeguridad\Controllers;

use App\Modules\GestionPacientes\Services\VinculoPacienteService;
use App\Modules\Shared\Controllers\CatalogoController;
use App\Modules\Shared\Services\CatalogoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UsuarioController extends CatalogoController
{
    protected const MODULO = 'usuarios';

    public function __construct(CatalogoService $catalog, private VinculoPacienteService $accounts)
    {
        parent::__construct($catalog);
    }

    public function editPatient(Request $request, int $id): View
    {
        $account = $this->accounts->account($request->user(), $id);
        $patients = $this->accounts->options($account);

        return view('catalog.patient-account', compact('account', 'patients'));
    }

    public function updatePatient(Request $request, int $id): RedirectResponse
    {
        $this->accounts->account($request->user(), $id);
        $data = $request->validate([
            'paciente_id' => ['present', 'nullable', 'integer', 'exists:paciente,id'],
            'confirmar_vinculo' => ['required', 'accepted'],
            'paciente_verificado_por' => ['missing'],
            'paciente_verificado_en' => ['missing'],
        ]);
        $this->accounts->update($request->user(), $id, isset($data['paciente_id']) ? (int) $data['paciente_id'] : null);

        return redirect()->route('catalog.users.patient.edit', $id)->with('success', 'Vínculo con el paciente guardado correctamente.');
    }
}
