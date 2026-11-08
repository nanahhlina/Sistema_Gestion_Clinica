--
-- PostgreSQL database dump
--

\restrict e8qnbC48yQjwUh56DB3jM5WRulTN3iYGiZflFw4IrAqLjjawf70CG1Jqn9RLjIp

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: vida_salud; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA vida_salud;


--
-- Name: actualizar_estado_orden(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.actualizar_estado_orden() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE o bigint;
BEGIN
 SELECT orden_id INTO o FROM orden_analisis WHERE id=NEW.orden_analisis_id;
 UPDATE orden_laboratorio SET estado=CASE WHEN EXISTS(
 SELECT 1 FROM orden_analisis d LEFT JOIN resultado_laboratorio r ON r.orden_analisis_id=d.id
 WHERE d.orden_id=o AND r.id IS NULL) THEN 'En proceso' ELSE 'Completada' END WHERE id=o;
 RETURN NEW;
END; $$;


--
-- Name: agendar_cita(bigint, bigint, bigint, bigint, timestamp with time zone, text); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.agendar_cita(p_usuario bigint, p_paciente bigint, p_medico bigint, p_especialidad bigint, p_inicio timestamp with time zone, p_motivo text DEFAULT NULL::text) RETURNS bigint
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE identificador bigint;
BEGIN
 PERFORM exigir_permiso(p_usuario,'citas.gestionar');
 INSERT INTO cita(paciente_id,medico_id,especialidad_id,inicio,fin,motivo_consulta,creada_por,modificada_por)
 VALUES(p_paciente,p_medico,p_especialidad,p_inicio,p_inicio+interval '30 minutes',p_motivo,p_usuario,p_usuario)
 RETURNING id INTO identificador;
 RETURN identificador;
END; $$;


--
-- Name: auditar_cambio(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.auditar_cambio() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE actor bigint; datos jsonb;
BEGIN
 actor:=nullif(current_setting('app.usuario_id',true),'')::bigint;
 IF TG_OP='DELETE' THEN datos:=to_jsonb(OLD); ELSE datos:=to_jsonb(NEW); END IF;
 INSERT INTO bitacora(usuario_id,accion,entidad,registro_id,descripcion)
 VALUES(actor,TG_OP,TG_TABLE_NAME,coalesce((datos->>'id')::bigint,(datos->>'paciente_id')::bigint),
 'Cambio de registro');
 IF TG_OP='DELETE' THEN RETURN OLD; END IF;
 RETURN NEW;
END; $$;


--
-- Name: cancelar_cita(bigint, bigint, text); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.cancelar_cita(p_usuario bigint, p_cita bigint, p_motivo text) RETURNS void
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
BEGIN
 PERFORM exigir_permiso(p_usuario,'citas.gestionar');
 UPDATE cita SET estado='Cancelada',motivo_cancelacion=p_motivo,modificada_por=p_usuario WHERE id=p_cita;
 IF NOT FOUND THEN RAISE EXCEPTION 'Cita inexistente'; END IF;
END; $$;


--
-- Name: comprobar_consulta(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.comprobar_consulta() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE c cita%ROWTYPE; medico_usuario bigint;
BEGIN
 IF TG_OP='UPDATE' AND (NEW.cita_id<>OLD.cita_id OR NEW.registrada_por<>OLD.registrada_por) THEN
  RAISE EXCEPTION 'No se puede trasladar una consulta a otra cita o autor';
 END IF;
 SELECT * INTO c FROM cita WHERE id=NEW.cita_id FOR UPDATE;
 IF NOT FOUND THEN RAISE EXCEPTION 'Cita inexistente'; END IF;
 SELECT usuario_id INTO medico_usuario FROM medico WHERE id=c.medico_id;
 IF medico_usuario<>NEW.registrada_por THEN RAISE EXCEPTION 'La consulta pertenece a otro medico'; END IF;
 PERFORM exigir_permiso(NEW.registrada_por,'atencion.registrar');
 IF c.estado IN ('Cancelada','Inasistencia') OR c.inicio>current_timestamp THEN
  RAISE EXCEPTION 'La cita no admite atencion';
 END IF;
 IF NEW.fecha_atencion<c.inicio OR NEW.fecha_atencion>clock_timestamp() THEN
  RAISE EXCEPTION 'Fecha de atencion invalida';
 END IF;
 IF c.estado<>'Atendida' THEN
  UPDATE cita SET estado='Atendida',modificada_por=NEW.registrada_por WHERE id=c.id;
 END IF;
 RETURN NEW;
END; $$;


--
-- Name: comprobar_orden_no_vacia(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.comprobar_orden_no_vacia() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE orden bigint;
BEGIN
 IF TG_TABLE_NAME='orden_laboratorio' THEN orden:=NEW.id;
 ELSIF TG_OP='DELETE' THEN orden:=OLD.orden_id;
 ELSE orden:=NEW.orden_id; END IF;
 IF EXISTS(SELECT 1 FROM orden_laboratorio WHERE id=orden)
 AND NOT EXISTS(SELECT 1 FROM orden_analisis WHERE orden_id=orden) THEN
  RAISE EXCEPTION 'Una orden debe tener al menos un analisis';
 END IF;
 RETURN NULL;
END; $$;


--
-- Name: comprobar_paciente(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.comprobar_paciente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
 NEW.ci := nullif(upper(btrim(NEW.ci)),'');
 IF NEW.fecha_nacimiento > current_date THEN RAISE EXCEPTION 'Fecha de nacimiento futura'; END IF;
 IF nullif(btrim(NEW.nombres),'') IS NULL OR nullif(btrim(NEW.apellidos),'') IS NULL
 OR nullif(btrim(NEW.telefono),'') IS NULL THEN RAISE EXCEPTION 'Nombre, apellido y telefono obligatorios'; END IF;
 RETURN NEW;
END; $$;


--
-- Name: exigir_permiso(bigint, text); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.exigir_permiso(p_usuario bigint, p_permiso text) RETURNS void
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
BEGIN
 IF NOT tiene_permiso(p_usuario,p_permiso) THEN
  RAISE EXCEPTION 'Usuario inactivo o permiso insuficiente: %',p_permiso;
 END IF;
 -- Contexto para auditoria, limitado a la transaccion actual.
 PERFORM set_config('app.usuario_id',p_usuario::text,true);
END; $$;


--
-- Name: horarios_disponibles(bigint, date); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.horarios_disponibles(p_medico bigint, p_fecha date) RETURNS TABLE(inicio timestamp with time zone, fin timestamp with time zone)
    LANGUAGE sql STABLE
    SET search_path TO 'vida_salud', 'public'
    AS $$
 SELECT b.inicio,b.inicio+interval '30 minutes'
 FROM horario h JOIN medico m ON m.id=h.medico_id JOIN usuario u ON u.id=m.usuario_id
 CROSS JOIN LATERAL generate_series(
 (p_fecha+h.hora_inicio) AT TIME ZONE 'America/La_Paz',
 ((p_fecha+h.hora_fin) AT TIME ZONE 'America/La_Paz')-interval '30 minutes',
 interval '30 minutes') AS b(inicio)
 WHERE h.medico_id=p_medico AND h.dia_semana=extract(isodow FROM p_fecha)
 AND h.activo AND m.activo AND u.activo AND b.inicio>current_timestamp
 AND NOT EXISTS(SELECT 1 FROM cita c WHERE c.medico_id=p_medico
 AND c.estado IN ('Programada','Confirmada','Atendida')
 AND tstzrange(c.inicio,c.fin,'[)') && tstzrange(b.inicio,b.inicio+interval '30 minutes','[)'))
 ORDER BY b.inicio;
$$;


--
-- Name: impedir_borrado(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.impedir_borrado() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN RAISE EXCEPTION 'No se permite borrar %. Conserve el historial',TG_TABLE_NAME; END; $$;


--
-- Name: proteger_horario(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.proteger_horario() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE m bigint;
BEGIN
 IF TG_OP='INSERT' THEN m:=NEW.medico_id; ELSE m:=OLD.medico_id; END IF;
 PERFORM 1 FROM medico WHERE id=m FOR UPDATE;
 IF TG_OP='UPDATE' AND NEW.medico_id<>OLD.medico_id THEN
  RAISE EXCEPTION 'Cree otro horario para cambiar de medico';
 END IF;
 -- Restriccion deliberadamente conservadora: resolver las citas afectadas primero.
 IF TG_OP<>'INSERT' AND EXISTS(SELECT 1 FROM cita c
 WHERE c.medico_id=m AND c.inicio>=current_timestamp
 AND c.estado IN ('Programada','Confirmada')
 AND extract(isodow FROM c.inicio AT TIME ZONE 'America/La_Paz')=OLD.dia_semana
 AND (c.inicio AT TIME ZONE 'America/La_Paz')::time>=OLD.hora_inicio
 AND (c.fin AT TIME ZONE 'America/La_Paz')::time<=OLD.hora_fin) THEN
  IF TG_OP='DELETE' THEN RAISE EXCEPTION 'El horario tiene citas futuras'; END IF;
  IF EXISTS(SELECT 1 FROM cita c WHERE c.medico_id=m
   AND c.inicio>=current_timestamp AND c.estado IN ('Programada','Confirmada')
   AND extract(isodow FROM c.inicio AT TIME ZONE 'America/La_Paz')=OLD.dia_semana
   AND (c.inicio AT TIME ZONE 'America/La_Paz')::time>=OLD.hora_inicio
   AND (c.fin AT TIME ZONE 'America/La_Paz')::time<=OLD.hora_fin
   AND (NOT NEW.activo OR NEW.dia_semana<>OLD.dia_semana
    OR (c.inicio AT TIME ZONE 'America/La_Paz')::time<NEW.hora_inicio
    OR (c.fin AT TIME ZONE 'America/La_Paz')::time>NEW.hora_fin
    OR extract(epoch FROM ((c.inicio AT TIME ZONE 'America/La_Paz')::time-NEW.hora_inicio))::numeric % 1800<>0))
  THEN RAISE EXCEPTION 'El cambio de horario afecta citas pendientes'; END IF;
 END IF;
 IF TG_OP='DELETE' THEN RETURN OLD; END IF;
 RETURN NEW;
END; $$;


--
-- Name: registrar_evento_cita(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.registrar_evento_cita() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
BEGIN
 IF TG_OP='INSERT' THEN
  INSERT INTO evento_cita(cita_id,usuario_id,estado_nuevo,inicio_nuevo,detalle)
  VALUES(NEW.id,NEW.creada_por,NEW.estado,NEW.inicio,'Creacion');
 ELSE
  INSERT INTO evento_cita(cita_id,usuario_id,estado_anterior,estado_nuevo,
   inicio_anterior,inicio_nuevo,detalle)
  VALUES(NEW.id,NEW.modificada_por,OLD.estado,NEW.estado,OLD.inicio,NEW.inicio,
   CASE WHEN NEW.estado='Cancelada' THEN NEW.motivo_cancelacion ELSE 'Actualizacion' END);
 END IF;
 RETURN NEW;
END;
$$;


--
-- Name: reprogramar_cita(bigint, bigint, timestamp with time zone); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.reprogramar_cita(p_usuario bigint, p_cita bigint, p_inicio timestamp with time zone) RETURNS void
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
BEGIN
 PERFORM exigir_permiso(p_usuario,'citas.gestionar');
 UPDATE cita SET inicio=p_inicio,fin=p_inicio+interval '30 minutes',estado='Programada',
 modificada_por=p_usuario WHERE id=p_cita;
 IF NOT FOUND THEN RAISE EXCEPTION 'Cita inexistente'; END IF;
END; $$;


--
-- Name: tiene_permiso(bigint, text); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.tiene_permiso(p_usuario bigint, p_permiso text) RETURNS boolean
    LANGUAGE sql STABLE
    SET search_path TO 'vida_salud', 'public'
    AS $$
 SELECT EXISTS(SELECT 1 FROM usuario u JOIN rol r ON r.id=u.rol_id
 JOIN rol_permiso rp ON rp.rol_id=r.id JOIN permiso p ON p.id=rp.permiso_id
 WHERE u.id=p_usuario AND u.activo AND r.activo AND p.codigo=p_permiso);
$$;


--
-- Name: validar_cita(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.validar_cita() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE
 fecha_local timestamp;
 fin_local timestamp;
 validar_reserva boolean;
BEGIN
 IF TG_OP = 'INSERT' THEN
  IF NEW.estado <> 'Programada' THEN
   RAISE EXCEPTION 'Una cita nueva debe estar Programada';
  END IF;
  validar_reserva := true;
 ELSE
  IF NEW.paciente_id <> OLD.paciente_id OR NEW.medico_id <> OLD.medico_id
     OR NEW.especialidad_id <> OLD.especialidad_id THEN
   RAISE EXCEPTION 'Para cambiar paciente, medico o especialidad, cancele y cree otra cita';
  END IF;
  validar_reserva := NEW.inicio IS DISTINCT FROM OLD.inicio OR NEW.fin IS DISTINCT FROM OLD.fin;
  IF OLD.estado IN ('Atendida','Cancelada','Inasistencia') THEN
   RAISE EXCEPTION 'Una cita finalizada conserva su registro historico';
  END IF;
  IF validar_reserva AND NEW.estado <> 'Programada' THEN
   RAISE EXCEPTION 'Una reprogramacion debe quedar Programada';
  END IF;
 END IF;
 IF validar_reserva THEN
  -- Bloqueo compartido con operaciones de configuracion de horarios.
  PERFORM 1 FROM medico WHERE id=NEW.medico_id FOR UPDATE;
  IF NEW.inicio <= current_timestamp THEN
   RAISE EXCEPTION 'La cita debe programarse en una fecha futura';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM paciente WHERE id=NEW.paciente_id AND activo) THEN
   RAISE EXCEPTION 'Paciente inactivo o inexistente';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM medico m JOIN usuario u ON u.id=m.usuario_id
   JOIN medico_especialidad me ON me.medico_id=m.id
   JOIN especialidad e ON e.id=me.especialidad_id
   WHERE m.id=NEW.medico_id AND e.id=NEW.especialidad_id
   AND m.activo AND u.activo AND me.activo AND e.activo) THEN
   RAISE EXCEPTION 'Medico o especialidad no disponibles';
  END IF;
  fecha_local := NEW.inicio AT TIME ZONE 'America/La_Paz';
  fin_local := NEW.fin AT TIME ZONE 'America/La_Paz';
  IF NOT EXISTS (SELECT 1 FROM horario h WHERE h.medico_id=NEW.medico_id
   AND h.activo AND h.dia_semana=extract(isodow FROM fecha_local)
   AND fecha_local::date=fin_local::date
   AND fecha_local::time >= h.hora_inicio AND fin_local::time <= h.hora_fin
   AND extract(epoch FROM (fecha_local::time-h.hora_inicio))::numeric % 1800=0) THEN
   RAISE EXCEPTION 'La cita no corresponde a un bloque del horario del medico';
  END IF;
 END IF;
 IF NEW.estado='Atendida' AND NEW.inicio > current_timestamp THEN
  RAISE EXCEPTION 'La atencion no puede registrarse antes del inicio';
 END IF;
 IF NEW.estado='Inasistencia' AND NEW.fin > current_timestamp THEN
  RAISE EXCEPTION 'La inasistencia se registra al terminar el bloque';
 END IF;
 RETURN NEW;
END;
$$;


--
-- Name: validar_detalle_orden(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.validar_detalle_orden() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE o bigint; estado_actual text;
BEGIN
 IF TG_OP='DELETE' THEN o:=OLD.orden_id; ELSE o:=NEW.orden_id; END IF;
 IF TG_OP='UPDATE' THEN RAISE EXCEPTION 'Quite el analisis pendiente y agregue el correcto'; END IF;
 SELECT estado INTO estado_actual FROM orden_laboratorio WHERE id=o FOR UPDATE;
 IF estado_actual IS DISTINCT FROM 'Pendiente' THEN RAISE EXCEPTION 'Solo se modifica el detalle de una orden pendiente'; END IF;
 IF TG_OP='DELETE' THEN RETURN OLD; END IF;
 IF NOT EXISTS(SELECT 1 FROM analisis_laboratorio WHERE id=NEW.analisis_id AND activo) THEN
  RAISE EXCEPTION 'Analisis inactivo o inexistente'; END IF;
 RETURN NEW;
END; $$;


--
-- Name: validar_orden(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.validar_orden() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE autor bigint;
BEGIN
 SELECT registrada_por INTO autor FROM consulta WHERE id=NEW.consulta_id;
 IF autor IS DISTINCT FROM NEW.solicitada_por THEN RAISE EXCEPTION 'La orden debe solicitarla el medico de la consulta'; END IF;
 IF TG_OP='INSERT' THEN
  PERFORM exigir_permiso(NEW.solicitada_por,'laboratorio.solicitar');
  IF NEW.estado<>'Pendiente' THEN RAISE EXCEPTION 'La orden debe iniciar Pendiente'; END IF;
 ELSE
  IF NEW.consulta_id<>OLD.consulta_id OR NEW.solicitada_por<>OLD.solicitada_por THEN
   RAISE EXCEPTION 'No se puede cambiar la consulta ni el solicitante';
  END IF;
  IF OLD.estado IN ('Cancelada','Completada') AND NEW.estado<>OLD.estado THEN
   RAISE EXCEPTION 'La orden ya esta finalizada';
  END IF;
  IF OLD.estado='En proceso' AND NEW.estado='Pendiente' THEN RAISE EXCEPTION 'No se permite retroceder la orden'; END IF;
 END IF;
 IF NEW.estado='Completada' AND (NOT EXISTS(SELECT 1 FROM orden_analisis WHERE orden_id=NEW.id)
 OR EXISTS(SELECT 1 FROM orden_analisis d LEFT JOIN resultado_laboratorio r ON r.orden_analisis_id=d.id
 WHERE d.orden_id=NEW.id AND r.id IS NULL)) THEN RAISE EXCEPTION 'Existen analisis sin resultado'; END IF;
 IF NEW.estado='Cancelada' AND EXISTS(SELECT 1 FROM orden_analisis d
 JOIN resultado_laboratorio r ON r.orden_analisis_id=d.id WHERE d.orden_id=NEW.id) THEN
  RAISE EXCEPTION 'No se puede cancelar una orden con resultados';
 END IF;
 RETURN NEW;
END; $$;


--
-- Name: validar_resultado(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.validar_resultado() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
DECLARE o bigint; estado_actual text;
BEGIN
 PERFORM exigir_permiso(NEW.laboratorista_id,'laboratorio.procesar');
 IF TG_OP='UPDATE' AND NEW.orden_analisis_id<>OLD.orden_analisis_id THEN
  RAISE EXCEPTION 'No se puede trasladar el resultado a otra orden'; END IF;
 SELECT orden_id INTO o FROM orden_analisis WHERE id=NEW.orden_analisis_id;
 SELECT estado INTO estado_actual FROM orden_laboratorio WHERE id=o FOR UPDATE;
 IF estado_actual IS NULL OR estado_actual='Cancelada' THEN RAISE EXCEPTION 'Orden inexistente o cancelada'; END IF;
 RETURN NEW;
END; $$;


--
-- Name: versionar_resultado(); Type: FUNCTION; Schema: vida_salud; Owner: -
--

CREATE FUNCTION vida_salud.versionar_resultado() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'vida_salud', 'public'
    AS $$
BEGIN
 INSERT INTO resultado_version(resultado_id,valor_anterior,observaciones_anteriores,
 archivo_anterior,autor_anterior,corregido_por)
 VALUES(OLD.id,OLD.resultado,OLD.observaciones,OLD.archivo_referencia,OLD.laboratorista_id,NEW.laboratorista_id);
 RETURN NEW;
END; $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: analisis_laboratorio; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.analisis_laboratorio (
    id bigint NOT NULL,
    nombre character varying(120) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: analisis_laboratorio_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.analisis_laboratorio ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.analisis_laboratorio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bitacora; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.bitacora (
    id bigint NOT NULL,
    usuario_id bigint,
    fecha timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    accion character varying(80) NOT NULL,
    entidad character varying(80),
    registro_id bigint,
    descripcion text,
    direccion_ip inet
);


--
-- Name: bitacora_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.bitacora ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.bitacora_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cita; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.cita (
    id bigint NOT NULL,
    paciente_id bigint NOT NULL,
    medico_id bigint NOT NULL,
    especialidad_id bigint NOT NULL,
    inicio timestamp with time zone NOT NULL,
    fin timestamp with time zone NOT NULL,
    estado character varying(20) DEFAULT 'Programada'::character varying NOT NULL,
    motivo_consulta text,
    motivo_cancelacion text,
    creada_por bigint NOT NULL,
    modificada_por bigint NOT NULL,
    creada_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT cita_check CHECK ((fin = (inicio + '00:30:00'::interval))),
    CONSTRAINT cita_check1 CHECK ((((estado)::text <> 'Cancelada'::text) OR (NULLIF(btrim(motivo_cancelacion), ''::text) IS NOT NULL))),
    CONSTRAINT cita_estado_check CHECK (((estado)::text = ANY ((ARRAY['Programada'::character varying, 'Confirmada'::character varying, 'Atendida'::character varying, 'Cancelada'::character varying, 'Inasistencia'::character varying])::text[])))
);


--
-- Name: cita_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.cita ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.cita_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: consulta; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.consulta (
    id bigint NOT NULL,
    cita_id bigint NOT NULL,
    fecha_atencion timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    notas_clinicas text,
    registrada_por bigint NOT NULL
);


--
-- Name: consulta_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.consulta ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.consulta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: diagnostico; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.diagnostico (
    id bigint NOT NULL,
    consulta_id bigint NOT NULL,
    descripcion text NOT NULL,
    codigo_clasificacion character varying(30),
    registrado_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT diagnostico_descripcion_check CHECK ((length(btrim(descripcion)) > 0))
);


--
-- Name: diagnostico_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.diagnostico ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.diagnostico_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: especialidad; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.especialidad (
    id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT especialidad_nombre_check CHECK ((length(btrim((nombre)::text)) > 0))
);


--
-- Name: especialidad_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.especialidad ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.especialidad_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: evento_cita; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.evento_cita (
    id bigint NOT NULL,
    cita_id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    ocurrido_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado_anterior character varying(20),
    estado_nuevo character varying(20) NOT NULL,
    inicio_anterior timestamp with time zone,
    inicio_nuevo timestamp with time zone NOT NULL,
    detalle text
);


--
-- Name: evento_cita_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.evento_cita ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.evento_cita_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: historia_clinica; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.historia_clinica (
    paciente_id bigint NOT NULL,
    antecedentes_personales text,
    antecedentes_familiares text,
    alergias text,
    registrada_por bigint NOT NULL,
    actualizada_por bigint NOT NULL,
    actualizada_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: tratamiento; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.tratamiento (
    id bigint NOT NULL,
    diagnostico_id bigint NOT NULL,
    descripcion text NOT NULL,
    indicaciones text,
    fecha_inicio date,
    fecha_fin date,
    registrado_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT tratamiento_check CHECK (((fecha_fin IS NULL) OR (fecha_inicio IS NULL) OR (fecha_fin >= fecha_inicio))),
    CONSTRAINT tratamiento_descripcion_check CHECK ((length(btrim(descripcion)) > 0))
);


--
-- Name: historial_clinico; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.historial_clinico AS
 SELECT c.paciente_id,
    co.id AS consulta_id,
    co.fecha_atencion,
    c.medico_id,
    co.notas_clinicas,
    d.id AS diagnostico_id,
    d.descripcion AS diagnostico,
    t.id AS tratamiento_id,
    t.descripcion AS tratamiento,
    t.indicaciones
   FROM (((vida_salud.consulta co
     JOIN vida_salud.cita c ON ((c.id = co.cita_id)))
     LEFT JOIN vida_salud.diagnostico d ON ((d.consulta_id = co.id)))
     LEFT JOIN vida_salud.tratamiento t ON ((t.diagnostico_id = d.id)));


--
-- Name: horario; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.horario (
    id bigint NOT NULL,
    medico_id bigint NOT NULL,
    dia_semana smallint NOT NULL,
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT horario_check CHECK ((hora_fin > hora_inicio)),
    CONSTRAINT horario_check1 CHECK (((EXTRACT(epoch FROM (hora_fin - hora_inicio)) % (1800)::numeric) = (0)::numeric)),
    CONSTRAINT horario_dia_semana_check CHECK (((dia_semana >= 1) AND (dia_semana <= 7)))
);


--
-- Name: horario_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.horario ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.horario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: medico; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.medico (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    registro_profesional character varying(60) NOT NULL,
    telefono character varying(40),
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: medico_especialidad; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.medico_especialidad (
    medico_id bigint NOT NULL,
    especialidad_id bigint NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: medico_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.medico ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.medico_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: orden_analisis; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.orden_analisis (
    id bigint NOT NULL,
    orden_id bigint NOT NULL,
    analisis_id bigint NOT NULL
);


--
-- Name: orden_analisis_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.orden_analisis ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.orden_analisis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: orden_laboratorio; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.orden_laboratorio (
    id bigint NOT NULL,
    consulta_id bigint NOT NULL,
    solicitada_por bigint NOT NULL,
    fecha_solicitud timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    indicaciones text,
    estado character varying(20) DEFAULT 'Pendiente'::character varying NOT NULL,
    motivo_cancelacion text,
    CONSTRAINT orden_laboratorio_check CHECK ((((estado)::text <> 'Cancelada'::text) OR (NULLIF(btrim(motivo_cancelacion), ''::text) IS NOT NULL))),
    CONSTRAINT orden_laboratorio_estado_check CHECK (((estado)::text = ANY ((ARRAY['Pendiente'::character varying, 'En proceso'::character varying, 'Completada'::character varying, 'Cancelada'::character varying])::text[])))
);


--
-- Name: orden_laboratorio_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.orden_laboratorio ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.orden_laboratorio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: paciente; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.paciente (
    id bigint NOT NULL,
    ci character varying(40),
    nombres character varying(100) NOT NULL,
    apellidos character varying(100) NOT NULL,
    fecha_nacimiento date NOT NULL,
    sexo character varying(30),
    telefono character varying(40) NOT NULL,
    correo character varying(150),
    direccion text,
    contacto_emergencia character varying(150),
    telefono_emergencia character varying(40),
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT paciente_ci_check CHECK (((ci IS NULL) OR (length(btrim((ci)::text)) > 0)))
);


--
-- Name: paciente_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.paciente ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.paciente_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: permiso; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.permiso (
    id bigint NOT NULL,
    codigo character varying(80) NOT NULL,
    descripcion text NOT NULL
);


--
-- Name: permiso_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.permiso ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.permiso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: reporte_atenciones; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.reporte_atenciones AS
 SELECT co.id AS consulta_id,
    c.id AS cita_id,
    c.paciente_id,
    c.medico_id,
    c.especialidad_id,
    co.fecha_atencion
   FROM (vida_salud.consulta co
     JOIN vida_salud.cita c ON ((c.id = co.cita_id)));


--
-- Name: usuario; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.usuario (
    id bigint NOT NULL,
    rol_id bigint NOT NULL,
    nombre_usuario character varying(80) NOT NULL,
    password_hash character varying(255) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellidos character varying(100) NOT NULL,
    correo character varying(150),
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT usuario_nombre_usuario_check CHECK ((length(btrim((nombre_usuario)::text)) > 0))
);


--
-- Name: reporte_citas; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.reporte_citas AS
 SELECT c.id,
    c.inicio,
    c.fin,
    c.estado,
    c.paciente_id,
    (((p.nombres)::text || ' '::text) || (p.apellidos)::text) AS paciente,
    c.medico_id,
    (((u.nombres)::text || ' '::text) || (u.apellidos)::text) AS medico,
    e.nombre AS especialidad
   FROM ((((vida_salud.cita c
     JOIN vida_salud.paciente p ON ((p.id = c.paciente_id)))
     JOIN vida_salud.medico m ON ((m.id = c.medico_id)))
     JOIN vida_salud.usuario u ON ((u.id = m.usuario_id)))
     JOIN vida_salud.especialidad e ON ((e.id = c.especialidad_id)));


--
-- Name: reporte_ordenes; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.reporte_ordenes AS
SELECT
    NULL::bigint AS id,
    NULL::timestamp with time zone AS fecha_solicitud,
    NULL::character varying(20) AS estado,
    NULL::bigint AS paciente_id,
    NULL::bigint AS medico_id,
    NULL::bigint AS analisis_solicitados,
    NULL::bigint AS resultados_registrados;


--
-- Name: reporte_pacientes; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.reporte_pacientes AS
 SELECT id,
    ci,
    nombres,
    apellidos,
    fecha_nacimiento,
    telefono,
    activo,
    creado_en
   FROM vida_salud.paciente;


--
-- Name: resultado_laboratorio; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.resultado_laboratorio (
    id bigint NOT NULL,
    orden_analisis_id bigint NOT NULL,
    laboratorista_id bigint NOT NULL,
    resultado text NOT NULL,
    observaciones text,
    archivo_referencia text,
    fecha_registro timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT resultado_laboratorio_resultado_check CHECK ((length(btrim(resultado)) > 0))
);


--
-- Name: reporte_resultados; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.reporte_resultados AS
 SELECT r.id,
    r.fecha_registro,
    o.id AS orden_id,
    c.paciente_id,
    c.medico_id,
    a.nombre AS analisis,
    r.resultado,
    r.observaciones,
    r.archivo_referencia,
    r.laboratorista_id
   FROM (((((vida_salud.resultado_laboratorio r
     JOIN vida_salud.orden_analisis d ON ((d.id = r.orden_analisis_id)))
     JOIN vida_salud.analisis_laboratorio a ON ((a.id = d.analisis_id)))
     JOIN vida_salud.orden_laboratorio o ON ((o.id = d.orden_id)))
     JOIN vida_salud.consulta co ON ((co.id = o.consulta_id)))
     JOIN vida_salud.cita c ON ((c.id = co.cita_id)));


--
-- Name: resultado_laboratorio_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.resultado_laboratorio ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.resultado_laboratorio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: resultado_version; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.resultado_version (
    id bigint NOT NULL,
    resultado_id bigint NOT NULL,
    valor_anterior text NOT NULL,
    observaciones_anteriores text,
    archivo_anterior text,
    autor_anterior bigint NOT NULL,
    corregido_por bigint NOT NULL,
    corregido_en timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: resultado_version_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.resultado_version ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.resultado_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: resumen_citas_diarias; Type: VIEW; Schema: vida_salud; Owner: -
--

CREATE VIEW vida_salud.resumen_citas_diarias AS
 SELECT ((inicio AT TIME ZONE 'America/La_Paz'::text))::date AS fecha,
    medico_id,
    especialidad_id,
    estado,
    count(*) AS cantidad
   FROM vida_salud.cita
  GROUP BY (((inicio AT TIME ZONE 'America/La_Paz'::text))::date), medico_id, especialidad_id, estado;


--
-- Name: rol; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.rol (
    id bigint NOT NULL,
    nombre character varying(60) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: rol_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.rol ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.rol_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: rol_permiso; Type: TABLE; Schema: vida_salud; Owner: -
--

CREATE TABLE vida_salud.rol_permiso (
    rol_id bigint NOT NULL,
    permiso_id bigint NOT NULL
);


--
-- Name: tratamiento_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.tratamiento ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.tratamiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: usuario_id_seq; Type: SEQUENCE; Schema: vida_salud; Owner: -
--

ALTER TABLE vida_salud.usuario ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME vida_salud.usuario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: analisis_laboratorio analisis_laboratorio_nombre_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.analisis_laboratorio
    ADD CONSTRAINT analisis_laboratorio_nombre_key UNIQUE (nombre);


--
-- Name: analisis_laboratorio analisis_laboratorio_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.analisis_laboratorio
    ADD CONSTRAINT analisis_laboratorio_pkey PRIMARY KEY (id);


--
-- Name: bitacora bitacora_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.bitacora
    ADD CONSTRAINT bitacora_pkey PRIMARY KEY (id);


--
-- Name: cita cita_medico_id_tstzrange_excl; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_medico_id_tstzrange_excl EXCLUDE USING gist (medico_id WITH =, tstzrange(inicio, fin, '[)'::text) WITH &&) WHERE (((estado)::text = ANY ((ARRAY['Programada'::character varying, 'Confirmada'::character varying, 'Atendida'::character varying])::text[])));


--
-- Name: cita cita_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_pkey PRIMARY KEY (id);


--
-- Name: consulta consulta_cita_id_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.consulta
    ADD CONSTRAINT consulta_cita_id_key UNIQUE (cita_id);


--
-- Name: consulta consulta_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.consulta
    ADD CONSTRAINT consulta_pkey PRIMARY KEY (id);


--
-- Name: diagnostico diagnostico_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.diagnostico
    ADD CONSTRAINT diagnostico_pkey PRIMARY KEY (id);


--
-- Name: especialidad especialidad_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.especialidad
    ADD CONSTRAINT especialidad_pkey PRIMARY KEY (id);


--
-- Name: evento_cita evento_cita_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.evento_cita
    ADD CONSTRAINT evento_cita_pkey PRIMARY KEY (id);


--
-- Name: historia_clinica historia_clinica_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.historia_clinica
    ADD CONSTRAINT historia_clinica_pkey PRIMARY KEY (paciente_id);


--
-- Name: horario horario_medico_id_dia_semana_numrange_excl; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.horario
    ADD CONSTRAINT horario_medico_id_dia_semana_numrange_excl EXCLUDE USING gist (medico_id WITH =, dia_semana WITH =, numrange(EXTRACT(epoch FROM hora_inicio), EXTRACT(epoch FROM hora_fin), '[)'::text) WITH &&) WHERE (activo);


--
-- Name: horario horario_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.horario
    ADD CONSTRAINT horario_pkey PRIMARY KEY (id);


--
-- Name: medico_especialidad medico_especialidad_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico_especialidad
    ADD CONSTRAINT medico_especialidad_pkey PRIMARY KEY (medico_id, especialidad_id);


--
-- Name: medico medico_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico
    ADD CONSTRAINT medico_pkey PRIMARY KEY (id);


--
-- Name: medico medico_registro_profesional_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico
    ADD CONSTRAINT medico_registro_profesional_key UNIQUE (registro_profesional);


--
-- Name: medico medico_usuario_id_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico
    ADD CONSTRAINT medico_usuario_id_key UNIQUE (usuario_id);


--
-- Name: orden_analisis orden_analisis_orden_id_analisis_id_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_analisis
    ADD CONSTRAINT orden_analisis_orden_id_analisis_id_key UNIQUE (orden_id, analisis_id);


--
-- Name: orden_analisis orden_analisis_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_analisis
    ADD CONSTRAINT orden_analisis_pkey PRIMARY KEY (id);


--
-- Name: orden_laboratorio orden_laboratorio_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_laboratorio
    ADD CONSTRAINT orden_laboratorio_pkey PRIMARY KEY (id);


--
-- Name: paciente paciente_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.paciente
    ADD CONSTRAINT paciente_pkey PRIMARY KEY (id);


--
-- Name: permiso permiso_codigo_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.permiso
    ADD CONSTRAINT permiso_codigo_key UNIQUE (codigo);


--
-- Name: permiso permiso_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.permiso
    ADD CONSTRAINT permiso_pkey PRIMARY KEY (id);


--
-- Name: resultado_laboratorio resultado_laboratorio_orden_analisis_id_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_laboratorio
    ADD CONSTRAINT resultado_laboratorio_orden_analisis_id_key UNIQUE (orden_analisis_id);


--
-- Name: resultado_laboratorio resultado_laboratorio_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_laboratorio
    ADD CONSTRAINT resultado_laboratorio_pkey PRIMARY KEY (id);


--
-- Name: resultado_version resultado_version_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_version
    ADD CONSTRAINT resultado_version_pkey PRIMARY KEY (id);


--
-- Name: rol rol_nombre_key; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.rol
    ADD CONSTRAINT rol_nombre_key UNIQUE (nombre);


--
-- Name: rol_permiso rol_permiso_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.rol_permiso
    ADD CONSTRAINT rol_permiso_pkey PRIMARY KEY (rol_id, permiso_id);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id);


--
-- Name: tratamiento tratamiento_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.tratamiento
    ADD CONSTRAINT tratamiento_pkey PRIMARY KEY (id);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id);


--
-- Name: bitacora_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX bitacora_fecha ON vida_salud.bitacora USING btree (fecha);


--
-- Name: bitacora_usuario_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX bitacora_usuario_fecha ON vida_salud.bitacora USING btree (usuario_id, fecha);


--
-- Name: cita_estado_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX cita_estado_fecha ON vida_salud.cita USING btree (estado, inicio);


--
-- Name: cita_medico_inicio; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX cita_medico_inicio ON vida_salud.cita USING btree (medico_id, inicio);


--
-- Name: cita_paciente_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX cita_paciente_fecha ON vida_salud.cita USING btree (paciente_id, inicio);


--
-- Name: diagnostico_consulta; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX diagnostico_consulta ON vida_salud.diagnostico USING btree (consulta_id);


--
-- Name: especialidad_nombre_unico; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE UNIQUE INDEX especialidad_nombre_unico ON vida_salud.especialidad USING btree (lower(btrim((nombre)::text)));


--
-- Name: evento_cita_historial; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX evento_cita_historial ON vida_salud.evento_cita USING btree (cita_id, ocurrido_en);


--
-- Name: medico_especialidad_especialidad; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX medico_especialidad_especialidad ON vida_salud.medico_especialidad USING btree (especialidad_id);


--
-- Name: orden_analisis_analisis; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX orden_analisis_analisis ON vida_salud.orden_analisis USING btree (analisis_id);


--
-- Name: orden_consulta; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX orden_consulta ON vida_salud.orden_laboratorio USING btree (consulta_id);


--
-- Name: orden_estado_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX orden_estado_fecha ON vida_salud.orden_laboratorio USING btree (estado, fecha_solicitud);


--
-- Name: paciente_apellidos; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX paciente_apellidos ON vida_salud.paciente USING btree (lower((apellidos)::text), lower((nombres)::text));


--
-- Name: paciente_ci_unico; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE UNIQUE INDEX paciente_ci_unico ON vida_salud.paciente USING btree (upper(btrim((ci)::text))) WHERE (ci IS NOT NULL);


--
-- Name: resultado_laboratorista_fecha; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX resultado_laboratorista_fecha ON vida_salud.resultado_laboratorio USING btree (laboratorista_id, fecha_registro);


--
-- Name: resultado_version_historial; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX resultado_version_historial ON vida_salud.resultado_version USING btree (resultado_id, corregido_en);


--
-- Name: rol_permiso_permiso; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX rol_permiso_permiso ON vida_salud.rol_permiso USING btree (permiso_id);


--
-- Name: tratamiento_diagnostico; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX tratamiento_diagnostico ON vida_salud.tratamiento USING btree (diagnostico_id);


--
-- Name: usuario_login_unico; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE UNIQUE INDEX usuario_login_unico ON vida_salud.usuario USING btree (lower(btrim((nombre_usuario)::text)));


--
-- Name: usuario_rol; Type: INDEX; Schema: vida_salud; Owner: -
--

CREATE INDEX usuario_rol ON vida_salud.usuario USING btree (rol_id);


--
-- Name: reporte_ordenes _RETURN; Type: RULE; Schema: vida_salud; Owner: -
--

CREATE OR REPLACE VIEW vida_salud.reporte_ordenes AS
 SELECT o.id,
    o.fecha_solicitud,
    o.estado,
    c.paciente_id,
    c.medico_id,
    count(d.id) AS analisis_solicitados,
    count(r.id) AS resultados_registrados
   FROM ((((vida_salud.orden_laboratorio o
     JOIN vida_salud.consulta co ON ((co.id = o.consulta_id)))
     JOIN vida_salud.cita c ON ((c.id = co.cita_id)))
     LEFT JOIN vida_salud.orden_analisis d ON ((d.orden_id = o.id)))
     LEFT JOIN vida_salud.resultado_laboratorio r ON ((r.orden_analisis_id = d.id)))
  GROUP BY o.id, c.paciente_id, c.medico_id;


--
-- Name: analisis_laboratorio auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.analisis_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: cita auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.cita FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: consulta auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.consulta FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: diagnostico auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.diagnostico FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: especialidad auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.especialidad FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: historia_clinica auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.historia_clinica FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: horario auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.horario FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: medico auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.medico FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: medico_especialidad auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.medico_especialidad FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: orden_analisis auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.orden_analisis FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: orden_laboratorio auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.orden_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: paciente auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.paciente FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: resultado_laboratorio auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.resultado_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: rol auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.rol FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: rol_permiso auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.rol_permiso FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: tratamiento auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.tratamiento FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: usuario auditar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER auditar AFTER INSERT OR DELETE OR UPDATE ON vida_salud.usuario FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio();


--
-- Name: bitacora bitacora_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER bitacora_no_borrar BEFORE DELETE OR UPDATE ON vida_salud.bitacora FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: cita cita_historial; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER cita_historial AFTER INSERT OR UPDATE ON vida_salud.cita FOR EACH ROW EXECUTE FUNCTION vida_salud.registrar_evento_cita();


--
-- Name: cita cita_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER cita_no_borrar BEFORE DELETE ON vida_salud.cita FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: cita cita_validacion; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER cita_validacion BEFORE INSERT OR UPDATE ON vida_salud.cita FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_cita();


--
-- Name: consulta consulta_integridad; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER consulta_integridad BEFORE INSERT OR UPDATE ON vida_salud.consulta FOR EACH ROW EXECUTE FUNCTION vida_salud.comprobar_consulta();


--
-- Name: consulta consulta_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER consulta_no_borrar BEFORE DELETE ON vida_salud.consulta FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: orden_analisis detalle_no_vacio; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE CONSTRAINT TRIGGER detalle_no_vacio AFTER DELETE OR UPDATE ON vida_salud.orden_analisis DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION vida_salud.comprobar_orden_no_vacia();


--
-- Name: orden_analisis detalle_orden_integridad; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER detalle_orden_integridad BEFORE INSERT OR DELETE OR UPDATE ON vida_salud.orden_analisis FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_detalle_orden();


--
-- Name: diagnostico diagnostico_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER diagnostico_no_borrar BEFORE DELETE ON vida_salud.diagnostico FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: evento_cita evento_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER evento_no_borrar BEFORE DELETE OR UPDATE ON vida_salud.evento_cita FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: horario horario_integridad; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER horario_integridad BEFORE INSERT OR DELETE OR UPDATE ON vida_salud.horario FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_horario();


--
-- Name: orden_laboratorio orden_con_analisis; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE CONSTRAINT TRIGGER orden_con_analisis AFTER INSERT OR UPDATE ON vida_salud.orden_laboratorio DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION vida_salud.comprobar_orden_no_vacia();


--
-- Name: orden_laboratorio orden_integridad; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER orden_integridad BEFORE INSERT OR UPDATE ON vida_salud.orden_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_orden();


--
-- Name: orden_laboratorio orden_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER orden_no_borrar BEFORE DELETE ON vida_salud.orden_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: paciente paciente_datos; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER paciente_datos BEFORE INSERT OR UPDATE ON vida_salud.paciente FOR EACH ROW EXECUTE FUNCTION vida_salud.comprobar_paciente();


--
-- Name: resultado_laboratorio resultado_estado; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER resultado_estado AFTER INSERT ON vida_salud.resultado_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.actualizar_estado_orden();


--
-- Name: resultado_laboratorio resultado_integridad; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER resultado_integridad BEFORE INSERT OR UPDATE ON vida_salud.resultado_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.validar_resultado();


--
-- Name: resultado_laboratorio resultado_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER resultado_no_borrar BEFORE DELETE ON vida_salud.resultado_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: resultado_laboratorio resultado_versionar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER resultado_versionar AFTER UPDATE ON vida_salud.resultado_laboratorio FOR EACH ROW EXECUTE FUNCTION vida_salud.versionar_resultado();


--
-- Name: tratamiento tratamiento_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER tratamiento_no_borrar BEFORE DELETE ON vida_salud.tratamiento FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: resultado_version versiones_no_borrar; Type: TRIGGER; Schema: vida_salud; Owner: -
--

CREATE TRIGGER versiones_no_borrar BEFORE DELETE OR UPDATE ON vida_salud.resultado_version FOR EACH ROW EXECUTE FUNCTION vida_salud.impedir_borrado();


--
-- Name: bitacora bitacora_usuario_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.bitacora
    ADD CONSTRAINT bitacora_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES vida_salud.usuario(id);


--
-- Name: cita cita_creada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_creada_por_fkey FOREIGN KEY (creada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: cita cita_medico_id_especialidad_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_medico_id_especialidad_id_fkey FOREIGN KEY (medico_id, especialidad_id) REFERENCES vida_salud.medico_especialidad(medico_id, especialidad_id);


--
-- Name: cita cita_modificada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_modificada_por_fkey FOREIGN KEY (modificada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: cita cita_paciente_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.cita
    ADD CONSTRAINT cita_paciente_id_fkey FOREIGN KEY (paciente_id) REFERENCES vida_salud.paciente(id);


--
-- Name: consulta consulta_cita_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.consulta
    ADD CONSTRAINT consulta_cita_id_fkey FOREIGN KEY (cita_id) REFERENCES vida_salud.cita(id);


--
-- Name: consulta consulta_registrada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.consulta
    ADD CONSTRAINT consulta_registrada_por_fkey FOREIGN KEY (registrada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: diagnostico diagnostico_consulta_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.diagnostico
    ADD CONSTRAINT diagnostico_consulta_id_fkey FOREIGN KEY (consulta_id) REFERENCES vida_salud.consulta(id);


--
-- Name: evento_cita evento_cita_cita_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.evento_cita
    ADD CONSTRAINT evento_cita_cita_id_fkey FOREIGN KEY (cita_id) REFERENCES vida_salud.cita(id);


--
-- Name: evento_cita evento_cita_usuario_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.evento_cita
    ADD CONSTRAINT evento_cita_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES vida_salud.usuario(id);


--
-- Name: historia_clinica historia_clinica_actualizada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.historia_clinica
    ADD CONSTRAINT historia_clinica_actualizada_por_fkey FOREIGN KEY (actualizada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: historia_clinica historia_clinica_paciente_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.historia_clinica
    ADD CONSTRAINT historia_clinica_paciente_id_fkey FOREIGN KEY (paciente_id) REFERENCES vida_salud.paciente(id);


--
-- Name: historia_clinica historia_clinica_registrada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.historia_clinica
    ADD CONSTRAINT historia_clinica_registrada_por_fkey FOREIGN KEY (registrada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: horario horario_medico_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.horario
    ADD CONSTRAINT horario_medico_id_fkey FOREIGN KEY (medico_id) REFERENCES vida_salud.medico(id);


--
-- Name: medico_especialidad medico_especialidad_especialidad_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico_especialidad
    ADD CONSTRAINT medico_especialidad_especialidad_id_fkey FOREIGN KEY (especialidad_id) REFERENCES vida_salud.especialidad(id);


--
-- Name: medico_especialidad medico_especialidad_medico_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico_especialidad
    ADD CONSTRAINT medico_especialidad_medico_id_fkey FOREIGN KEY (medico_id) REFERENCES vida_salud.medico(id);


--
-- Name: medico medico_usuario_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.medico
    ADD CONSTRAINT medico_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES vida_salud.usuario(id);


--
-- Name: orden_analisis orden_analisis_analisis_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_analisis
    ADD CONSTRAINT orden_analisis_analisis_id_fkey FOREIGN KEY (analisis_id) REFERENCES vida_salud.analisis_laboratorio(id);


--
-- Name: orden_analisis orden_analisis_orden_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_analisis
    ADD CONSTRAINT orden_analisis_orden_id_fkey FOREIGN KEY (orden_id) REFERENCES vida_salud.orden_laboratorio(id);


--
-- Name: orden_laboratorio orden_laboratorio_consulta_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_laboratorio
    ADD CONSTRAINT orden_laboratorio_consulta_id_fkey FOREIGN KEY (consulta_id) REFERENCES vida_salud.consulta(id);


--
-- Name: orden_laboratorio orden_laboratorio_solicitada_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.orden_laboratorio
    ADD CONSTRAINT orden_laboratorio_solicitada_por_fkey FOREIGN KEY (solicitada_por) REFERENCES vida_salud.usuario(id);


--
-- Name: resultado_laboratorio resultado_laboratorio_laboratorista_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_laboratorio
    ADD CONSTRAINT resultado_laboratorio_laboratorista_id_fkey FOREIGN KEY (laboratorista_id) REFERENCES vida_salud.usuario(id);


--
-- Name: resultado_laboratorio resultado_laboratorio_orden_analisis_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_laboratorio
    ADD CONSTRAINT resultado_laboratorio_orden_analisis_id_fkey FOREIGN KEY (orden_analisis_id) REFERENCES vida_salud.orden_analisis(id);


--
-- Name: resultado_version resultado_version_autor_anterior_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_version
    ADD CONSTRAINT resultado_version_autor_anterior_fkey FOREIGN KEY (autor_anterior) REFERENCES vida_salud.usuario(id);


--
-- Name: resultado_version resultado_version_corregido_por_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_version
    ADD CONSTRAINT resultado_version_corregido_por_fkey FOREIGN KEY (corregido_por) REFERENCES vida_salud.usuario(id);


--
-- Name: resultado_version resultado_version_resultado_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.resultado_version
    ADD CONSTRAINT resultado_version_resultado_id_fkey FOREIGN KEY (resultado_id) REFERENCES vida_salud.resultado_laboratorio(id);


--
-- Name: rol_permiso rol_permiso_permiso_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.rol_permiso
    ADD CONSTRAINT rol_permiso_permiso_id_fkey FOREIGN KEY (permiso_id) REFERENCES vida_salud.permiso(id);


--
-- Name: rol_permiso rol_permiso_rol_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.rol_permiso
    ADD CONSTRAINT rol_permiso_rol_id_fkey FOREIGN KEY (rol_id) REFERENCES vida_salud.rol(id);


--
-- Name: tratamiento tratamiento_diagnostico_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.tratamiento
    ADD CONSTRAINT tratamiento_diagnostico_id_fkey FOREIGN KEY (diagnostico_id) REFERENCES vida_salud.diagnostico(id);


--
-- Name: usuario usuario_rol_id_fkey; Type: FK CONSTRAINT; Schema: vida_salud; Owner: -
--

ALTER TABLE ONLY vida_salud.usuario
    ADD CONSTRAINT usuario_rol_id_fkey FOREIGN KEY (rol_id) REFERENCES vida_salud.rol(id);


--
-- PostgreSQL database dump complete
--

\unrestrict e8qnbC48yQjwUh56DB3jM5WRulTN3iYGiZflFw4IrAqLjjawf70CG1Jqn9RLjIp

