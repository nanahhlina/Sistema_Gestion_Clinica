<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsVidaSalud;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, SeedsVidaSalud;

    public function test_guest_is_redirected_to_login_from_home(): void
    {
        $this->get(route('home'))->assertRedirectToRoute('login');
    }

    /** @return array<string, array{string}> */
    public static function demoAccounts(): array
    {
        return [
            'administrador' => ['admin'], 'recepción' => ['recepcion'],
            'médico' => ['medico'], 'laboratorio' => ['laboratorio'],
            'paciente verificado' => ['paciente'],
        ];
    }

    #[DataProvider('demoAccounts')]
    public function test_active_account_can_sign_in_with_its_login(string $login): void
    {
        $this->seedVidaSalud();

        $response = $this->post(route('login'), [
            'login' => $login, 'password' => env('DEMO_PASSWORD', 'VidaSalud2026!'),
        ]);

        $response->assertRedirectToRoute('home');
        $this->assertAuthenticatedAs($this->account($login));
    }

    public function test_invalid_password_does_not_authenticate(): void
    {
        $this->seedVidaSalud();

        $response = $this->from(route('login'))->post(route('login'), [
            'login' => 'admin', 'password' => 'clave-incorrecta',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        $this->seedVidaSalud();
        DB::table('usuario')->where('id', $this->account('medico')->getKey())->update(['activo' => false]);

        $response = $this->post(route('login'), [
            'login' => 'medico', 'password' => env('DEMO_PASSWORD', 'VidaSalud2026!'),
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $this->seedVidaSalud();

        $response = $this->actingAs($this->account('recepcion'))->post(route('logout'));

        $response->assertRedirectToRoute('login');
        $this->assertGuest();
    }
}
