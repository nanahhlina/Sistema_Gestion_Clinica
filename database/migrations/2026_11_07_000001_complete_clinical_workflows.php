<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION vida_salud.validar_resultado() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud,public AS $$
            DECLARE orden bigint; estado_actual text; actor bigint; cambio_clinico boolean;
            BEGIN
                IF TG_OP='UPDATE' AND NEW.orden_analisis_id<>OLD.orden_analisis_id THEN
                    RAISE EXCEPTION 'No se puede trasladar el resultado a otra orden';
                END IF;
                SELECT orden_id INTO orden FROM orden_analisis WHERE id=NEW.orden_analisis_id;
                SELECT estado INTO estado_actual FROM orden_laboratorio WHERE id=orden FOR UPDATE;
                IF estado_actual IS NULL OR estado_actual='Cancelada' THEN RAISE EXCEPTION 'Orden inexistente o cancelada'; END IF;
                cambio_clinico:=TG_OP='INSERT';
                IF TG_OP='UPDATE' THEN
                    cambio_clinico:=ROW(NEW.resultado,NEW.observaciones,NEW.archivo_referencia,NEW.laboratorista_id)
                        IS DISTINCT FROM ROW(OLD.resultado,OLD.observaciones,OLD.archivo_referencia,OLD.laboratorista_id);
                END IF;
                IF cambio_clinico THEN PERFORM exigir_permiso(NEW.laboratorista_id,'laboratorio.procesar');
                ELSIF ROW(NEW.publicado_en,NEW.publicado_por) IS DISTINCT FROM ROW(OLD.publicado_en,OLD.publicado_por) THEN
                    actor:=nullif(current_setting('app.usuario_id',true),'')::bigint;
                    PERFORM exigir_permiso(actor,'laboratorio.procesar');
                    IF NOT EXISTS(SELECT 1 FROM usuario u JOIN rol r ON r.id=u.rol_id WHERE u.id=actor AND r.nombre='Laboratorista')
                        OR (NEW.publicado_por IS NOT NULL AND NEW.publicado_por IS DISTINCT FROM actor) THEN
                        RAISE EXCEPTION 'Solo laboratorio puede publicar o retirar resultados';
                    END IF;
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TABLE vida_salud.operacion_web (
                usuario_id bigint NOT NULL REFERENCES vida_salud.usuario(id),
                clave uuid NOT NULL, operacion varchar(100) NOT NULL,
                recurso_id bigint NOT NULL, creada_en timestamptz NOT NULL DEFAULT current_timestamp,
                PRIMARY KEY(usuario_id, clave)
            );
            CREATE FUNCTION vida_salud.reservar_cita_propia(p_usuario bigint, p_medico bigint,
                p_especialidad bigint, p_inicio timestamptz, p_motivo text DEFAULT NULL) RETURNS bigint
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE paciente bigint; identificador bigint;
            BEGIN
                PERFORM exigir_permiso(p_usuario, 'citas.propias');
                SELECT u.paciente_id INTO paciente FROM usuario u JOIN rol r ON r.id=u.rol_id
                    JOIN vida_salud.paciente p ON p.id=u.paciente_id
                    WHERE u.id=p_usuario AND u.activo AND r.activo AND r.nombre='Paciente'
                        AND u.paciente_verificado_en IS NOT NULL AND p.activo FOR SHARE OF u,p,r;
                IF paciente IS NULL THEN RAISE EXCEPTION 'La cuenta necesita un paciente activo y un vinculo verificado por recepcion'; END IF;
                INSERT INTO cita(paciente_id,medico_id,especialidad_id,inicio,motivo_consulta,creada_por,modificada_por)
                    VALUES(paciente,p_medico,p_especialidad,p_inicio,p_motivo,p_usuario,p_usuario) RETURNING id INTO identificador;
                RETURN identificador;
            END; $$;
            CREATE FUNCTION vida_salud.cambiar_asistencia(p_usuario bigint,p_cita bigint,p_estado text) RETURNS bigint
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE c cita%ROWTYPE;
            BEGIN
                PERFORM exigir_permiso(p_usuario,'citas.gestionar');
                SELECT * INTO c FROM cita WHERE id=p_cita FOR UPDATE;
                IF NOT FOUND THEN RAISE EXCEPTION 'Cita inexistente'; END IF;
                IF NOT ((p_estado='Confirmada' AND c.estado='Programada') OR
                    (p_estado='Inasistencia' AND c.estado IN ('Programada','Confirmada') AND c.fin<=current_timestamp)) THEN
                    RAISE EXCEPTION 'La cita no admite esa transicion de asistencia';
                END IF;
                UPDATE cita SET estado=p_estado,modificada_por=p_usuario WHERE id=p_cita;
                RETURN p_cita;
            END; $$;
            CREATE FUNCTION vida_salud.validar_publicacion_web() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF NEW.publicado_por IS NOT NULL AND
                    (TG_OP='INSERT' OR ROW(NEW.publicado_por,NEW.publicado_en) IS DISTINCT FROM ROW(OLD.publicado_por,OLD.publicado_en)) THEN
                    PERFORM exigir_permiso(NEW.publicado_por,'laboratorio.procesar');
                    IF NOT EXISTS(SELECT 1 FROM usuario u JOIN rol r ON r.id=u.rol_id
                        WHERE u.id=NEW.publicado_por AND r.nombre='Laboratorista') THEN
                        RAISE EXCEPTION 'Solo laboratorio puede publicar resultados';
                    END IF;
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER zz_publicacion_web BEFORE INSERT OR UPDATE ON vida_salud.resultado_laboratorio
                FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_publicacion_web();
        SQL);
    }

    public function down(): void
    {
        if (DB::table('vida_salud.operacion_web')->exists()) {
            throw new RuntimeException('Conserve el registro de operaciones; no revierta una instalación utilizada.');
        }
        DB::unprepared('DROP TRIGGER zz_publicacion_web ON vida_salud.resultado_laboratorio;
            DROP FUNCTION vida_salud.validar_publicacion_web();
            DROP FUNCTION vida_salud.cambiar_asistencia(bigint,bigint,text);
            DROP FUNCTION vida_salud.reservar_cita_propia(bigint,bigint,bigint,timestamptz,text);
            DROP TABLE vida_salud.operacion_web;');
        (require __DIR__.'/2026_10_24_135001_correct_laboratory_publication_audit.php')->up();
    }
};
