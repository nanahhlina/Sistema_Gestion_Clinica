<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE vida_salud.pago ADD COLUMN ultima_verificacion_en timestamptz');
        DB::statement("CREATE INDEX pago_verificacion_idx ON vida_salud.pago(ultima_verificacion_en,id) WHERE metodo='PayPal'");
    }

    public function down(): void
    {
        throw new RuntimeException('La coordinación de pagos requiere revisión antes de retirarse.');
    }
};
