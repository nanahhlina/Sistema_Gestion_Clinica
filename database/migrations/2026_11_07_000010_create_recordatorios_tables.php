<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('Vida Salud requiere PostgreSQL.');
        }

        $sql = file_get_contents(database_path('sql/2026_11_07_cu24_recordatorios.sql'));

        if ($sql === false) {
            throw new RuntimeException('No se encontro el DDL de CU24 recordatorios.');
        }

        DB::unprepared($sql);
    }

    public function down(): void
    {
        throw new RuntimeException('Las tablas de recordatorios se conservan para auditoria e historial.');
    }
};

