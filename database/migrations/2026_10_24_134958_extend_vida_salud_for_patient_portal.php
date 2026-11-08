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
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION vida_salud.auditar_cambio() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE actor bigint; datos jsonb;
            BEGIN
                actor := nullif(current_setting('app.usuario_id', true), '')::bigint;
                IF TG_OP = 'DELETE' THEN datos := to_jsonb(OLD); ELSE datos := to_jsonb(NEW); END IF;
                INSERT INTO bitacora(usuario_id, accion, entidad, registro_id, descripcion, direccion_ip)
                VALUES (actor, TG_OP, TG_TABLE_NAME, coalesce((datos->>'id')::bigint, (datos->>'paciente_id')::bigint),
                    'Cambio de registro', nullif(current_setting('app.direccion_ip', true), '')::inet);
                IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END;
            $$;

            ALTER TABLE vida_salud.usuario
                ADD COLUMN paciente_id bigint UNIQUE REFERENCES vida_salud.paciente(id),
                ADD COLUMN paciente_verificado_por bigint REFERENCES vida_salud.usuario(id),
                ADD COLUMN paciente_verificado_en timestamptz,
                ADD CONSTRAINT usuario_paciente_verificacion_check CHECK (
                    (paciente_id IS NULL AND paciente_verificado_por IS NULL AND paciente_verificado_en IS NULL)
                    OR (paciente_id IS NOT NULL AND paciente_verificado_por IS NOT NULL AND paciente_verificado_en IS NOT NULL)
                );

            CREATE UNIQUE INDEX usuario_correo_unico ON vida_salud.usuario (lower(btrim(correo)))
                WHERE nullif(btrim(correo), '') IS NOT NULL;

            ALTER TABLE vida_salud.cita
                ADD COLUMN codigo varchar(30) GENERATED ALWAYS AS (
                    'VS-' || lpad(id::text, greatest(8, length(id::text)), '0')
                ) STORED UNIQUE,
                ADD CONSTRAINT cita_paciente_solapamiento_excl EXCLUDE USING gist (
                    paciente_id WITH =, tstzrange(inicio, fin, '[)') WITH &&
                ) WHERE (estado IN ('Programada', 'Confirmada', 'Atendida'));

            ALTER TABLE vida_salud.resultado_laboratorio
                ADD COLUMN publicado_por bigint REFERENCES vida_salud.usuario(id),
                ADD COLUMN publicado_en timestamptz,
                ADD CONSTRAINT resultado_publicacion_check CHECK (
                    (publicado_por IS NULL) = (publicado_en IS NULL)
                );

            CREATE FUNCTION vida_salud.retirar_publicacion_corregida() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF ROW(NEW.resultado, NEW.observaciones, NEW.archivo_referencia)
                    IS DISTINCT FROM ROW(OLD.resultado, OLD.observaciones, OLD.archivo_referencia) THEN
                    NEW.publicado_por := NULL;
                    NEW.publicado_en := NULL;
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER resultado_retirar_publicacion BEFORE UPDATE ON vida_salud.resultado_laboratorio
                FOR EACH ROW EXECUTE FUNCTION vida_salud.retirar_publicacion_corregida();

            CREATE OR REPLACE FUNCTION vida_salud.versionar_resultado() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF ROW(NEW.resultado, NEW.observaciones, NEW.archivo_referencia)
                    IS DISTINCT FROM ROW(OLD.resultado, OLD.observaciones, OLD.archivo_referencia) THEN
                    INSERT INTO resultado_version(resultado_id, valor_anterior, observaciones_anteriores,
                        archivo_anterior, autor_anterior, corregido_por)
                    VALUES (OLD.id, OLD.resultado, OLD.observaciones, OLD.archivo_referencia,
                        OLD.laboratorista_id, NEW.laboratorista_id);
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE FUNCTION vida_salud.validar_vinculo_usuario() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE nombre_rol text;
            BEGIN
                SELECT nombre INTO nombre_rol FROM rol WHERE id = NEW.rol_id FOR SHARE;
                IF NEW.paciente_id IS NOT NULL AND nombre_rol IS DISTINCT FROM 'Paciente' THEN
                    RAISE EXCEPTION 'Solo una cuenta con rol Paciente puede vincularse a un paciente';
                END IF;
                IF EXISTS (SELECT 1 FROM medico WHERE usuario_id = NEW.id)
                    AND (NEW.paciente_id IS NOT NULL OR nombre_rol IS DISTINCT FROM 'Medico') THEN
                    RAISE EXCEPTION 'Una cuenta de medico debe conservar el rol Medico y no puede vincularse a un paciente';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER usuario_vinculo_integridad BEFORE INSERT OR UPDATE ON vida_salud.usuario
                FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_vinculo_usuario();

            CREATE FUNCTION vida_salud.validar_vinculo_medico() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE cuenta usuario%ROWTYPE; nombre_rol text;
            BEGIN
                SELECT * INTO cuenta FROM usuario WHERE id = NEW.usuario_id FOR UPDATE;
                SELECT nombre INTO nombre_rol FROM rol WHERE id = cuenta.rol_id FOR SHARE;
                IF cuenta.paciente_id IS NOT NULL OR nombre_rol IS DISTINCT FROM 'Medico' THEN
                    RAISE EXCEPTION 'El medico requiere una cuenta con rol Medico sin vinculo de paciente';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER medico_vinculo_integridad BEFORE INSERT OR UPDATE ON vida_salud.medico
                FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_vinculo_medico();

            CREATE FUNCTION vida_salud.proteger_rol_vinculado() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF NEW.nombre IS DISTINCT FROM OLD.nombre AND EXISTS (
                    SELECT 1 FROM usuario u WHERE u.rol_id = OLD.id
                        AND (u.paciente_id IS NOT NULL OR EXISTS (SELECT 1 FROM medico m WHERE m.usuario_id = u.id))
                ) THEN
                    RAISE EXCEPTION 'No se puede renombrar un rol con cuentas de paciente o medico vinculadas';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER rol_vinculo_integridad BEFORE UPDATE ON vida_salud.rol
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_rol_vinculado();

            CREATE FUNCTION vida_salud.proteger_disponibilidad_citas() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE hay_citas boolean := false;
            BEGIN
                IF TG_TABLE_NAME = 'usuario' THEN
                    IF (NOT OLD.activo OR NEW.activo) AND NEW.rol_id = OLD.rol_id THEN RETURN NEW; END IF;
                    SELECT EXISTS (
                        SELECT 1 FROM cita c JOIN medico m ON m.id = c.medico_id
                        WHERE m.usuario_id = OLD.id AND c.inicio > current_timestamp
                            AND c.estado IN ('Programada', 'Confirmada')
                    ) INTO hay_citas;
                ELSE
                    IF NOT OLD.activo OR NEW.activo THEN RETURN NEW; END IF;
                    IF TG_TABLE_NAME = 'paciente' THEN
                        SELECT EXISTS (SELECT 1 FROM cita WHERE paciente_id = OLD.id
                            AND inicio > current_timestamp AND estado IN ('Programada', 'Confirmada')) INTO hay_citas;
                    ELSIF TG_TABLE_NAME = 'medico' THEN
                        SELECT EXISTS (SELECT 1 FROM cita WHERE medico_id = OLD.id
                            AND inicio > current_timestamp AND estado IN ('Programada', 'Confirmada')) INTO hay_citas;
                    ELSIF TG_TABLE_NAME = 'especialidad' THEN
                        SELECT EXISTS (SELECT 1 FROM cita WHERE especialidad_id = OLD.id
                            AND inicio > current_timestamp AND estado IN ('Programada', 'Confirmada')) INTO hay_citas;
                    ELSIF TG_TABLE_NAME = 'medico_especialidad' THEN
                        SELECT EXISTS (SELECT 1 FROM cita WHERE medico_id = OLD.medico_id AND especialidad_id = OLD.especialidad_id
                            AND inicio > current_timestamp AND estado IN ('Programada', 'Confirmada')) INTO hay_citas;
                    ELSIF TG_TABLE_NAME = 'rol' THEN
                        SELECT EXISTS (
                            SELECT 1 FROM cita c JOIN medico m ON m.id = c.medico_id JOIN usuario u ON u.id = m.usuario_id
                            WHERE u.rol_id = OLD.id AND c.inicio > current_timestamp
                                AND c.estado IN ('Programada', 'Confirmada')
                        ) INTO hay_citas;
                    END IF;
                END IF;
                IF hay_citas THEN
                    RAISE EXCEPTION 'No se puede desactivar ni cambiar el rol: existen citas futuras programadas o confirmadas';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER paciente_disponibilidad BEFORE UPDATE ON vida_salud.paciente
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();
            CREATE TRIGGER medico_disponibilidad BEFORE UPDATE ON vida_salud.medico
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();
            CREATE TRIGGER especialidad_disponibilidad BEFORE UPDATE ON vida_salud.especialidad
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();
            CREATE TRIGGER medico_especialidad_disponibilidad BEFORE UPDATE ON vida_salud.medico_especialidad
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();
            CREATE TRIGGER usuario_disponibilidad BEFORE UPDATE ON vida_salud.usuario
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();
            CREATE TRIGGER rol_disponibilidad BEFORE UPDATE ON vida_salud.rol
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_disponibilidad_citas();

            CREATE FUNCTION vida_salud.bloquear_disponibilidad_cita() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF TG_OP = 'UPDATE' THEN
                    IF NEW.inicio IS NOT DISTINCT FROM OLD.inicio AND NEW.fin IS NOT DISTINCT FROM OLD.fin THEN
                        RETURN NEW;
                    END IF;
                END IF;
                PERFORM 1 FROM medico WHERE id = NEW.medico_id FOR UPDATE;
                PERFORM 1 FROM paciente WHERE id = NEW.paciente_id FOR SHARE;
                PERFORM 1 FROM usuario u JOIN medico m ON m.usuario_id = u.id
                    WHERE m.id = NEW.medico_id FOR SHARE OF u;
                PERFORM 1 FROM rol r JOIN usuario u ON u.rol_id = r.id JOIN medico m ON m.usuario_id = u.id
                    WHERE m.id = NEW.medico_id AND r.activo AND r.nombre = 'Medico' FOR SHARE OF r;
                IF NOT FOUND THEN RAISE EXCEPTION 'El medico requiere un rol Medico activo'; END IF;
                PERFORM 1 FROM especialidad WHERE id = NEW.especialidad_id FOR SHARE;
                PERFORM 1 FROM medico_especialidad
                    WHERE medico_id = NEW.medico_id AND especialidad_id = NEW.especialidad_id FOR SHARE;
                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER aa_cita_bloquear_disponibilidad BEFORE INSERT OR UPDATE ON vida_salud.cita
                FOR EACH ROW EXECUTE FUNCTION vida_salud.bloquear_disponibilidad_cita();
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER aa_cita_bloquear_disponibilidad ON vida_salud.cita;
            DROP TRIGGER paciente_disponibilidad ON vida_salud.paciente;
            DROP TRIGGER medico_disponibilidad ON vida_salud.medico;
            DROP TRIGGER especialidad_disponibilidad ON vida_salud.especialidad;
            DROP TRIGGER medico_especialidad_disponibilidad ON vida_salud.medico_especialidad;
            DROP TRIGGER usuario_disponibilidad ON vida_salud.usuario;
            DROP TRIGGER rol_disponibilidad ON vida_salud.rol;
            DROP TRIGGER rol_vinculo_integridad ON vida_salud.rol;
            DROP TRIGGER medico_vinculo_integridad ON vida_salud.medico;
            DROP TRIGGER usuario_vinculo_integridad ON vida_salud.usuario;
            DROP TRIGGER resultado_retirar_publicacion ON vida_salud.resultado_laboratorio;
            DROP FUNCTION vida_salud.bloquear_disponibilidad_cita();
            DROP FUNCTION vida_salud.proteger_disponibilidad_citas();
            DROP FUNCTION vida_salud.proteger_rol_vinculado();
            DROP FUNCTION vida_salud.validar_vinculo_medico();
            DROP FUNCTION vida_salud.validar_vinculo_usuario();
            DROP FUNCTION vida_salud.retirar_publicacion_corregida();
            ALTER TABLE vida_salud.resultado_laboratorio
                DROP CONSTRAINT resultado_publicacion_check, DROP COLUMN publicado_por, DROP COLUMN publicado_en;
            ALTER TABLE vida_salud.cita DROP CONSTRAINT cita_paciente_solapamiento_excl, DROP COLUMN codigo;
            DROP INDEX vida_salud.usuario_correo_unico;
            ALTER TABLE vida_salud.usuario DROP CONSTRAINT usuario_paciente_verificacion_check,
                DROP COLUMN paciente_id, DROP COLUMN paciente_verificado_por, DROP COLUMN paciente_verificado_en;
            SQL);

        $this->restoreOriginalFunctions();
    }

    private function restoreOriginalFunctions(): void
    {
        $dump = file_get_contents(database_path('vida_salud_existing.sql'));

        if ($dump === false) {
            throw new RuntimeException('No se pudo leer el esquema original para restaurar sus funciones.');
        }

        foreach (['auditar_cambio', 'versionar_resultado'] as $function) {
            if (preg_match('/^CREATE FUNCTION vida_salud\.'.preg_quote($function, '/').'\(\).*?\$\$;/ms', $dump, $matches) !== 1) {
                throw new RuntimeException('No se encontro la definicion original de '.$function.'.');
            }

            DB::unprepared(preg_replace('/^CREATE FUNCTION/', 'CREATE OR REPLACE FUNCTION', $matches[0]));
        }
    }
};
