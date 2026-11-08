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

        $sql = file_get_contents(database_path('sql/2026_11_07_cu21_cu26.sql'));

        if ($sql === false) {
            throw new RuntimeException('No se encontro el DDL de CU21-CU26.');
        }

        // CREATE sin IF NOT EXISTS: cualquier tabla previa requiere comparar su estructura.
        DB::unprepared($sql);
    }

    public function down(): void
    {
        throw new RuntimeException('Las tablas de pagos e inventario se conservan. La reversion requiere revision expresa para evitar perdida de datos.');
    }
};
