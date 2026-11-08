<?php

use App\Http\Middleware\EnsureCommerceReady;
use App\Http\Middleware\EnsureFinancialPhaseReady;
use App\Http\Middleware\EnsureInventoryPhaseReady;
use App\Http\Middleware\PrivateWorkflow;
use App\Modules\AdministracionSeguridad\Controllers\AuthController;
use App\Modules\AdministracionSeguridad\Controllers\RolController;
use App\Modules\AdministracionSeguridad\Controllers\UsuarioController;
use App\Modules\GestionAbastecimiento\Controllers\CompraController;
use App\Modules\GestionAbastecimiento\Controllers\InventarioController;
use App\Modules\GestionAbastecimiento\Controllers\LoteController;
use App\Modules\GestionAbastecimiento\Controllers\ProductoController;
use App\Modules\GestionAbastecimiento\Controllers\ProveedorController;
use App\Modules\GestionAbastecimiento\Controllers\VentaController;
use App\Modules\GestionCitasAgendaMedica\Controllers\AgendaMedicaController;
use App\Modules\GestionCitasAgendaMedica\Controllers\CitaController;
use App\Modules\GestionCitasAgendaMedica\Controllers\ReservaCitaController;
use App\Modules\GestionClinica\Controllers\ConsultaController;
use App\Modules\GestionClinica\Controllers\HistoriaClinicaController;
use App\Modules\GestionClinica\Controllers\TratamientoController;
use App\Modules\GestionFinanciera\Controllers\ConfiguracionFinancieraController;
use App\Modules\GestionFinanciera\Controllers\PagoController;
use App\Modules\GestionFinanciera\Controllers\PayPalController;
use App\Modules\GestionFinanciera\Controllers\PrecioConsultaController;
use App\Modules\GestionLaboratorio\Controllers\OrdenLaboratorioController;
use App\Modules\GestionLaboratorio\Controllers\ResultadoLabController;
use App\Modules\GestionLaboratorio\Controllers\TipoAnalisisController;
use App\Modules\GestionPacientes\Controllers\NotificacionController;
use App\Modules\GestionPacientes\Controllers\PacienteController;
use App\Modules\GestionPacientes\Controllers\RecordatorioController;
use App\Modules\ReportesSistema\Controllers\BitacoraController;
use App\Modules\ReportesSistema\Controllers\ReporteController;
use App\Modules\Shared\Controllers\CatalogoRouterController;
use App\Modules\Shared\Controllers\InicioController;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateWorkflow::class, EnsureCommerceReady::class, 'throttle:30,1'])->group(function (): void {
    Route::get('/pago/paypal/{id}/retorno', [PayPalController::class, 'customerReturn'])->whereNumber('id')->name('finance.paypal.customer.return');
    Route::get('/pago/paypal/{id}/cancelado', [PayPalController::class, 'customerCancel'])->whereNumber('id')->name('finance.paypal.customer.cancel');
    Route::post('/pago/paypal/{id}/verificar', [PayPalController::class, 'customerVerify'])->whereNumber('id')->middleware('signed')->name('finance.paypal.customer.verify');
    Route::get('/pago/paypal/{id}/comprobante', [PayPalController::class, 'customerReceipt'])->whereNumber('id')->middleware('signed')->name('finance.paypal.customer.receipt');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [AuthController::class, 'show'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::middleware(PrivateWorkflow::class)->group(function (): void {
        Route::middleware(EnsureCommerceReady::class)->group(function (): void {
            Route::post('/finanzas/pagos/{id}/paypal', [PayPalController::class, 'process'])->whereNumber('id')->name('finance.paypal.process');
            Route::post('/finanzas/pagos/{id}/paypal/cancelar', [PayPalController::class, 'cancel'])->whereNumber('id')->name('finance.paypal.cancel');
            Route::get('/finanzas/pagos/{id}/paypal/conciliacion', [PayPalController::class, 'reconcileForm'])->whereNumber('id')->name('finance.paypal.associate.form');
            Route::post('/finanzas/pagos/{id}/paypal/conciliacion', [PayPalController::class, 'associate'])->whereNumber('id')->name('finance.paypal.associate');
            Route::get('/finanzas/pagos/{id}/paypal/retorno', [PayPalController::class, 'returned'])->whereNumber('id')->name('finance.paypal.return');
            Route::get('/abastecimiento/ventas/{id}/pago', [PagoController::class, 'saleForm'])->whereNumber('id')->name('finance.sale.payment');
            Route::post('/abastecimiento/ventas/{id}/pago', [PagoController::class, 'saleStore'])->whereNumber('id')->name('finance.sale.payment.store');
            Route::get('/finanzas/cobros', [PagoController::class, 'index'])->name('finance.payments.index');
            Route::get('/finanzas/citas/{id}/pago', [PagoController::class, 'create'])->whereNumber('id')->name('finance.payments.create');
            Route::post('/finanzas/citas/{id}/pago', [PagoController::class, 'store'])->whereNumber('id')->name('finance.payments.store');
            Route::get('/finanzas/pagos/{id}', [PagoController::class, 'show'])->whereNumber('id')->name('finance.payments.show');
            Route::get('/finanzas/pagos/{id}/devolucion', [PagoController::class, 'refundForm'])->whereNumber('id')->name('finance.payments.refund.form');
            Route::post('/finanzas/pagos/{id}/devolucion', [PagoController::class, 'refund'])->whereNumber('id')->name('finance.payments.refund');
            Route::get('/abastecimiento/ventas', [VentaController::class, 'index'])->name('supply.sales.index');
            Route::get('/abastecimiento/ventas/nueva', [VentaController::class, 'create'])->name('supply.sales.create');
            Route::post('/abastecimiento/ventas', [VentaController::class, 'store'])->name('supply.sales.store');
            Route::get('/abastecimiento/ventas/{id}', [VentaController::class, 'show'])->whereNumber('id')->name('supply.sales.show');
            Route::post('/abastecimiento/ventas/{id}/entregar', [VentaController::class, 'retry'])->whereNumber('id')->name('supply.sales.retry');
        });
        Route::prefix('abastecimiento')->middleware(EnsureInventoryPhaseReady::class)->group(function (): void {
            Route::get('/compras', [CompraController::class, 'index'])->name('supply.purchases.index');
            Route::get('/compras/nueva', [CompraController::class, 'create'])->name('supply.purchases.create');
            Route::post('/compras', [CompraController::class, 'store'])->name('supply.purchases.store');
            Route::get('/compras/{id}', [CompraController::class, 'show'])->whereNumber('id')->name('supply.purchases.show');
            Route::post('/compras/{id}/recepciones', [CompraController::class, 'receive'])->whereNumber('id')->name('supply.purchases.receive');
            Route::post('/compras/{id}/cancelar', [CompraController::class, 'cancel'])->whereNumber('id')->name('supply.purchases.cancel');
            foreach (['productos' => ProductoController::class, 'proveedores' => ProveedorController::class] as $module => $controller) {
                Route::get('/'.$module, [$controller, 'index'])->name('supply.'.$module.'.index');
                Route::get('/'.$module.'/nuevo', [$controller, 'edit'])->name('supply.'.$module.'.create');
                Route::get('/'.$module.'/{id}/editar', [$controller, 'edit'])->whereNumber('id')->name('supply.'.$module.'.edit');
                Route::post('/'.$module.'/{id?}', [$controller, 'save'])->whereNumber('id')->name('supply.'.$module.'.save');
            }
            Route::get('/lotes', [LoteController::class, 'index'])->name('supply.lots.index');
            Route::get('/lotes/nuevo', [LoteController::class, 'edit'])->name('supply.lots.create');
            Route::get('/lotes/{id}/editar', [LoteController::class, 'edit'])->whereNumber('id')->name('supply.lots.edit');
            Route::post('/lotes/{id?}', [LoteController::class, 'save'])->whereNumber('id')->name('supply.lots.save');
            Route::get('/inventario', [InventarioController::class, 'index'])->name('supply.inventory.index');
            Route::get('/inventario/productos/{product}', [InventarioController::class, 'show'])->whereNumber('product')->name('supply.inventory.show');
            Route::get('/kardex', [InventarioController::class, 'kardex'])->name('supply.kardex');
            Route::get('/lotes/{lot}/movimiento', [InventarioController::class, 'create'])->whereNumber('lot')->name('supply.movements.create');
            Route::post('/lotes/{lot}/movimientos', [InventarioController::class, 'store'])->whereNumber('lot')->name('supply.movements.store');
        });
        Route::get('/finanzas/configuracion', [ConfiguracionFinancieraController::class, 'index'])->middleware(EnsureFinancialPhaseReady::class)->name('finance.configuration');
        Route::get('/finanzas/configuracion/editar', [ConfiguracionFinancieraController::class, 'edit'])->middleware(EnsureFinancialPhaseReady::class)->name('finance.configuration.edit');
        Route::post('/finanzas/configuracion', [ConfiguracionFinancieraController::class, 'save'])->middleware(EnsureFinancialPhaseReady::class)->name('finance.configuration.save');
        Route::get('/citas/{id}/precio', [PrecioConsultaController::class, 'edit'])->whereNumber('id')->middleware(EnsureFinancialPhaseReady::class)->name('appointments.price.edit');
        Route::post('/citas/{id}/precio', [PrecioConsultaController::class, 'save'])->whereNumber('id')->middleware(EnsureFinancialPhaseReady::class)->name('appointments.price.save');
        Route::get('/clinica/pacientes/{patient}/datos-medicos/editar', [HistoriaClinicaController::class, 'editMedical'])->whereNumber('patient')->middleware(EnsureFinancialPhaseReady::class)->name('clinical.medical.edit');
        Route::post('/clinica/pacientes/{patient}/datos-medicos', [HistoriaClinicaController::class, 'saveMedical'])->whereNumber('patient')->middleware(EnsureFinancialPhaseReady::class)->name('clinical.medical.save');
        Route::get('/citas/disponibilidad', [CitaController::class, 'availability'])->middleware('throttle:60,1')->name('appointments.availability');
        Route::get('/mi-cuenta/disponibilidad', [ReservaCitaController::class, 'availability'])->middleware('throttle:60,1')->name('reservations.availability');
        Route::get('/citas/nueva', [CitaController::class, 'create'])->name('appointments.create');
        Route::post('/citas', [CitaController::class, 'store'])->name('appointments.store');
        Route::get('/citas/{id}', [CitaController::class, 'show'])->whereNumber('id')->name('appointments.show');
        Route::get('/citas/{id}/gestionar', [CitaController::class, 'edit'])->whereNumber('id')->name('appointments.edit');
        Route::post('/citas/{id}/gestionar', [CitaController::class, 'update'])->whereNumber('id')->name('appointments.update');
        Route::get('/mi-cuenta/citas', [CitaController::class, 'mine'])->name('appointments.mine');
        Route::get('/mi-cuenta/recordatorios', [RecordatorioController::class, 'patientIndex'])->name('patient.reminders');
        Route::get('/agenda/recordatorios', [RecordatorioController::class, 'doctorIndex'])->name('doctor.reminders');
        Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notifications.index');

        // CU24 - Gestionar recordatorios
        Route::get('/recordatorios', [RecordatorioController::class, 'index'])->name('reminders.index');
        Route::get('/recordatorios/nuevo', [RecordatorioController::class, 'create'])->name('reminders.create');
        Route::post('/recordatorios', [RecordatorioController::class, 'store'])->name('reminders.store');
        Route::post('/recordatorios/procesar', [RecordatorioController::class, 'processScheduled'])->name('reminders.process');
        Route::get('/recordatorios/{id}', [RecordatorioController::class, 'show'])->whereNumber('id')->name('reminders.show');
        Route::get('/recordatorios/{id}/editar', [RecordatorioController::class, 'edit'])->whereNumber('id')->name('reminders.edit');
        Route::post('/recordatorios/{id}', [RecordatorioController::class, 'update'])->whereNumber('id')->name('reminders.update');
        Route::get('/recordatorios/{id}/cancelar', [RecordatorioController::class, 'cancelForm'])->whereNumber('id')->name('reminders.cancel.form');
        Route::post('/recordatorios/{id}/cancelar', [RecordatorioController::class, 'cancel'])->whereNumber('id')->name('reminders.cancel');
        Route::post('/recordatorios/{id}/enviar', [RecordatorioController::class, 'sendNow'])->whereNumber('id')->name('reminders.send');
        Route::post('/recordatorios/{id}/sincronizar', [RecordatorioController::class, 'syncAppointment'])->whereNumber('id')->name('reminders.sync');

        Route::get('/mi-cuenta/reservar', [ReservaCitaController::class, 'create'])->name('reservations.create');
        Route::post('/mi-cuenta/reservar', [ReservaCitaController::class, 'store'])->name('reservations.store');
        Route::get('/mi-cuenta/historial', [HistoriaClinicaController::class, 'mine'])->name('clinical.mine');
        Route::get('/clinica/pacientes', [HistoriaClinicaController::class, 'index'])->name('clinical.index');
        Route::get('/clinica/pacientes/{patient}', [HistoriaClinicaController::class, 'show'])->whereNumber('patient')->name('clinical.show');
        Route::get('/clinica/pacientes/{patient}/diagnosticos/nuevo', [ConsultaController::class, 'create'])->whereNumber('patient')->name('diagnoses.create');
        Route::post('/clinica/pacientes/{patient}/diagnosticos', [ConsultaController::class, 'store'])->whereNumber('patient')->name('diagnoses.store');
        Route::get('/clinica/pacientes/{patient}/tratamientos/nuevo', [TratamientoController::class, 'create'])->whereNumber('patient')->name('treatments.create');
        Route::post('/clinica/pacientes/{patient}/tratamientos', [TratamientoController::class, 'store'])->whereNumber('patient')->name('treatments.store');
        Route::get('/laboratorio/pacientes/{patient}/ordenes/nueva', [OrdenLaboratorioController::class, 'create'])->whereNumber('patient')->name('orders.create');
        Route::post('/laboratorio/pacientes/{patient}/ordenes', [OrdenLaboratorioController::class, 'store'])->whereNumber('patient')->name('orders.store');
        Route::get('/laboratorio/ordenes', [ResultadoLabController::class, 'index'])->name('orders.index');
        Route::get('/laboratorio/ordenes/{id}', [ResultadoLabController::class, 'show'])->whereNumber('id')->name('orders.show');
        Route::get('/laboratorio/analisis/{item}/resultado/nuevo', [ResultadoLabController::class, 'create'])->whereNumber('item')->name('results.create');
        Route::post('/laboratorio/analisis/{item}/resultado', [ResultadoLabController::class, 'store'])->whereNumber('item')->name('results.store');
        Route::get('/laboratorio/resultados/{id}', [ResultadoLabController::class, 'result'])->whereNumber('id')->name('results.show');
        Route::post('/laboratorio/resultados/{id}/publicacion', [ResultadoLabController::class, 'publish'])->whereNumber('id')->name('results.publish');
        Route::get('/laboratorio/resultados/{id}/informe', [ResultadoLabController::class, 'download'])->whereNumber('id')->name('results.download');
        Route::get('/laboratorio/tipos-analisis', [TipoAnalisisController::class, 'index'])->name('analysis-types.index');
        Route::get('/laboratorio/tipos-analisis/nuevo', [TipoAnalisisController::class, 'edit'])->name('analysis-types.create');
        Route::get('/laboratorio/tipos-analisis/{id}/editar', [TipoAnalisisController::class, 'edit'])->whereNumber('id')->name('analysis-types.edit');
        Route::post('/laboratorio/tipos-analisis/{id?}', [TipoAnalisisController::class, 'save'])->whereNumber('id')->name('analysis-types.save');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reports.index');
    });
    Route::get('/', [InicioController::class, 'index'])->name('home');
    Route::get('/salir/confirmar', [AuthController::class, 'confirmLogout'])->name('logout.confirm');
    Route::post('/salir', [AuthController::class, 'logout'])->block(10, 10)->name('logout');

    Route::get('/agenda', [AgendaMedicaController::class, 'index'])->name('agenda.index');
    Route::get('/gestion/pacientes/{id}/citas', [PacienteController::class, 'citas'])->whereNumber('id')->name('appointments.patient');
    Route::get('/gestion/medicos/{id}/agenda', [AgendaMedicaController::class, 'doctor'])->whereNumber('id')->name('agenda.doctor');
    Route::get('/gestion/usuarios/{id}/paciente', [UsuarioController::class, 'editPatient'])->whereNumber('id')->name('catalog.users.patient.edit');
    Route::put('/gestion/usuarios/{id}/paciente', [UsuarioController::class, 'updatePatient'])->whereNumber('id')->name('catalog.users.patient.update');

    Route::get('/gestion/roles/{id}/eliminar', [RolController::class, 'confirmDeleteRole'])->whereNumber('id')->name('catalog.roles.confirm-destroy');
    Route::delete('/gestion/roles/{id}', [RolController::class, 'destroyRole'])->whereNumber('id')->name('catalog.roles.destroy');
    Route::get('/gestion/{module}', [CatalogoRouterController::class, 'index'])->name('catalog.index');
    Route::get('/gestion/{module}/nuevo', [CatalogoRouterController::class, 'create'])->name('catalog.create');
    Route::post('/gestion/{module}', [CatalogoRouterController::class, 'store'])->name('catalog.store');
    Route::get('/gestion/{module}/{id}/editar', [CatalogoRouterController::class, 'edit'])->whereNumber('id')->name('catalog.edit');
    Route::match(['put', 'post'], '/gestion/{module}/{id}', [CatalogoRouterController::class, 'update'])->whereNumber('id')->name('catalog.update');

    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('auditoria.index');
    Route::get('/bitacora/{id}', [BitacoraController::class, 'show'])->whereNumber('id')->name('auditoria.show');
});
