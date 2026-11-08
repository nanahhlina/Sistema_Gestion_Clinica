<?php

namespace App\Modules\Shared\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
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

        if ($user->hasPermission('finanzas.configurar')) {
            $add('Configuración financiera', 'Tipo de cambio e historial de cotizaciones', 'file', route('finance.configuration'));
        }
        foreach ([
            ['pagos.registrar', 'Cobros de consultas', 'Pagos y comprobantes', 'finance.payments.index'],
            ['ventas.gestionar', 'Ventas de medicamentos', 'Cobros y entrega por lotes', 'supply.sales.index'],
            ['productos.gestionar', 'Productos', 'Medicamentos e insumos médicos', 'supply.productos.index'],
            ['compras.gestionar', 'Compras', 'Registro y recepción de mercadería', 'supply.purchases.index'],
            ['proveedores.gestionar', 'Proveedores', 'Datos y estado de proveedores', 'supply.proveedores.index'],
            ['lotes.gestionar', 'Lotes', 'Vencimiento y disponibilidad', 'supply.lots.index'],
            ['inventario.consultar', 'Inventario y kardex', 'Existencias físicas, disponibles y movimientos', 'supply.inventory.index'],
        ] as [$permission, $title, $description, $route]) {
            if ($user->hasPermission($permission)) {
                $add($title, $description, 'file', route($route));
            }
        }

        if ($user->hasPermission('bitacora.consultar')) {
            $add('Bitácora', 'Actividad y trazabilidad', 'shield', route('auditoria.index'));
        }

        if ($user->hasPermission('recordatorios.gestionar') || ($user->hasPermission('citas.gestionar') && ! $user->isDoctor() && ! $user->isPatient())) {
            $add('Recordatorios', 'Recordatorios para pacientes y médicos sobre sus citas', 'bell', route('reminders.index'));
            $stats['Recordatorios programados'] = DB::table('vida_salud.recordatorio')->where('estado', 'Programado')->count();
        }

        if ($user->hasPermission('citas.gestionar') && ! $user->isDoctor() && ! $user->isPatient()) {
            $add('Agendar cita', 'Programar una cita médica', 'calendar', route('appointments.create'));
        }
        if ($user->isPatient()) {
            if ($user->hasPermission('citas.propias')) {
                $add('Mis citas y reservas', 'Reserva y consulta tus citas', 'calendar', route('appointments.mine'));
                $add('Mis recordatorios', 'Avisos y recordatorios de tus próximas citas', 'bell', route('patient.reminders'));
            }
            if ($user->hasPermission('perfil.propio')) {
                $add('Mi historial clínico', 'Diagnósticos, tratamientos y resultados publicados', 'file', route('clinical.mine'));
            }
        }
        if ($user->isDoctor()) {
            if ($user->hasPermission('historia.consultar')) {
                $add('Atención e historial clínico', 'Pacientes, diagnósticos y tratamientos', 'doctor', route('clinical.index'));
            }
            if ($user->hasPermission('agenda.consultar')) {
                $add('Recordatorios de citas', 'Recordatorios de consultas con tus pacientes', 'bell', route('doctor.reminders'));
            }
        }
        if (in_array($user->roleName(), ['Medico', 'Laboratorista']) && $user->hasPermission('laboratorio.consultar')) {
            $add('Laboratorio', 'Órdenes y resultados autorizados', 'file', route('orders.index'));
        }
        if ($user->roleName() === 'Administrador') {
            if ($user->hasPermission('analisis.gestionar')) {
                $add('Tipos de análisis', 'Catálogo de laboratorio', 'file', route('analysis-types.index'));
            }
            if ($user->hasPermission('reportes.administrativos')) {
                $add('Reportes del sistema', 'Citas, pacientes, atenciones y laboratorio', 'file', route('reports.index'));
            }
        }

        if ($user->hasPermission('agenda.consultar') && ! $user->isPatient()
            && ($user->isDoctor() || $user->hasPermission('pacientes.gestionar') || $user->hasPermission('medicos.gestionar'))) {
            $add('Agenda médica', 'Consulta de citas por fecha y estado', 'calendar', route('agenda.index'));
        }

        return view('home', compact('modules', 'stats'));
    }
}
