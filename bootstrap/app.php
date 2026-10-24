<?php

use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $exceptions->dontFlash(['password', 'password_confirmation', 'password_hash']);
        /** Los errores esperados de integridad se traducen sin mostrar SQL ni credenciales. */
        $exceptions->render(function (QueryException $error, Request $request) {
            $code = $error->errorInfo[0] ?? '';
            $message = match ($code) {
                '23505' => 'Ya existe un registro con ese identificador. Revisa CI, usuario, correo o nombre.',
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

                return back()->withInput($request->except(['password', 'password_confirmation', 'password_hash']))->withErrors(['operacion' => $message]);
            }

            return null;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
