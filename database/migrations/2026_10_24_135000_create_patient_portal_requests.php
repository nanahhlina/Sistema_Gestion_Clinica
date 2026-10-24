<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vida_salud.solicitud_portal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('vida_salud.usuario');
            $table->string('ci_solicitado', 40)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('telefono', 40);
            $table->string('estado', 20)->default('Pendiente')->index();
            $table->timestampTz('creado_en')->useCurrent();
            $table->foreignId('resuelta_por')->nullable()->constrained('vida_salud.usuario');
            $table->timestampTz('resuelta_en')->nullable();
            $table->text('observacion')->nullable();
        });
        DB::statement("ALTER TABLE vida_salud.solicitud_portal ADD CONSTRAINT solicitud_portal_estado CHECK (estado IN ('Pendiente','Aprobada','Rechazada'))");
    }

    public function down(): void
    {
        if (DB::table('vida_salud.solicitud_portal')->exists()) {
            throw new RuntimeException('No se elimina una cola de verificación con registros.');
        }
        Schema::drop('vida_salud.solicitud_portal');
    }
};
