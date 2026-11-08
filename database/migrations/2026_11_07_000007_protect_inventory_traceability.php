<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION vida_salud.conservar_movimiento() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Los movimientos son inmutables. Registra un movimiento compensatorio con motivo';
            END; $$;
            CREATE TRIGGER conservar_movimiento BEFORE UPDATE OR DELETE ON vida_salud.movimiento_inventario
                FOR EACH ROW EXECUTE FUNCTION vida_salud.conservar_movimiento();
            CREATE FUNCTION vida_salud.proteger_identidad_lote() RETURNS trigger LANGUAGE plpgsql SET search_path TO vida_salud,public AS $$
            BEGIN
                IF ROW(NEW.producto_id,NEW.numero_lote,NEW.fecha_vencimiento) IS DISTINCT FROM ROW(OLD.producto_id,OLD.numero_lote,OLD.fecha_vencimiento)
                    AND EXISTS(SELECT 1 FROM movimiento_inventario WHERE lote_id=OLD.id) THEN
                    RAISE EXCEPTION 'El producto, número y vencimiento de un lote con movimientos se conservan';
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER proteger_identidad_lote BEFORE UPDATE ON vida_salud.lote
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_identidad_lote();
            CREATE FUNCTION vida_salud.auditar_lote() RETURNS trigger LANGUAGE plpgsql SET search_path TO vida_salud,public AS $$
            DECLARE datos jsonb;
            BEGIN
                IF TG_OP='DELETE' THEN datos:=to_jsonb(OLD); ELSE datos:=to_jsonb(NEW); END IF;
                INSERT INTO bitacora(usuario_id,accion,entidad,registro_id,descripcion,direccion_ip)
                    VALUES(nullif(current_setting('app.usuario_id',true),'')::bigint,TG_OP,'lote',(datos->>'id')::bigint,
                        jsonb_build_object('anterior',CASE WHEN TG_OP<>'INSERT' THEN to_jsonb(OLD) ELSE NULL END,
                            'nuevo',CASE WHEN TG_OP<>'DELETE' THEN to_jsonb(NEW) ELSE NULL END,
                            'motivo',nullif(current_setting('app.motivo_lote',true),''))::text,
                        nullif(current_setting('app.direccion_ip',true),'')::inet);
                IF TG_OP='DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END; $$;
            DROP TRIGGER auditar ON vida_salud.lote;
            CREATE TRIGGER auditar AFTER INSERT OR UPDATE OR DELETE ON vida_salud.lote
                FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_lote();
            SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Las protecciones del inventario y su auditoría requieren revisión expresa antes de retirarse.');
    }
};
