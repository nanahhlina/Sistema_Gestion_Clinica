<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDatabaseSafety;

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->make(ConsoleKernel::class)->bootstrap();
    $app['config']->set('database.default', 'pgsql');
    $app['config']->set('database.connections.pgsql', $input['database']);
    $app['config']->set('session.driver', 'array');
    $app['config']->set('cache.default', 'array');
    $app['config']->set('app.key', $input['app_key']);
    DB::purge('pgsql');
    TestDatabaseSafety::assertApplication($app);
    DB::selectOne("select set_config('application_name', ?, false)", [$input['application_name']]);
    DB::statement("set lock_timeout = '10s'");
    DB::statement("set statement_timeout = '15s'");
    Auth::loginUsingId($input['actor']);

    if ($input['holder']) {
        DB::beginTransaction();
        DB::table('medico')->where('id', $input['payload']['medico_id'])->lockForUpdate()->first();
        DB::table('paciente')->where('id', $input['payload']['paciente_id'])->lockForUpdate()->first();
    }

    $waitForFile = static function (string $path): void {
        $deadline = microtime(true) + 15;
        while (! is_file($path)) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Tiempo agotado esperando la barrera del proceso de prueba.');
            }
            usleep(10000);
        }
    };

    touch($input['directory'].'/'.$input['name'].'.ready');
    $waitForFile($input['directory'].'/start');
    $kernel = $app->make(HttpKernel::class);
    $request = Request::create('/agenda', 'POST', $input['payload'], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $response = $kernel->handle($request);

    if ($input['holder']) {
        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException('La primera reserva falló con HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
        touch($input['directory'].'/holder.responded');
        $waitForFile($input['directory'].'/commit');
        DB::commit();
    }

    $kernel->terminate($request, $response);
    echo json_encode(['status' => $response->getStatusCode()], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(1);
}
