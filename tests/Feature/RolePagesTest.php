<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsVidaSalud;
use Tests\TestCase;

class RolePagesTest extends TestCase
{
    use RefreshDatabase, SeedsVidaSalud;

    /** @return array<string, array{string, string, array<string, string>, string}> */
    public static function allowedPages(): array
    {
        $pages = [];
        foreach (['admin', 'recepcion', 'medico', 'paciente'] as $login) {
            $pages[$login.' inicio'] = [$login, 'home', [], 'none'];
        }
        foreach (['pacientes', 'medicos', 'especialidades', 'horarios', 'usuarios', 'roles'] as $module) {
            foreach (['index', 'create', 'edit'] as $action) {
                $pages['admin '.$module.' '.$action] = ['admin', 'catalog.'.$action, ['module' => $module], $action === 'edit' ? 'catalog' : 'none'];
            }
        }
        foreach (['pacientes', 'medicos', 'especialidades', 'horarios'] as $module) {
            $pages['recepcion '.$module] = ['recepcion', 'catalog.index', ['module' => $module], 'none'];
        }
        foreach (['create', 'edit'] as $action) {
            $pages['recepcion pacientes '.$action] = ['recepcion', 'catalog.'.$action, ['module' => 'pacientes'], $action === 'edit' ? 'catalog' : 'none'];
        }
        $pages['admin bitacora'] = ['admin', 'auditoria.index', [], 'none'];

        return $pages;
    }

    /** @param array<string, string> $parameters */
    #[DataProvider('allowedPages')]
    public function test_allowed_role_page_renders_its_complete_html(string $login, string $routeName, array $parameters, string $fixture): void
    {
        $this->seedVidaSalud();
        if ($fixture === 'catalog') {
            $table = match ($parameters['module']) {
                'pacientes' => 'paciente', 'medicos' => 'medico', 'especialidades' => 'especialidad',
                'horarios' => 'horario', 'usuarios' => 'usuario', 'roles' => 'rol',
            };
            $parameters['id'] = DB::table($table)->orderBy('id')->value('id');
        }

        $response = $this->actingAs($this->account($login))->get(route($routeName, $parameters));

        $response->assertOk()->assertSee('<html', false)->assertSee('</html>', false)->assertSee('Vida Salud');
    }

    /** @return array<string, array{string, string}> */
    public static function forbiddenPages(): array
    {
        return [
            'recepcion sin bitácora' => ['recepcion', 'auditoria.index'],
            'medico sin bitácora' => ['medico', 'auditoria.index'],
            'paciente sin bitácora' => ['paciente', 'auditoria.index'],
        ];
    }

    #[DataProvider('forbiddenPages')]
    public function test_role_without_permission_gets_403(string $login, string $routeName): void
    {
        $this->seedVidaSalud();

        $this->actingAs($this->account($login))->get(route($routeName))->assertForbidden();
    }
}
