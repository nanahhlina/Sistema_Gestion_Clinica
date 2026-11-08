<?php

use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['active' => EnsureActiveAccount::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $error, Request $request) {
            if (! $request->expectsJson() && $request->is('finanzas*', 'abastecimiento*', 'citas/*/precio', 'clinica/pacientes/*/datos-medicos')) {
                return response()->view('workflows.invalid', ['messages' => array_merge(...array_values($error->errors()))], 422);
            }

            return null;
        });
        $exceptions->report(function (QueryException $error) {
            if (request()->is('citas*', 'mi-cuenta*', 'clinica*', 'laboratorio*', 'reportes*', 'finanzas*', 'abastecimiento*')) {
                // Una excepción SQL puede contener valores clínicos en sus bindings.
                Log::warning('Operación clínica rechazada por PostgreSQL.', ['sqlstate' => $error->errorInfo[0] ?? 'unknown']);

                return false;
            }

            return null;
        });
        $exceptions->dontFlash(['password', 'password_confirmation', 'password_hash', 'descripcion', 'indicaciones', 'notas_clinicas', 'resultado', 'observaciones', 'motivo', 'motivo_consulta', 'archivo', 'archivo_token', 'enfermedades_cronicas', 'medicamentos_actuales']);
        $expiredSession = function (Request $request) {
            $message = 'Tu sesión ha vencido o no está activa. Inicia sesión para continuar.';
            Auth::forgetGuards();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                // El aviso sobrevive al GET de ingreso también tras una respuesta JSON.
                $request->session()->flash('warning', $message);
            }

            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => $message, 'redirect' => route('login')], 401)
                : redirect()->route('login');
        };
        $exceptions->render(fn (AuthenticationException $error, Request $request) => $expiredSession($request));
        $exceptions->render(function (HttpExceptionInterface $error, Request $request) use ($expiredSession) {
            // Laravel convierte TokenMismatchException en HTTP 419 antes de estos callbacks.
            if ($error->getStatusCode() === 419 && $error->getPrevious() instanceof TokenMismatchException
                && ! $request->user()) {
                return $expiredSession($request);
            }

            return null;
        });
        /** Los errores esperados de integridad se traducen sin mostrar SQL ni credenciales. */
        $exceptions->render(function (QueryException $error, Request $request) {
            $code = $error->errorInfo[0] ?? '';
            $message = match ($code) {
                '23505' => $request->is('abastecimiento*') ? 'El registro ya existe. Revisa el NIT o el número de lote del producto.' : 'Ya existe un registro con ese identificador. Revisa CI, usuario, correo o nombre.',
                '23P01' => 'El horario se superpone con una cita o franja existente. Elige otro bloque.',
                '23503' => 'La referencia ya no existe o el registro está relacionado con otros datos.',
                '23514' => 'Los datos no cumplen las restricciones del registro.',
                'P0001' => trim(preg_replace('/^.*?ERROR:\s*/s', '', explode('CONTEXT:', $error->errorInfo[2] ?? 'Operación rechazada.')[0])),
                default => null,
            };
            if ($message !== null) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message, 'errors' => ['operacion' => [$message]]], 422);
                }

                if ($request->is('citas*', 'mi-cuenta*', 'clinica*', 'laboratorio*', 'reportes*', 'finanzas*', 'abastecimiento*')) {
                    return response()->view('workflows.invalid', ['messages' => [$message]], 422);
                }

                return back()->withInput($request->except(['password', 'password_confirmation', 'password_hash', 'enfermedades_cronicas', 'medicamentos_actuales']))->withErrors(['operacion' => $message]);
            }

            return null;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
