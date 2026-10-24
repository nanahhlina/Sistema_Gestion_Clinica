<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $password = env('DEMO_PASSWORD');

            if (! is_string($password) || $password === '') {
                if (! app()->environment(['local', 'testing'])) {
                    throw new RuntimeException('Configure DEMO_PASSWORD para crear las cuentas de demostracion fuera del entorno local.');
                }

                $password = 'VidaSalud2026!';
            }

            $users = [
                'admin' => ['rol' => 'Administrador', 'nombres' => 'Admin', 'apellidos' => 'Prueba'],
                'recepcion' => ['rol' => 'Recepcionista', 'nombres' => 'Recepcion', 'apellidos' => 'Prueba'],
                'medico' => ['rol' => 'Medico', 'nombres' => 'Dr', 'apellidos' => 'Uno'],
                'laboratorio' => ['rol' => 'Laboratorista', 'nombres' => 'Laboratorio', 'apellidos' => 'Prueba'],
                'paciente' => ['rol' => 'Paciente', 'nombres' => 'Ana', 'apellidos' => 'Prueba'],
            ];
            $userIds = [];

            foreach ($users as $username => $attributes) {
                $roleId = (int) DB::table('vida_salud.rol')->where('nombre', $attributes['rol'])->value('id');
                $user = DB::table('vida_salud.usuario')->whereRaw('lower(btrim(nombre_usuario)) = ?', [$username])->first();

                if ($user === null) {
                    $userIds[$username] = DB::table('vida_salud.usuario')->insertGetId([
                        'rol_id' => $roleId,
                        'nombre_usuario' => $username,
                        'password_hash' => Hash::make($password),
                        'nombres' => $attributes['nombres'],
                        'apellidos' => $attributes['apellidos'],
                        'correo' => $username.'@vida-salud.test',
                    ]);
                } else {
                    if ((int) $user->rol_id !== $roleId) {
                        throw new RuntimeException('La cuenta '.$username.' ya existe con otro rol; no se modifica automaticamente.');
                    }

                    $userIds[$username] = (int) $user->id;
                }
            }

            $previousActor = DB::selectOne("SELECT current_setting('app.usuario_id', true) AS actor")->actor;
            DB::selectOne("SELECT set_config('app.usuario_id', ?, true)", [(string) $userIds['admin']]);

            try {
                $this->seedClinicalDirectory($userIds);
            } finally {
                DB::selectOne("SELECT set_config('app.usuario_id', ?, true)", [$previousActor ?? '']);
            }
        });
    }

    /**
     * @param  array<string, int>  $userIds
     */
    private function seedClinicalDirectory(array $userIds): void
    {
        DB::table('vida_salud.paciente')->insertOrIgnore([
            'ci' => 'TEST-001',
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'fecha_nacimiento' => '1995-05-15',
            'sexo' => 'Femenino',
            'telefono' => '70000001',
            'correo' => 'paciente@vida-salud.test',
        ]);
        $patientId = (int) DB::table('vida_salud.paciente')->whereRaw('upper(btrim(ci)) = ?', ['TEST-001'])->value('id');
        $patientAccount = DB::table('vida_salud.usuario')->where('id', $userIds['paciente'])->first();

        if ($patientAccount->paciente_id !== null && (int) $patientAccount->paciente_id !== $patientId) {
            throw new RuntimeException('La cuenta paciente ya esta vinculada a otra persona; no se modifica automaticamente.');
        }

        if ($patientAccount->paciente_id === null) {
            DB::table('vida_salud.usuario')->where('id', $userIds['paciente'])->update([
                'paciente_id' => $patientId,
                'paciente_verificado_por' => $userIds['admin'],
                'paciente_verificado_en' => now(),
            ]);
        }

        DB::table('vida_salud.medico')->insertOrIgnore([
            'usuario_id' => $userIds['medico'],
            'registro_profesional' => 'MED-TEST-01',
            'telefono' => '70000002',
        ]);
        $doctor = DB::table('vida_salud.medico')->where('usuario_id', $userIds['medico'])->first();

        if ($doctor === null || $doctor->registro_profesional !== 'MED-TEST-01') {
            throw new RuntimeException('El medico de demostracion tiene un registro incompatible; no se modifica automaticamente.');
        }

        DB::table('vida_salud.especialidad')->insertOrIgnore(['nombre' => 'Medicina General']);
        $specialtyId = (int) DB::table('vida_salud.especialidad')->whereRaw('lower(btrim(nombre)) = ?', ['medicina general'])->value('id');
        DB::table('vida_salud.medico_especialidad')->insertOrIgnore([
            'medico_id' => $doctor->id,
            'especialidad_id' => $specialtyId,
        ]);

        foreach (range(1, 5) as $weekday) {
            if (! DB::table('vida_salud.horario')->where('medico_id', $doctor->id)->where('dia_semana', $weekday)->exists()) {
                DB::table('vida_salud.horario')->insert([
                    'medico_id' => $doctor->id,
                    'dia_semana' => $weekday,
                    'hora_inicio' => '08:00:00',
                    'hora_fin' => '12:00:00',
                ]);
            }
        }

        foreach (['Hemograma completo', 'Glucosa en sangre', 'Examen general de orina'] as $analysisName) {
            DB::table('vida_salud.analisis_laboratorio')->insertOrIgnore(['nombre' => $analysisName]);
        }
    }
}
