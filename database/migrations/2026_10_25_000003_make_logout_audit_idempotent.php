<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vida_salud.bitacora', function (Blueprint $table): void {
            $table->string('clave_operacion', 80)->nullable();
            $table->unique('clave_operacion', 'bitacora_clave_operacion_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('vida_salud.bitacora')->whereNotNull('clave_operacion')->exists()) {
            throw new RuntimeException('No se retira la protección de idempotencia de una bitácora con operaciones registradas.');
        }

        Schema::table('vida_salud.bitacora', function (Blueprint $table): void {
            $table->dropUnique('bitacora_clave_operacion_unique');
            $table->dropColumn('clave_operacion');
        });
    }
};
