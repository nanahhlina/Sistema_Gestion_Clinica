<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsVidaSalud;
use Tests\TestCase;

class AcceptanceRulesTest extends TestCase
{
    use RefreshDatabase, SeedsVidaSalud;

    public function test_cp12_doctor_cannot_administer_users_even_through_direct_urls(): void
    {
        $this->seedVidaSalud();
        $this->actingAs($this->account('medico'))->get(route('catalog.index', ['module' => 'usuarios']))->assertForbidden();
        $this->post(route('catalog.store', ['module' => 'usuarios']), [])->assertForbidden();
        $this->assertDatabaseCount('usuario', 5);
    }
}
