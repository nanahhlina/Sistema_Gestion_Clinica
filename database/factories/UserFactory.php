<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rol_id' => $this->roleId('Recepcionista'),
            'nombre_usuario' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= Hash::make((string) env('DEMO_PASSWORD', 'VidaSalud2026!')),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'activo' => true,
            'paciente_id' => null,
            'paciente_verificado_por' => null,
            'paciente_verificado_en' => null,
        ];
    }

    /**
     * Indicate that the account has no verified patient link.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paciente_id' => null,
            'paciente_verificado_por' => null,
            'paciente_verificado_en' => null,
        ]);
    }

    public function withRole(string $roleName): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol_id' => $this->roleId($roleName),
        ]);
    }

    public function verifiedPatient(int $patientId, int $verifiedBy): static
    {
        return $this->withRole('Paciente')->state(fn (array $attributes): array => [
            'paciente_id' => $patientId,
            'paciente_verificado_por' => $verifiedBy,
            'paciente_verificado_en' => now(),
        ]);
    }

    private function roleId(string $roleName): int
    {
        DB::table('vida_salud.rol')->insertOrIgnore(['nombre' => $roleName]);

        return (int) DB::table('vida_salud.rol')->where('nombre', $roleName)->value('id');
    }
}
