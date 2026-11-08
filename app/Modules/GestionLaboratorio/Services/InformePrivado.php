<?php

namespace App\Modules\GestionLaboratorio\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InformePrivado
{
    public function stage(User $user, UploadedFile $file): string
    {
        $path = 'laboratorio/pendientes/'.$user->id.'/'.Str::uuid().'.'.$file->extension();
        abort_unless(Storage::disk('local')->putFileAs(dirname($path), $file, basename($path)), 503, 'No se pudo almacenar el informe. Intenta nuevamente.');

        return Crypt::encryptString(json_encode(['usuario' => $user->id, 'ruta' => $path, 'vence' => time() + 86400]));
    }

    public function decode(User $user, ?string $token): ?string
    {
        if (! $token) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(422, 'Referencia de informe inválida. Selecciona nuevamente el archivo.');
        }
        abort_unless(($data['usuario'] ?? null) === $user->id && ($data['vence'] ?? 0) >= time() && preg_match('~^laboratorio/pendientes/'.$user->id.'/[a-f0-9-]+\.(pdf|jpg|jpeg|png)$~', $data['ruta'] ?? ''), 422, 'El informe temporal venció o no pertenece a esta cuenta.');

        return $data['ruta'];
    }

    public function copy(string $temporary): string
    {
        $target = 'laboratorio/informes/'.Str::uuid().'.'.pathinfo($temporary, PATHINFO_EXTENSION);
        abort_unless(Storage::disk('local')->exists($temporary), 422, 'El archivo temporal ya no está disponible. Selecciónalo nuevamente.');
        abort_unless(Storage::disk('local')->copy($temporary, $target), 503, 'No se pudo guardar el informe.');

        return $target;
    }

    public function download(string $path)
    {
        abort_unless(preg_match('~^laboratorio/informes/[a-f0-9-]+\.(pdf|jpg|jpeg|png)$~', $path) && Storage::disk('local')->exists($path), 404, 'Informe no disponible.');

        return Storage::disk('local')->download($path, 'informe.'.pathinfo($path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
