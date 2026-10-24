<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION vida_salud.validar_resultado() RETURNS trigger
LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
DECLARE orden bigint; estado_actual text; solicitante bigint; actor bigint; cambio_clinico boolean;
BEGIN
 IF TG_OP='UPDATE' AND NEW.orden_analisis_id<>OLD.orden_analisis_id THEN
  RAISE EXCEPTION 'No se puede trasladar el resultado a otra orden';
 END IF;
 SELECT orden_id INTO orden FROM orden_analisis WHERE id=NEW.orden_analisis_id;
 SELECT estado,solicitada_por INTO estado_actual,solicitante FROM orden_laboratorio WHERE id=orden FOR UPDATE;
 IF estado_actual IS NULL OR estado_actual='Cancelada' THEN RAISE EXCEPTION 'Orden inexistente o cancelada'; END IF;
 cambio_clinico := TG_OP='INSERT';
 IF TG_OP='UPDATE' THEN
  cambio_clinico := ROW(NEW.resultado,NEW.observaciones,NEW.archivo_referencia,NEW.laboratorista_id)
   IS DISTINCT FROM ROW(OLD.resultado,OLD.observaciones,OLD.archivo_referencia,OLD.laboratorista_id);
 END IF;
 IF cambio_clinico THEN
  PERFORM exigir_permiso(NEW.laboratorista_id,'laboratorio.procesar');
 ELSE
  IF NEW.publicado_en IS NOT NULL AND ROW(NEW.publicado_en,NEW.publicado_por) IS DISTINCT FROM ROW(OLD.publicado_en,OLD.publicado_por) THEN
   actor := nullif(current_setting('app.usuario_id',true),'')::bigint;
   IF actor IS DISTINCT FROM NEW.publicado_por OR actor IS DISTINCT FROM solicitante THEN
    RAISE EXCEPTION 'Solo el medico solicitante puede publicar resultados';
   END IF;
   IF estado_actual<>'Completada' THEN RAISE EXCEPTION 'La orden debe estar completada antes de publicar'; END IF;
   PERFORM exigir_permiso(actor,'laboratorio.solicitar');
  END IF;
 END IF;
 RETURN NEW;
END; $$;
SQL);
    }

    public function down(): void
    {
        $dump = file_get_contents(database_path('vida_salud_existing.sql'));
        if ($dump === false || preg_match('/^CREATE FUNCTION vida_salud\\.validar_resultado\\(\\).*?\\$\\$;/ms', $dump, $matches) !== 1) {
            throw new RuntimeException('No se encontró la función original de resultados.');
        }
        DB::unprepared(preg_replace('/^CREATE FUNCTION/', 'CREATE OR REPLACE FUNCTION', $matches[0]));
    }
};
