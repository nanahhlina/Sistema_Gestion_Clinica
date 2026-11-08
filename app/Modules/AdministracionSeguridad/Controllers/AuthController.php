<?php

namespace App\Modules\AdministracionSeguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $login = mb_strtolower(trim($request->string('login')->toString()));
        $key = 'login:'.Str::lower($login).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Clinic::fail('Demasiados intentos. Espera un minuto e inténtalo de nuevo.', 'login');
        }
        $column = str_contains($login, '@') ? 'correo' : 'nombre_usuario';
        $user = User::whereRaw('lower(btrim('.$column.')) = ?', [$login])->first();
        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password_hash) || ! $user->isActive()) {
            RateLimiter::hit($key, 60);
            Clinic::fail('Las credenciales no son válidas o la cuenta está desactivada.', 'login');
        }
        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        Clinic::transaction($user, fn () => DB::table('bitacora')->insert(['usuario_id' => $user->id, 'accion' => 'INGRESO', 'entidad' => 'usuario', 'registro_id' => $user->id, 'descripcion' => 'Inicio de sesión', 'direccion_ip' => $request->ip()]));

        return redirect()->intended(route('home'));
    }

    public function confirmLogout(): View
    {
        return view('auth.logout-confirm');
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        // La exclusión durable evita duplicar la salida aun si un reintento llega
        // con autenticación antigua después de vencer el bloqueo de sesión.
        $operationKey = 'logout:'.hash('sha256', $request->session()->getId());
        Clinic::transaction($user, fn () => DB::table('bitacora')->insertOrIgnore([
            'usuario_id' => $user->id,
            'accion' => 'SALIDA',
            'entidad' => 'usuario',
            'registro_id' => $user->id,
            'descripcion' => 'Cierre de sesión',
            'direccion_ip' => $request->ip(),
            'clave_operacion' => $operationKey,
        ]));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('login')]);
        }

        return redirect()->route('login');
    }
}
