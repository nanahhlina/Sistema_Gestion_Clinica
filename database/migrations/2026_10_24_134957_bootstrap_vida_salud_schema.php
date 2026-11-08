<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('Vida Salud requiere PostgreSQL.');
        }

        if (DB::selectOne("SELECT to_regclass('vida_salud.paciente') AS tabla")->tabla !== null) {
            return;
        }

        $dump = file_get_contents(database_path('vida_salud_existing.sql'));

        if ($dump === false) {
            throw new RuntimeException('No se pudo leer el esquema original de Vida Salud.');
        }

        $dump = preg_replace('/^\\\\(?:un)?restrict[^\r\n]*(?:\r?\n|$)/m', '', $dump);
        $dump = str_replace('CREATE SCHEMA vida_salud;', 'CREATE SCHEMA IF NOT EXISTS vida_salud;', $dump);
        $searchPath = DB::selectOne('SHOW search_path')->search_path;

        try {
            DB::unprepared('CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public');
            DB::unprepared($dump);
        } finally {
            DB::selectOne("SELECT set_config('search_path', ?, false)", [$searchPath]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('El esquema clinico original no se elimina automaticamente. Restaure una copia de seguridad si necesita revertirlo.');
    }
};
