<?php

namespace App\Modules\GestionPacientes\Services;

use App\Models\User;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;

class VinculoPacienteService
{
    public function account(User $actor, int $id): User
    {
        Clinic::authorize($actor, 'usuarios.gestionar');
        $account = User::findOrFail($id);
        abort_unless($account->isPatient(), 404);

        return $account;
    }

    public function options(User $account): array
    {
        return DB::table('paciente as p')
            ->where(function ($query) use ($account): void {
                $query->where('p.activo', true)->orWhere('p.id', $account->paciente_id);
            })
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('usuario as u')
                ->whereColumn('u.paciente_id', 'p.id')->where('u.id', '<>', $account->id))
            ->orderBy('p.apellidos')->orderBy('p.nombres')->orderBy('p.id')
            ->get(['p.id', 'p.nombres', 'p.apellidos', 'p.ci', 'p.fecha_nacimiento', 'p.activo'])
            ->mapWithKeys(fn ($patient) => [$patient->id => $patient->nombres.' '.$patient->apellidos
                .' · CI: '.($patient->ci ?: 'Sin CI').' · Nacimiento: '.$patient->fecha_nacimiento
                .($patient->activo ? '' : ' (inactivo)')])->all();
    }

    public function update(User $actor, int $id, ?int $patientId): void
    {
        Clinic::authorize($actor, 'usuarios.gestionar');
        Clinic::transaction($actor, function () use ($actor, $id, $patientId): void {
            $account = User::query()->lockForUpdate()->findOrFail($id);
            abort_unless($account->isPatient(), 404);
            if ($patientId !== null) {
                $patient = DB::table('paciente')->where('id', $patientId)->lockForUpdate()->first();
                if (! $patient || (! $patient->activo && (int) $account->paciente_id !== $patientId)) {
                    Clinic::fail('Selecciona un paciente activo. Puedes conservar el vínculo actual si está inactivo.', 'paciente_id');
                }
                if (DB::table('usuario')->where('paciente_id', $patientId)->where('id', '<>', $id)->exists()) {
                    Clinic::fail('El paciente ya está vinculado a otra cuenta.', 'paciente_id');
                }
            }
            if (($account->paciente_id === null && $patientId === null)
                || ($account->paciente_id !== null && (int) $account->paciente_id === $patientId)) {
                return;
            }
            DB::table('usuario')->where('id', $id)->update([
                'paciente_id' => $patientId,
                'paciente_verificado_por' => $patientId === null ? null : $actor->id,
                'paciente_verificado_en' => $patientId === null ? null : now(),
            ]);
        });
    }
}
