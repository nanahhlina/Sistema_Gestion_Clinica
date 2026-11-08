<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE vida_salud.horario
                ADD COLUMN duracion_minutos smallint NOT NULL DEFAULT 30,
                ADD CONSTRAINT horario_duracion_check CHECK (duracion_minutos IN (15, 30, 45, 60)),
                DROP CONSTRAINT horario_check1,
                ADD CONSTRAINT horario_check1 CHECK (
                    extract(epoch FROM (hora_fin - hora_inicio)) % (duracion_minutos * 60) = 0
                );

            ALTER TABLE vida_salud.cita
                ADD COLUMN duracion_minutos smallint NOT NULL DEFAULT 30,
                ADD CONSTRAINT cita_duracion_check CHECK (duracion_minutos IN (15, 30, 45, 60)),
                DROP CONSTRAINT cita_check,
                ADD CONSTRAINT cita_check CHECK (fin = inicio + make_interval(mins => duracion_minutos));

            -- Existing events are immutable. NULL endings identify the original 30-minute history.
            ALTER TABLE vida_salud.evento_cita
                ADD COLUMN fin_anterior timestamptz,
                ADD COLUMN fin_nuevo timestamptz;

            CREATE OR REPLACE FUNCTION vida_salud.horarios_disponibles(p_medico bigint, p_fecha date)
            RETURNS TABLE(inicio timestamptz, fin timestamptz)
            LANGUAGE sql STABLE SET search_path TO vida_salud, public AS $$
                SELECT b.inicio, b.inicio + make_interval(mins => h.duracion_minutos)
                FROM horario h
                JOIN medico m ON m.id = h.medico_id
                JOIN usuario u ON u.id = m.usuario_id
                JOIN rol r ON r.id = u.rol_id
                CROSS JOIN LATERAL generate_series(
                    (p_fecha + h.hora_inicio) AT TIME ZONE 'America/La_Paz',
                    ((p_fecha + h.hora_fin) AT TIME ZONE 'America/La_Paz')
                        - make_interval(mins => h.duracion_minutos),
                    make_interval(mins => h.duracion_minutos)
                ) AS b(inicio)
                WHERE h.medico_id = p_medico AND h.dia_semana = extract(isodow FROM p_fecha)
                    AND h.activo AND m.activo AND u.activo AND r.activo AND r.nombre = 'Medico'
                    AND b.inicio > current_timestamp
                    AND ((b.inicio + make_interval(mins => h.duracion_minutos))
                        AT TIME ZONE 'America/La_Paz')::date = p_fecha
                    AND NOT EXISTS (
                        SELECT 1 FROM cita c WHERE c.medico_id = p_medico
                            AND c.estado IN ('Programada', 'Confirmada', 'Atendida')
                            AND tstzrange(c.inicio, c.fin, '[)') && tstzrange(
                                b.inicio, b.inicio + make_interval(mins => h.duracion_minutos), '[)'
                            )
                    )
                ORDER BY b.inicio;
            $$;

            CREATE OR REPLACE FUNCTION vida_salud.agendar_cita(
                p_usuario bigint, p_paciente bigint, p_medico bigint, p_especialidad bigint,
                p_inicio timestamptz, p_motivo text DEFAULT NULL
            ) RETURNS bigint
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE identificador bigint;
            BEGIN
                PERFORM exigir_permiso(p_usuario, 'citas.gestionar');
                -- The validation trigger derives fin and duration from the locked active schedule.
                INSERT INTO cita(paciente_id, medico_id, especialidad_id, inicio,
                    motivo_consulta, creada_por, modificada_por)
                VALUES(p_paciente, p_medico, p_especialidad, p_inicio, p_motivo, p_usuario, p_usuario)
                RETURNING id INTO identificador;
                RETURN identificador;
            END;
            $$;

            CREATE OR REPLACE FUNCTION vida_salud.reprogramar_cita(
                p_usuario bigint, p_cita bigint, p_inicio timestamptz
            ) RETURNS void
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                PERFORM exigir_permiso(p_usuario, 'citas.gestionar');
                UPDATE cita SET inicio = p_inicio, estado = 'Programada', modificada_por = p_usuario
                WHERE id = p_cita;
                IF NOT FOUND THEN RAISE EXCEPTION 'Cita inexistente'; END IF;
            END;
            $$;

            CREATE OR REPLACE FUNCTION vida_salud.validar_cita() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE fecha_local timestamp; validar_reserva boolean; minutos integer;
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    IF NEW.estado <> 'Programada' THEN
                        RAISE EXCEPTION 'Una cita nueva debe estar Programada';
                    END IF;
                    validar_reserva := true;
                ELSE
                    IF NEW.paciente_id IS DISTINCT FROM OLD.paciente_id
                        OR NEW.medico_id IS DISTINCT FROM OLD.medico_id
                        OR NEW.especialidad_id IS DISTINCT FROM OLD.especialidad_id THEN
                        RAISE EXCEPTION 'Para cambiar paciente, medico o especialidad, cancele y cree otra cita';
                    END IF;
                    validar_reserva := NEW.inicio IS DISTINCT FROM OLD.inicio
                        OR NEW.fin IS DISTINCT FROM OLD.fin
                        OR NEW.duracion_minutos IS DISTINCT FROM OLD.duracion_minutos;
                    IF OLD.estado IN ('Atendida', 'Cancelada', 'Inasistencia') THEN
                        RAISE EXCEPTION 'Una cita finalizada conserva su registro historico';
                    END IF;
                    IF validar_reserva AND NEW.estado <> 'Programada' THEN
                        RAISE EXCEPTION 'Una reprogramacion debe quedar Programada';
                    END IF;
                END IF;

                IF validar_reserva THEN
                    -- Schedule edits take the same lock, so duration cannot change during a booking.
                    PERFORM 1 FROM medico WHERE id = NEW.medico_id FOR UPDATE;
                    IF NEW.inicio <= current_timestamp THEN
                        RAISE EXCEPTION 'La cita debe programarse en una fecha futura';
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM paciente WHERE id = NEW.paciente_id AND activo) THEN
                        RAISE EXCEPTION 'Paciente inactivo o inexistente';
                    END IF;
                    IF NOT EXISTS (
                        SELECT 1 FROM medico m JOIN usuario u ON u.id = m.usuario_id
                        JOIN rol r ON r.id = u.rol_id
                        JOIN medico_especialidad me ON me.medico_id = m.id
                        JOIN especialidad e ON e.id = me.especialidad_id
                        WHERE m.id = NEW.medico_id AND e.id = NEW.especialidad_id
                            AND m.activo AND u.activo AND r.activo AND r.nombre = 'Medico'
                            AND me.activo AND e.activo
                    ) THEN RAISE EXCEPTION 'Medico o especialidad no disponibles'; END IF;

                    fecha_local := NEW.inicio AT TIME ZONE 'America/La_Paz';
                    SELECT h.duracion_minutos INTO minutos FROM horario h
                    WHERE h.medico_id = NEW.medico_id AND h.activo
                        AND h.dia_semana = extract(isodow FROM fecha_local)
                        AND fecha_local::time >= h.hora_inicio
                        AND (fecha_local + make_interval(mins => h.duracion_minutos))::date = fecha_local::date
                        AND (fecha_local + make_interval(mins => h.duracion_minutos))::time <= h.hora_fin
                        AND extract(epoch FROM (fecha_local::time - h.hora_inicio))
                            % (h.duracion_minutos * 60) = 0
                    LIMIT 1;
                    IF minutos IS NULL THEN
                        RAISE EXCEPTION 'La cita no corresponde a un bloque del horario del medico';
                    END IF;
                    -- Ignore supplied ending/duration, including legacy callers supplying 30 minutes.
                    NEW.duracion_minutos := minutos;
                    NEW.fin := NEW.inicio + make_interval(mins => minutos);
                END IF;

                IF NEW.estado = 'Atendida' AND NEW.inicio > current_timestamp THEN
                    RAISE EXCEPTION 'La atencion no puede registrarse antes del inicio';
                END IF;
                IF NEW.estado = 'Inasistencia' AND NEW.fin > current_timestamp THEN
                    RAISE EXCEPTION 'La inasistencia se registra al terminar el bloque';
                END IF;
                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE FUNCTION vida_salud.proteger_horario() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            DECLARE m bigint;
            BEGIN
                IF TG_OP = 'INSERT' THEN m := NEW.medico_id; ELSE m := OLD.medico_id; END IF;
                PERFORM 1 FROM medico WHERE id = m FOR UPDATE;
                IF TG_OP = 'UPDATE' AND NEW.medico_id <> OLD.medico_id THEN
                    RAISE EXCEPTION 'Cree otro horario para cambiar de medico';
                END IF;
                IF TG_OP <> 'INSERT' AND EXISTS (
                    SELECT 1 FROM cita c WHERE c.medico_id = m AND c.inicio >= current_timestamp
                        AND c.estado IN ('Programada', 'Confirmada')
                        AND extract(isodow FROM c.inicio AT TIME ZONE 'America/La_Paz') = OLD.dia_semana
                        AND (c.inicio AT TIME ZONE 'America/La_Paz')::time >= OLD.hora_inicio
                        AND (c.fin AT TIME ZONE 'America/La_Paz')::time <= OLD.hora_fin
                ) THEN
                    IF TG_OP = 'DELETE' THEN RAISE EXCEPTION 'El horario tiene citas futuras'; END IF;
                    IF NEW.duracion_minutos IS DISTINCT FROM OLD.duracion_minutos THEN
                        RAISE EXCEPTION 'El cambio de duracion afecta citas pendientes; resuelvelas primero';
                    END IF;
                    IF EXISTS (
                        SELECT 1 FROM cita c WHERE c.medico_id = m AND c.inicio >= current_timestamp
                            AND c.estado IN ('Programada', 'Confirmada')
                            AND extract(isodow FROM c.inicio AT TIME ZONE 'America/La_Paz') = OLD.dia_semana
                            AND (c.inicio AT TIME ZONE 'America/La_Paz')::time >= OLD.hora_inicio
                            AND (c.fin AT TIME ZONE 'America/La_Paz')::time <= OLD.hora_fin
                            AND (NOT NEW.activo OR NEW.dia_semana <> OLD.dia_semana
                                OR (c.inicio AT TIME ZONE 'America/La_Paz')::time < NEW.hora_inicio
                                OR (c.fin AT TIME ZONE 'America/La_Paz')::time > NEW.hora_fin
                                OR extract(epoch FROM (
                                    (c.inicio AT TIME ZONE 'America/La_Paz')::time - NEW.hora_inicio
                                )) % (NEW.duracion_minutos * 60) <> 0)
                    ) THEN RAISE EXCEPTION 'El cambio de horario afecta citas pendientes'; END IF;
                END IF;
                IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE FUNCTION vida_salud.registrar_evento_cita() RETURNS trigger
            LANGUAGE plpgsql SET search_path TO vida_salud, public AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    INSERT INTO evento_cita(cita_id, usuario_id, estado_nuevo, inicio_nuevo, fin_nuevo, detalle)
                    VALUES(NEW.id, NEW.creada_por, NEW.estado, NEW.inicio, NEW.fin, 'Creacion');
                ELSE
                    INSERT INTO evento_cita(cita_id, usuario_id, estado_anterior, estado_nuevo,
                        inicio_anterior, inicio_nuevo, fin_anterior, fin_nuevo, detalle)
                    VALUES(NEW.id, NEW.modificada_por, OLD.estado, NEW.estado,
                        OLD.inicio, NEW.inicio, OLD.fin, NEW.fin,
                        CASE WHEN NEW.estado = 'Cancelada' THEN NEW.motivo_cancelacion ELSE 'Actualizacion' END);
                END IF;
                RETURN NEW;
            END;
            $$;
            SQL);
    }

    public function down(): void
    {
        $inUse = DB::selectOne(<<<'SQL'
            SELECT EXISTS(SELECT 1 FROM vida_salud.horario WHERE duracion_minutos <> 30)
                OR EXISTS(SELECT 1 FROM vida_salud.cita WHERE duracion_minutos <> 30)
                OR EXISTS(SELECT 1 FROM vida_salud.evento_cita
                    WHERE fin_anterior IS NOT NULL OR fin_nuevo IS NOT NULL) AS in_use
            SQL)->in_use;

        if ($inUse) {
            throw new RuntimeException('No se revierte la duración configurable con citas, horarios o eventos nuevos. Conserve el historial.');
        }

        $dump = file_get_contents(database_path('vida_salud_existing.sql'));
        if ($dump === false) {
            throw new RuntimeException('No se pudo leer el esquema original para restaurar sus funciones.');
        }

        foreach (['horarios_disponibles', 'agendar_cita', 'reprogramar_cita', 'validar_cita', 'proteger_horario', 'registrar_evento_cita'] as $function) {
            if (preg_match('/^CREATE FUNCTION vida_salud\.'.preg_quote($function, '/').'\(.*?\$\$;/ms', $dump, $matches) !== 1) {
                throw new RuntimeException('No se encontró la función original de '.$function.'.');
            }
            DB::unprepared(preg_replace('/^CREATE FUNCTION/', 'CREATE OR REPLACE FUNCTION', $matches[0]));
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE vida_salud.horario DROP CONSTRAINT horario_check1,
                DROP CONSTRAINT horario_duracion_check, DROP COLUMN duracion_minutos,
                ADD CONSTRAINT horario_check1 CHECK (extract(epoch FROM (hora_fin - hora_inicio)) % 1800 = 0);
            ALTER TABLE vida_salud.cita DROP CONSTRAINT cita_check,
                DROP CONSTRAINT cita_duracion_check, DROP COLUMN duracion_minutos,
                ADD CONSTRAINT cita_check CHECK (fin = inicio + interval '30 minutes');
            ALTER TABLE vida_salud.evento_cita DROP COLUMN fin_anterior, DROP COLUMN fin_nuevo;
            SQL);
    }
};
