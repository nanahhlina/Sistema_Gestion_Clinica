<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['paciente', 'parentesco_emergencia', 'character varying(60)'],
            ['paciente', 'tipo_sangre', 'character varying(10)'],
            ['historia_clinica', 'enfermedades_cronicas', 'text'],
            ['historia_clinica', 'medicamentos_actuales', 'text'],
            ['cita', 'precio_consulta', 'numeric(12,2)'],
        ] as [$table, $column, $type]) {
            $existing = DB::selectOne('SELECT format_type(a.atttypid,a.atttypmod) AS type, a.attnotnull, a.attgenerated, pg_get_expr(d.adbin,d.adrelid) AS default_value FROM pg_attribute a LEFT JOIN pg_attrdef d ON d.adrelid=a.attrelid AND d.adnum=a.attnum WHERE a.attrelid=to_regclass(?) AND a.attname=? AND NOT a.attisdropped', ['vida_salud.'.$table, $column]);
            if ($existing) {
                if ($existing->type !== $type || $existing->attnotnull || $existing->attgenerated !== '' || $existing->default_value !== null) {
                    throw new RuntimeException("Estructura incompatible: {$table}.{$column}; revisar antes de adoptar.");
                }
            } else {
                DB::statement("ALTER TABLE vida_salud.{$table} ADD COLUMN {$column} {$type} NULL");
            }
        }
        $constraint = DB::selectOne("SELECT pg_get_constraintdef(oid) AS definition, convalidated FROM pg_constraint WHERE conrelid='vida_salud.cita'::regclass AND conname='cita_precio_consulta_no_negativo'");
        if ($constraint && (! $constraint->convalidated || $constraint->definition !== 'CHECK (((precio_consulta IS NULL) OR (precio_consulta >= (0)::numeric)))')) {
            throw new RuntimeException('Restricción de precio incompatible; revisar antes de adoptar.');
        }
        if (! $constraint) {
            DB::statement('ALTER TABLE vida_salud.cita ADD CONSTRAINT cita_precio_consulta_no_negativo CHECK (precio_consulta IS NULL OR precio_consulta >= 0)');
        }
        DB::statement("ALTER TABLE vida_salud.cita ADD CONSTRAINT cita_precio_consulta_finito CHECK (precio_consulta IS NULL OR precio_consulta <> 'NaN'::numeric)");
    }

    public function down(): void
    {
        throw new RuntimeException('Los campos adoptados se conservan; su retirada requiere revisión expresa.');
    }
};
