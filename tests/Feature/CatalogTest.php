<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsVidaSalud;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase, SeedsVidaSalud;

    /** @return array{ci: string, nombres: string, apellidos: string, fecha_nacimiento: string, telefono: string, activo: string} */
    private function patientPayload(): array
    {
        return [
            'ci' => 'TEST-NEW', 'nombres' => 'Carla', 'apellidos' => 'Prueba',
            'fecha_nacimiento' => '1990-02-10', 'telefono' => '70000003', 'activo' => '1',
        ];
    }

    public function test_reception_creates_a_patient_with_normalized_ci(): void
    {
        $this->seedVidaSalud();
        $payload = [...$this->patientPayload(), 'ci' => '  test-new  '];

        $response = $this->actingAs($this->account('recepcion'))
            ->post(route('catalog.store', ['module' => 'pacientes']), $payload);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('paciente', ['ci' => 'TEST-NEW', 'nombres' => 'Carla']);
    }

    public function test_duplicate_ci_is_rejected_after_normalization_without_creating_a_patient(): void
    {
        $this->seedVidaSalud();
        $before = DB::table('paciente')->count();

        $response = $this->actingAs($this->account('recepcion'))
            ->post(route('catalog.store', ['module' => 'pacientes']), [...$this->patientPayload(), 'ci' => ' test-001 ']);

        $response->assertSessionHasErrors(['operacion' => 'Ya existe un registro con ese identificador. Revisa CI, usuario, correo o nombre.']);
        $this->assertDatabaseCount('paciente', $before);
    }

    public function test_reception_can_read_specialties_but_cannot_create_them(): void
    {
        $this->seedVidaSalud();

        $this->actingAs($this->account('recepcion'))
            ->get(route('catalog.index', ['module' => 'especialidades']))->assertOk();
        $this->post(route('catalog.store', ['module' => 'especialidades']), ['nombre' => 'No autorizada', 'activo' => '1'])
            ->assertForbidden();

        $this->assertDatabaseMissing('especialidad', ['nombre' => 'No autorizada']);
    }

    public function test_admin_cannot_create_a_specialty_whose_normalized_name_exists(): void
    {
        $this->seedVidaSalud();
        $before = DB::table('especialidad')->count();

        $response = $this->actingAs($this->account('admin'))
            ->post(route('catalog.store', ['module' => 'especialidades']), ['nombre' => '  medicina general  ', 'activo' => '1']);

        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('especialidad', $before);
    }

    public function test_patient_cannot_list_all_patients(): void
    {
        $this->seedVidaSalud();

        $this->actingAs($this->account('paciente'))
            ->get(route('catalog.index', ['module' => 'pacientes']))->assertForbidden();
    }

    public function test_patient_names_are_escaped_in_catalog_html(): void
    {
        $this->seedVidaSalud();
        DB::table('paciente')->where('ci', 'TEST-001')->update(['nombres' => '<script>alert(1)</script>']);

        $response = $this->actingAs($this->account('recepcion'))->get(route('catalog.index', ['module' => 'pacientes']));

        $response->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
