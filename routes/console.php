<?php

use App\Modules\GestionFinanciera\Services\PayPalGateway;
use App\Modules\GestionFinanciera\Services\PayPalService;
use App\Modules\GestionPacientes\Services\RecordatorioService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('laboratorio:limpiar-temporales', function () {
    $disk = Storage::disk('local');
    $count = 0;
    foreach ($disk->allFiles('laboratorio/pendientes') as $path) {
        if (preg_match('~^laboratorio/pendientes/\d+/[a-f0-9-]+\.(pdf|jpg|jpeg|png)$~', $path) && $disk->lastModified($path) < time() - 86400) {
            $disk->delete($path);
            $count++;
        }
    }
    $this->info('Informes temporales vencidos eliminados: '.$count);
})->purpose('Eliminar únicamente informes temporales generados y vencidos');
Schedule::command('laboratorio:limpiar-temporales')->hourly()->withoutOverlapping();

Artisan::command('paypal:conciliar', function () {
    $gateway = app(PayPalGateway::class);
    if (! $gateway->configured()) {
        $this->warn('PayPal Sandbox no configurado. No se consultó ni modificó la base.');

        return 1;
    }
    $ready = DB::table('migrations')->whereIn('migration', ['2026_11_07_000008_complete_payment_processing_support', '2026_11_07_000009_keep_paypal_reconciliation_fair'])->count() === 2;
    if (! $ready) {
        $this->warn('Actualización de pagos pendiente.');

        return 1;
    }
    $ids = DB::table('pago as p')->leftJoin('transaccion_paypal as t', 't.pago_id', '=', 'p.id')->where('p.metodo', 'PayPal')->where(function ($q) {
        $q->where('p.estado', 'Pendiente')->orWhere(function ($q) {
            $q->where('p.estado', 'Completado')->whereNotNull('t.reembolso_solicitado_en')->whereNotIn('t.estado_reembolso', ['COMPLETED', 'FAILED', 'CANCELLED']);
        });
    })->orderByRaw('p.ultima_verificacion_en NULLS FIRST, p.id')->limit(100)->pluck('p.id');
    foreach ($ids as $id) {
        app(PayPalService::class)->process($id);
    }
    $this->info('Intentos revisados: '.$ids->count());

    return 0;
})->purpose('Conciliar únicamente pagos PayPal Sandbox existentes, con exclusión persistida por pago');
Schedule::command('paypal:conciliar')->everyMinute()->withoutOverlapping();

Artisan::command('recordatorios:enviar', function () {
    $service = app(RecordatorioService::class);
    $result = $service->processScheduled();
    $this->info("Recordatorios procesados: {$result['total']} (Enviados: {$result['enviados']}, Fallidos: {$result['fallidos']})");

    return 0;
})->purpose('Enviar los recordatorios programados pendientes cuya fecha y hora hayan llegado');
Schedule::command('recordatorios:enviar')->everyMinute()->withoutOverlapping();
