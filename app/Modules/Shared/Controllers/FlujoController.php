<?php

namespace App\Modules\Shared\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Services\OperacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class FlujoController extends Controller
{
    protected function form(Request $request, string $title, string $action, array $fields, array $values = [], string $operation = '', array $context = [])
    {
        $key = $request->input('clave') ?: OperacionWeb::token();
        $signature = $request->input('firma') ?: OperacionWeb::signature($request->user(), $operation, $key);

        return response()->view('workflows.form', compact('title', 'action', 'fields', 'values', 'operation', 'context', 'key', 'signature'));
    }

    protected function validated(Request $request, array $rules): array
    {
        // En estos flujos los errores nunca persisten el cuerpo clínico en la sesión.
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                abort(response()->json(['message' => 'Revisa los datos ingresados.', 'errors' => $validator->errors()], 422));
            }
            abort(response()->view('workflows.invalid', ['messages' => $validator->errors()->all()], 422));
        }

        return $validator->validated();
    }

    protected function review(Request $request, string $title, string $action, array $data, array $summary, string $operation)
    {
        $key = $request->input('clave');
        abort_unless(is_string($key) && is_string($request->input('firma')) && hash_equals(OperacionWeb::signature($request->user(), $operation, $key), $request->input('firma')), 422);

        return response()->view('workflows.review', ['title' => $title, 'action' => $action, 'data' => $data, 'summary' => $summary, 'key' => $key, 'signature' => $request->input('firma')]);
    }
}
