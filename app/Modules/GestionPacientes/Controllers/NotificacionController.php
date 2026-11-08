<?php

namespace App\Modules\GestionPacientes\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Notificaciones y Recordatorios según el diagrama del sistema.
 * Conectado con la gestión de recordatorios CU24.
 */
class NotificacionController extends Controller
{
    public function index(Request $request, RecordatorioController $recordatorios)
    {
        if ($request->user()->isPatient()) {
            return $recordatorios->patientIndex($request);
        }

        if ($request->user()->isDoctor()) {
            return $recordatorios->doctorIndex($request);
        }

        return $recordatorios->index($request);
    }
}
