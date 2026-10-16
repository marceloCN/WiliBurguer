--
-- PostgreSQL database dump
--

\restrict 6DlmzPd9YRupvJJGGvHNmhuQffnXfC40Ysxk8Z3RzhRs4FNcKkbFCWM909OrmBc

-- Dumped from database version 16.15 (Debian 16.15-1.pgdg13+2)
-- Dumped by pg_dump version 16.15 (Debian 16.15-1.pgdg13+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: burguer
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO burguer;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: burguer
--

COMMENT ON SCHEMA public IS '';


--
-- Name: tipo_pedido; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipo_pedido AS ENUM (
    'MESA',
    'PARA_LLEVAR'
);


ALTER TYPE public.tipo_pedido OWNER TO burguer;

--
-- Name: tipodescuento; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipodescuento AS ENUM (
    'PORCENTAJE',
    'MONTO_FIJO'
);


ALTER TYPE public.tipodescuento OWNER TO burguer;

--
-- Name: tipoestado_pedido; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipoestado_pedido AS ENUM (
    'PENDIENTE',
    'ANULADO',
    'EJECUTADO'
);


ALTER TYPE public.tipoestado_pedido OWNER TO burguer;

--
-- Name: tipoestadocaja; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipoestadocaja AS ENUM (
    'ABIERTA',
    'CERRADA'
);


ALTER TYPE public.tipoestadocaja OWNER TO burguer;

--
-- Name: tipoestadopago; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipoestadopago AS ENUM (
    'PENDIENTE',
    'APROBADO',
    'RECHAZADO',
    'ANULADO'
);


ALTER TYPE public.tipoestadopago OWNER TO burguer;

--
-- Name: tipoestadoventa; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipoestadoventa AS ENUM (
    'PENDIENTE',
    'PAGADA',
    'RECHAZADO',
    'ANULADO'
);


ALTER TYPE public.tipoestadoventa OWNER TO burguer;

--
-- Name: tipomovimientocaja; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipomovimientocaja AS ENUM (
    'INGRESO',
    'EGRESO'
);


ALTER TYPE public.tipomovimientocaja OWNER TO burguer;

--
-- Name: tipomovimientoinventario; Type: TYPE; Schema: public; Owner: burguer
--

CREATE TYPE public.tipomovimientoinventario AS ENUM (
    'ENTRADA',
    'SALIDA',
    'AJUSTE'
);


ALTER TYPE public.tipomovimientoinventario OWNER TO burguer;

--
-- Name: actualizar_caja(integer, integer, character varying, boolean); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.actualizar_caja(p_id integer, p_idsucursal integer, p_nombre character varying, p_activo boolean) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_duplicado INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_duplicado
      FROM caja
     WHERE idsucursal = p_idsucursal
       AND nombre = p_nombre
       AND id <> p_id;

    IF v_duplicado > 0 THEN
        RAISE EXCEPTION 'Ya existe una caja con ese nombre en esta sucursal.';
    END IF;

    UPDATE caja
       SET nombre = p_nombre,
           activo = p_activo
     WHERE id = p_id;
END;
$$;


ALTER FUNCTION public.actualizar_caja(p_id integer, p_idsucursal integer, p_nombre character varying, p_activo boolean) OWNER TO burguer;

--
-- Name: actualizar_mesa(integer, integer, integer, integer, boolean, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.actualizar_mesa(IN p_id integer, IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_duplicado INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_duplicado
      FROM mesa
     WHERE idsucursal = p_idsucursal
       AND numeromesa = p_numeromesa
       AND id <> p_id;

    IF v_duplicado > 0 THEN
        RAISE EXCEPTION 'Ya existe una mesa con ese número en esta sucursal.';
    END IF;

    UPDATE mesa
       SET numeromesa = p_numeromesa,
           capacidad = p_capacidad,
           estado = p_estado
     WHERE id = p_id;

    CALL registrar_accion(
        p_id_empleado,
        'UPDATE',
        'mesa',
        p_id,
        p_codigo_permiso,
        'EXITOSO',
        p_equipo,
        p_ip
    );
END;
$$;


ALTER PROCEDURE public.actualizar_mesa(IN p_id integer, IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: actualizar_perfil(integer, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.actualizar_perfil(IN p_id_empleado integer, IN p_nombre character varying, IN p_apellido character varying, IN p_telefono character varying, IN p_usuario character varying, IN p_correo character varying, IN p_password_hash character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_nombre VARCHAR(100);
    v_apellido VARCHAR(100);
    v_telefono VARCHAR(15);
    v_id_cuenta INTEGER;
    v_usuario VARCHAR(50);
    v_correo VARCHAR(150);
    v_cambia_empleado BOOLEAN;
    v_cambia_cuenta BOOLEAN;
BEGIN
    SELECT nombre, apellido, telefono
      INTO v_nombre, v_apellido, v_telefono
      FROM empleado
     WHERE id = p_id_empleado;

    SELECT id, usuario, correo
      INTO v_id_cuenta, v_usuario, v_correo
      FROM cuentausuario
     WHERE idempleado = p_id_empleado;

    v_cambia_empleado := p_nombre IS DISTINCT FROM v_nombre
        OR p_apellido IS DISTINCT FROM v_apellido
        OR COALESCE(p_telefono, '') IS DISTINCT FROM COALESCE(v_telefono, '');

    v_cambia_cuenta := p_usuario IS DISTINCT FROM v_usuario
        OR p_correo IS DISTINCT FROM v_correo
        OR p_password_hash IS NOT NULL;

    UPDATE empleado
       SET nombre = p_nombre,
           apellido = p_apellido,
           telefono = p_telefono
     WHERE id = p_id_empleado;

    UPDATE cuentausuario
       SET usuario = p_usuario,
           correo = p_correo
     WHERE idempleado = p_id_empleado;

    IF p_password_hash IS NOT NULL THEN
        UPDATE cuentausuario
           SET passwordhash = p_password_hash,
               intentosfallidos = 0,
               bloqueado = FALSE,
               bloqueadohasta = NULL
         WHERE idempleado = p_id_empleado;
    END IF;

    IF v_cambia_empleado THEN
        CALL registrar_accion(p_id_empleado, 'UPDATE', 'empleado', p_id_empleado, '101', 'EXITOSO', p_equipo, p_ip);
    END IF;

    IF v_cambia_cuenta THEN
        CALL registrar_accion(p_id_empleado, 'UPDATE', 'cuentausuario', v_id_cuenta, '106', 'EXITOSO', p_equipo, p_ip);
    END IF;
END;
$$;


ALTER PROCEDURE public.actualizar_perfil(IN p_id_empleado integer, IN p_nombre character varying, IN p_apellido character varying, IN p_telefono character varying, IN p_usuario character varying, IN p_correo character varying, IN p_password_hash character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: dar_baja_caja(integer, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.dar_baja_caja(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE caja SET activo = FALSE WHERE id = p_id;

    CALL registrar_accion(
        p_id_empleado,
        'DELETE',
        'caja',
        p_id,
        p_codigo_permiso,
        'EXITOSO',
        p_equipo,
        p_ip
    );
END;
$$;


ALTER PROCEDURE public.dar_baja_caja(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: dar_baja_mesa(integer, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.dar_baja_mesa(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE mesa SET estado = FALSE WHERE id = p_id;

    CALL registrar_accion(
        p_id_empleado,
        'DELETE',
        'mesa',
        p_id,
        p_codigo_permiso,
        'EXITOSO',
        p_equipo,
        p_ip
    );
END;
$$;


ALTER PROCEDURE public.dar_baja_mesa(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: fn_calcular_parametros_cierre(); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.fn_calcular_parametros_cierre() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_efectivo NUMERIC(10,2) := 0;
    v_qr       NUMERIC(10,2) := 0;
    v_tarjeta  NUMERIC(10,2) := 0;
    v_ventas   NUMERIC(10,2) := 0;
    v_ingresos NUMERIC(10,2) := 0;
    v_egresos  NUMERIC(10,2) := 0;
    v_fondo    NUMERIC(10,2) := 0;
BEGIN
    SELECT COALESCE(SUM(CASE WHEN LOWER(mp.nombre) = 'efectivo' THEN p.montopago ELSE 0 END), 0),
           COALESCE(SUM(CASE WHEN LOWER(mp.nombre) LIKE '%tarjeta%' THEN p.montopago ELSE 0 END), 0),
           COALESCE(SUM(CASE WHEN LOWER(mp.nombre) <> 'efectivo' AND LOWER(mp.nombre) NOT LIKE '%tarjeta%' THEN p.montopago ELSE 0 END), 0)
      INTO v_efectivo, v_tarjeta, v_qr
      FROM pago p
      JOIN metodopago mp ON mp.id = p.idmetodopago
     WHERE p.idaperturacaja = NEW.idaperturacaja
       AND p.estadopago = 'APROBADO';

    v_ventas := v_efectivo + v_qr + v_tarjeta;

    SELECT COALESCE(SUM(monto), 0)
      INTO v_ingresos
      FROM movimientocaja
     WHERE idaperturacaja = NEW.idaperturacaja
       AND tipomovimiento = 'INGRESO';

    SELECT COALESCE(SUM(monto), 0)
      INTO v_egresos
      FROM movimientocaja
     WHERE idaperturacaja = NEW.idaperturacaja
       AND tipomovimiento = 'EGRESO';

    SELECT COALESCE(montoinicial, 0)
      INTO v_fondo
      FROM aperturacaja
     WHERE id = NEW.idaperturacaja;

    NEW.fondoinicial := v_fondo;
    NEW.totalventasefectivo := v_efectivo;
    NEW.totalventasqr := v_qr;
    NEW.totalventastarjeta := v_tarjeta;
    NEW.totalventas := v_ventas;
    NEW.totalingresos := v_ingresos;
    NEW.totalegresos := v_egresos;
    NEW.efectivoesperado := v_fondo + v_efectivo + v_ingresos - v_egresos;
    NEW.diferencia := NEW.efectivocontado - NEW.efectivoesperado;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_calcular_parametros_cierre() OWNER TO burguer;

--
-- Name: fn_crear_cierre_inicial(); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.fn_crear_cierre_inicial() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cierrecaja (
        fechacierre, fondoinicial,
        totalventasefectivo, totalventasqr, totalventastarjeta, totalventas,
        totalingresos, totalegresos,
        efectivoesperado, efectivocontado, diferencia,
        observacion, idaperturacaja, idempleado
    ) VALUES (
        NEW.fechaapertura, NEW.montoinicial,
        0, 0, 0, 0,
        0, 0,
        NEW.montoinicial, NEW.montoinicial, 0,
        'Cierre provisional: la jornada aún está en curso.', NEW.id, NEW.idempleado
    );
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_crear_cierre_inicial() OWNER TO burguer;

--
-- Name: fn_refrescar_cierre_por_movimiento(); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.fn_refrescar_cierre_por_movimiento() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = OLD.idaperturacaja;
        RETURN OLD;
    END IF;
    UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = NEW.idaperturacaja;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_refrescar_cierre_por_movimiento() OWNER TO burguer;

--
-- Name: fn_refrescar_cierre_por_pago(); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.fn_refrescar_cierre_por_pago() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = OLD.idaperturacaja;
        RETURN OLD;
    END IF;
    UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = NEW.idaperturacaja;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_refrescar_cierre_por_pago() OWNER TO burguer;

--
-- Name: fn_validar_apertura_caja(); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.fn_validar_apertura_caja() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_abiertas INTEGER;
BEGIN
    IF TG_OP = 'INSERT' THEN
        SELECT COUNT(*) INTO v_abiertas
          FROM aperturacaja
         WHERE idcaja = NEW.idcaja
           AND estado = 'ABIERTA';
    ELSE
        SELECT COUNT(*) INTO v_abiertas
          FROM aperturacaja
         WHERE idcaja = NEW.idcaja
           AND estado = 'ABIERTA'
           AND id <> NEW.id;
    END IF;

    IF v_abiertas > 0 THEN
        RAISE EXCEPTION 'Ya existe una apertura de caja abierta para esta caja; debes cerrarla antes de abrir una nueva jornada.';
    END IF;

    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_validar_apertura_caja() OWNER TO burguer;

--
-- Name: reasignar_subordinados(integer, integer, integer); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.reasignar_subordinados(IN p_jefe_anterior integer, IN p_jefe_nuevo integer, IN p_idsucursal integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_rol_cajero INTEGER;
    v_id_rol_mesero INTEGER;
BEGIN
    IF p_jefe_anterior IS NOT DISTINCT FROM p_jefe_nuevo THEN
        RETURN;
    END IF;

    SELECT id INTO v_id_rol_cajero FROM rol WHERE claverol = 'CAJERO' LIMIT 1;
    SELECT id INTO v_id_rol_mesero FROM rol WHERE claverol = 'MESERO' LIMIT 1;

    -- Si el nuevo gerente dependía del antiguo (idjefe = antiguo), romper
    -- la referencia circular: el nuevo jefe queda sin jefe.
    IF p_jefe_nuevo IS NOT NULL THEN
        UPDATE empleado
           SET idjefe = NULL
         WHERE id = p_jefe_nuevo
           AND idjefe IS NOT DISTINCT FROM p_jefe_anterior;
    END IF;

    -- Reasignar al nuevo gerente TODOS los cajeros/meseros que dependían
    -- del antiguo gerente (estén en esta u otra sucursal).
    UPDATE empleado
       SET idjefe = p_jefe_nuevo
     WHERE idjefe IS NOT DISTINCT FROM p_jefe_anterior
       AND idrol IN (v_id_rol_cajero, v_id_rol_mesero)
       AND (p_jefe_nuevo IS NULL OR id <> p_jefe_nuevo);

    -- Los cajeros/meseros de ESTA sucursal que estén sin gerente (idjefe NULL)
    -- pasan a depender del nuevo gerente.
    UPDATE empleado
       SET idjefe = p_jefe_nuevo
     WHERE idsucursal = p_idsucursal
       AND idjefe IS NULL
       AND idrol IN (v_id_rol_cajero, v_id_rol_mesero)
       AND (p_jefe_nuevo IS NULL OR id <> p_jefe_nuevo);

    -- El antiguo gerente queda sin jefe.
    IF p_jefe_anterior IS NOT NULL THEN
        UPDATE empleado SET idjefe = NULL WHERE id = p_jefe_anterior;
    END IF;
END;
$$;


ALTER PROCEDURE public.reasignar_subordinados(IN p_jefe_anterior integer, IN p_jefe_nuevo integer, IN p_idsucursal integer) OWNER TO burguer;

--
-- Name: registrar_acceso(integer, character varying, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.registrar_acceso(IN p_id_empleado integer, IN p_tipo_acceso character varying, IN p_equipo character varying, IN p_ip character varying, IN p_resultado character varying, IN p_detalle character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO bitacoraacceso (idempleado, tipoacceso, equipo, ip, resultado, detalle)
    VALUES (
        p_id_empleado,
        p_tipo_acceso,
        p_equipo,
        p_ip,
        p_resultado,
        LEFT(p_detalle, 255)
    );
END;
$$;


ALTER PROCEDURE public.registrar_acceso(IN p_id_empleado integer, IN p_tipo_acceso character varying, IN p_equipo character varying, IN p_ip character varying, IN p_resultado character varying, IN p_detalle character varying) OWNER TO burguer;

--
-- Name: registrar_accion(integer, character varying, character varying, bigint, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.registrar_accion(IN p_id_empleado integer, IN p_accion character varying, IN p_tabla_afectada character varying, IN p_id_registro_afectado bigint, IN p_codigo_permiso character varying, IN p_resultado character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_permiso INTEGER;
BEGIN
    v_id_permiso := COALESCE(
        (SELECT id FROM permiso WHERE codigo = p_codigo_permiso AND estado = TRUE),
        (SELECT id FROM permiso WHERE estado = TRUE ORDER BY id LIMIT 1),
        1
    );

    INSERT INTO bitacoraaccion (idempleado, idpermiso, accion, tablaafectada, idregistroafectado, equipo, ip, resultado)
    VALUES (
        p_id_empleado,
        v_id_permiso,
        p_accion,
        p_tabla_afectada,
        p_id_registro_afectado,
        p_equipo,
        p_ip,
        COALESCE(p_resultado, 'EXITOSO')
    );
END;
$$;


ALTER PROCEDURE public.registrar_accion(IN p_id_empleado integer, IN p_accion character varying, IN p_tabla_afectada character varying, IN p_id_registro_afectado bigint, IN p_codigo_permiso character varying, IN p_resultado character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: registrar_caja(integer, character varying, boolean); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.registrar_caja(p_idsucursal integer, p_nombre character varying, p_activo boolean) RETURNS integer
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_activas INTEGER;
    v_duplicado INTEGER;
    v_id INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_activas
      FROM caja
     WHERE idsucursal = p_idsucursal AND activo = TRUE;

    IF v_activas >= 2 THEN
        RAISE EXCEPTION 'La sucursal ya tiene dos cajas activas.';
    END IF;

    SELECT COUNT(*) INTO v_duplicado
      FROM caja
     WHERE idsucursal = p_idsucursal AND nombre = p_nombre;

    IF v_duplicado > 0 THEN
        RAISE EXCEPTION 'Ya existe una caja con ese nombre en esta sucursal.';
    END IF;

    INSERT INTO caja (nombre, idsucursal, activo)
    VALUES (p_nombre, p_idsucursal, p_activo)
    RETURNING id INTO v_id;

    RETURN v_id;
END;
$$;


ALTER FUNCTION public.registrar_caja(p_idsucursal integer, p_nombre character varying, p_activo boolean) OWNER TO burguer;

--
-- Name: registrar_mesa(integer, integer, integer, boolean, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: burguer
--

CREATE PROCEDURE public.registrar_mesa(IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_id
      FROM mesa
     WHERE idsucursal = p_idsucursal AND numeromesa = p_numeromesa;

    IF v_id > 0 THEN
        RAISE EXCEPTION 'Ya existe una mesa con ese número en esta sucursal.';
    END IF;

    INSERT INTO mesa (numeromesa, capacidad, estado, idsucursal)
    VALUES (p_numeromesa, p_capacidad, p_estado, p_idsucursal)
    RETURNING id INTO v_id;

    CALL registrar_accion(
        p_id_empleado,
        'CREATE',
        'mesa',
        v_id,
        p_codigo_permiso,
        'EXITOSO',
        p_equipo,
        p_ip
    );
END;
$$;


ALTER PROCEDURE public.registrar_mesa(IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO burguer;

--
-- Name: verificar_bloqueo_y_desbloquear(integer); Type: FUNCTION; Schema: public; Owner: burguer
--

CREATE FUNCTION public.verificar_bloqueo_y_desbloquear(p_id_cuenta integer) RETURNS boolean
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_hasta TIMESTAMP;
BEGIN
	SELECT cu.bloqueadohasta INTO v_hasta FROM cuentausuario cu WHERE cu.idempleado = p_id_cuenta;
    if now()> v_hasta then
        UPDATE cuentausuario cu SET bloqueado = FALSE, bloqueadohasta = NULL, intentosfallidos = 0
		WHERE cu.idempleado = p_id_cuenta;
        RETURN TRUE;
	end if;
    return false;
END;
$$;


ALTER FUNCTION public.verificar_bloqueo_y_desbloquear(p_id_cuenta integer) OWNER TO burguer;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: aperturacaja; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.aperturacaja (
    id integer NOT NULL,
    fechaapertura timestamp without time zone NOT NULL,
    montoinicial numeric(10,2) NOT NULL,
    estado public.tipoestadocaja DEFAULT 'ABIERTA'::public.tipoestadocaja NOT NULL,
    idcaja integer NOT NULL,
    idempleado integer NOT NULL,
    CONSTRAINT aperturacaja_montoinicial_check CHECK ((montoinicial >= (0)::numeric))
);


ALTER TABLE public.aperturacaja OWNER TO burguer;

--
-- Name: aperturacaja_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.aperturacaja ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.aperturacaja_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bitacoraacceso; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.bitacoraacceso (
    id bigint NOT NULL,
    idempleado integer,
    fechahora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tipoacceso character varying(20) NOT NULL,
    equipo character varying(100),
    ip character varying(45),
    resultado character varying(20) NOT NULL,
    detalle character varying(255)
);


ALTER TABLE public.bitacoraacceso OWNER TO burguer;

--
-- Name: bitacoraacceso_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.bitacoraacceso ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.bitacoraacceso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bitacoraaccion; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.bitacoraaccion (
    id bigint NOT NULL,
    idempleado integer NOT NULL,
    idpermiso integer NOT NULL,
    fechahora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    accion character varying(10) NOT NULL,
    tablaafectada character varying(100),
    idregistroafectado bigint,
    equipo character varying(150),
    ip character varying(45),
    resultado character varying(255) DEFAULT 'EXITOSO'::character varying NOT NULL
);


ALTER TABLE public.bitacoraaccion OWNER TO burguer;

--
-- Name: bitacoraaccion_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.bitacoraaccion ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.bitacoraaccion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: caja; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.caja (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    idsucursal integer NOT NULL
);


ALTER TABLE public.caja OWNER TO burguer;

--
-- Name: caja_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.caja ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.caja_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: categoria; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.categoria (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.categoria OWNER TO burguer;

--
-- Name: categoria_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.categoria ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.categoria_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cierrecaja; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.cierrecaja (
    id integer NOT NULL,
    fechacierre timestamp without time zone NOT NULL,
    fondoinicial numeric(10,2) NOT NULL,
    totalventasefectivo numeric(10,2) DEFAULT 0 NOT NULL,
    totalventasqr numeric(10,2) DEFAULT 0 NOT NULL,
    totalventastarjeta numeric(10,2) DEFAULT 0 NOT NULL,
    totalventas numeric(10,2) DEFAULT 0 NOT NULL,
    totalingresos numeric(10,2) DEFAULT 0 NOT NULL,
    totalegresos numeric(10,2) DEFAULT 0 NOT NULL,
    efectivoesperado numeric(10,2) NOT NULL,
    efectivocontado numeric(10,2) NOT NULL,
    diferencia numeric(10,2) NOT NULL,
    observacion character varying(250),
    idaperturacaja integer NOT NULL,
    idempleado integer NOT NULL
);


ALTER TABLE public.cierrecaja OWNER TO burguer;

--
-- Name: cierrecaja_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.cierrecaja ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cierrecaja_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: configuracion_sistema; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.configuracion_sistema (
    id integer NOT NULL,
    clave character varying(100) NOT NULL,
    valor text,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.configuracion_sistema OWNER TO burguer;

--
-- Name: configuracion_sistema_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.configuracion_sistema ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.configuracion_sistema_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cuentausuario; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.cuentausuario (
    id integer NOT NULL,
    idempleado integer NOT NULL,
    usuario character varying(50) NOT NULL,
    correo character varying(150) NOT NULL,
    passwordhash character varying(255),
    googlesub character varying(255),
    intentosfallidos integer DEFAULT 0 NOT NULL,
    bloqueado boolean DEFAULT false NOT NULL,
    bloqueadohasta timestamp without time zone,
    fechacreacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ultimoacceso timestamp without time zone
);


ALTER TABLE public.cuentausuario OWNER TO burguer;

--
-- Name: cuentausuario_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.cuentausuario ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cuentausuario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detallepedido; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.detallepedido (
    id integer NOT NULL,
    cantidad integer NOT NULL,
    preciounitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    descuento numeric(10,2) DEFAULT 0 NOT NULL,
    total numeric(10,2) NOT NULL,
    idpedido integer NOT NULL,
    idproductosucursal integer,
    idpromocion integer,
    CONSTRAINT detallepedido_cantidad_check CHECK ((cantidad > 0)),
    CONSTRAINT detallepedido_check CHECK (((idproductosucursal IS NOT NULL) OR (idpromocion IS NOT NULL))),
    CONSTRAINT detallepedido_descuento_check CHECK ((descuento >= (0)::numeric)),
    CONSTRAINT detallepedido_preciounitario_check CHECK ((preciounitario >= (0)::numeric)),
    CONSTRAINT detallepedido_subtotal_check CHECK ((subtotal >= (0)::numeric)),
    CONSTRAINT detallepedido_total_check CHECK ((total >= (0)::numeric))
);


ALTER TABLE public.detallepedido OWNER TO burguer;

--
-- Name: detallepedido_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.detallepedido ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.detallepedido_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detallereceta; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.detallereceta (
    id integer NOT NULL,
    idproducto integer NOT NULL,
    idinsumo integer NOT NULL,
    cantidad numeric(12,3) NOT NULL,
    CONSTRAINT detallereceta_cantidad_check CHECK ((cantidad > (0)::numeric))
);


ALTER TABLE public.detallereceta OWNER TO burguer;

--
-- Name: detallereceta_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.detallereceta ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.detallereceta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: diaatencion; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.diaatencion (
    id integer NOT NULL,
    nombredia character varying(20) NOT NULL,
    numerodia integer NOT NULL
);


ALTER TABLE public.diaatencion OWNER TO burguer;

--
-- Name: diaatencion_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.diaatencion ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.diaatencion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: empleado; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.empleado (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100) NOT NULL,
    telefono character varying(15),
    estado boolean DEFAULT true NOT NULL,
    fecharegistro timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    idjefe integer,
    idrol integer NOT NULL,
    idsucursal integer
);


ALTER TABLE public.empleado OWNER TO burguer;

--
-- Name: empleado_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.empleado ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.empleado_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: historialproducto; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.historialproducto (
    id integer NOT NULL,
    fecha_registro timestamp without time zone NOT NULL,
    precioanterior numeric(10,2) NOT NULL,
    precionuevo numeric(10,2) NOT NULL,
    idproductosucursal integer NOT NULL
);


ALTER TABLE public.historialproducto OWNER TO burguer;

--
-- Name: historialproducto_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.historialproducto ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.historialproducto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: horariosucursal; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.horariosucursal (
    idhorariosucursal integer NOT NULL,
    idsucursal integer NOT NULL,
    horaapertura time without time zone NOT NULL,
    horacierre time without time zone NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    iddiaatencion integer NOT NULL
);


ALTER TABLE public.horariosucursal OWNER TO burguer;

--
-- Name: horariosucursal_idhorariosucursal_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.horariosucursal ALTER COLUMN idhorariosucursal ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.horariosucursal_idhorariosucursal_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: insumo; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.insumo (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255),
    idunidadmedida integer NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.insumo OWNER TO burguer;

--
-- Name: insumo_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.insumo ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.insumo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: inventariosucursal; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.inventariosucursal (
    id integer NOT NULL,
    idsucursal integer NOT NULL,
    idinsumo integer NOT NULL,
    cantidad numeric(12,3) DEFAULT 0 NOT NULL,
    cantidadminima numeric(12,3),
    fechaactualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT inventariosucursal_cantidad_check CHECK ((cantidad >= (0)::numeric)),
    CONSTRAINT inventariosucursal_cantidadminima_check CHECK (((cantidadminima IS NULL) OR (cantidadminima >= (0)::numeric)))
);


ALTER TABLE public.inventariosucursal OWNER TO burguer;

--
-- Name: inventariosucursal_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.inventariosucursal ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.inventariosucursal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: mesa; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.mesa (
    id integer NOT NULL,
    numeromesa integer NOT NULL,
    capacidad integer DEFAULT 4 NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    idsucursal integer NOT NULL
);


ALTER TABLE public.mesa OWNER TO burguer;

--
-- Name: mesa_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.mesa ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mesa_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: metodopago; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.metodopago (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    gateway character varying(50)
);


ALTER TABLE public.metodopago OWNER TO burguer;

--
-- Name: metodopago_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.metodopago ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.metodopago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO burguer;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO burguer;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: burguer
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: movimientocaja; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.movimientocaja (
    id integer NOT NULL,
    fechamovimiento timestamp without time zone NOT NULL,
    tipomovimiento public.tipomovimientocaja NOT NULL,
    concepto character varying(150) NOT NULL,
    monto numeric(10,2) NOT NULL,
    observacion character varying(250),
    idaperturacaja integer NOT NULL,
    idempleado integer NOT NULL,
    CONSTRAINT movimientocaja_monto_check CHECK ((monto > (0)::numeric))
);


ALTER TABLE public.movimientocaja OWNER TO burguer;

--
-- Name: movimientocaja_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.movimientocaja ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.movimientocaja_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: movimientoinventario; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.movimientoinventario (
    id integer NOT NULL,
    idinventariosucursal integer NOT NULL,
    tipomovimiento public.tipomovimientoinventario NOT NULL,
    cantidad numeric(12,3) NOT NULL,
    cantidadanterior numeric(12,3) NOT NULL,
    cantidadnueva numeric(12,3) NOT NULL,
    fechamovimiento timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    idempleado integer,
    observacion character varying(255),
    CONSTRAINT movimientoinventario_cantidad_check CHECK ((cantidad > (0)::numeric))
);


ALTER TABLE public.movimientoinventario OWNER TO burguer;

--
-- Name: movimientoinventario_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.movimientoinventario ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.movimientoinventario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pago; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.pago (
    id integer NOT NULL,
    idventa integer NOT NULL,
    idmetodopago integer NOT NULL,
    idaperturacaja integer NOT NULL,
    montopago numeric(10,2) NOT NULL,
    estadopago public.tipoestadopago DEFAULT 'PENDIENTE'::public.tipoestadopago NOT NULL,
    montorecibido numeric(10,2),
    cambioentregado numeric(10,2) DEFAULT 0 NOT NULL,
    referenciaexterna character varying(200),
    gateway character varying(50),
    codigoautorizacion character varying(100),
    idtransaccion character varying(200),
    ultimos4 character varying(4),
    marcatarjeta character varying(30),
    fechapago timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    observacionpago character varying(200)
);


ALTER TABLE public.pago OWNER TO burguer;

--
-- Name: pago_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.pago ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pedido; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.pedido (
    id integer NOT NULL,
    nropedido integer NOT NULL,
    fecha timestamp without time zone NOT NULL,
    total numeric(10,2) NOT NULL,
    estadopedido public.tipoestado_pedido DEFAULT 'PENDIENTE'::public.tipoestado_pedido NOT NULL,
    tipopedido public.tipo_pedido DEFAULT 'MESA'::public.tipo_pedido NOT NULL,
    idsucursal integer NOT NULL,
    idempleado integer NOT NULL,
    idmesa integer,
    subtotalpedido numeric(10,2) NOT NULL,
    descuentopedido numeric(10,2) DEFAULT 0 NOT NULL,
    horarecogida timestamp without time zone,
    observaciones text,
    idaperturacaja integer,
    CONSTRAINT pedido_descuentopedido_check CHECK ((descuentopedido >= (0)::numeric)),
    CONSTRAINT pedido_subtotalpedido_check CHECK ((subtotalpedido >= (0)::numeric)),
    CONSTRAINT pedido_total_check CHECK ((total >= (0)::numeric))
);


ALTER TABLE public.pedido OWNER TO burguer;

--
-- Name: pedido_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.pedido ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.pedido_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: permiso; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.permiso (
    id integer NOT NULL,
    codigo character varying(100) NOT NULL,
    nombre character varying(100) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.permiso OWNER TO burguer;

--
-- Name: permiso_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.permiso ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.permiso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: producto; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.producto (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(200) NOT NULL,
    photo character varying(255),
    estado boolean DEFAULT true NOT NULL,
    idcategoria integer NOT NULL
);


ALTER TABLE public.producto OWNER TO burguer;

--
-- Name: producto_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.producto ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.producto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: promocion; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.promocion (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    tipodescuento public.tipodescuento DEFAULT 'MONTO_FIJO'::public.tipodescuento NOT NULL,
    valordescuento numeric(10,2) NOT NULL,
    fechainicio timestamp without time zone NOT NULL,
    fechafin timestamp without time zone NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    photo character varying(255),
    CONSTRAINT promocion_check CHECK ((fechafin > fechainicio)),
    CONSTRAINT promocion_check1 CHECK (((tipodescuento = 'MONTO_FIJO'::public.tipodescuento) OR (valordescuento <= (100)::numeric))),
    CONSTRAINT promocion_valordescuento_check CHECK ((valordescuento >= (0)::numeric))
);


ALTER TABLE public.promocion OWNER TO burguer;

--
-- Name: promocion_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.promocion ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.promocion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: promocion_sucursal_producto; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.promocion_sucursal_producto (
    idpromocion integer NOT NULL,
    idsucursalproducto integer NOT NULL,
    cantidad_producto integer DEFAULT 1 NOT NULL,
    CONSTRAINT promocion_sucursal_producto_cantidad_producto_check CHECK ((cantidad_producto >= 1))
);


ALTER TABLE public.promocion_sucursal_producto OWNER TO burguer;

--
-- Name: rol; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.rol (
    id integer NOT NULL,
    claverol character varying(50) NOT NULL,
    nombrerol character varying(50) NOT NULL
);


ALTER TABLE public.rol OWNER TO burguer;

--
-- Name: rol_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.rol ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.rol_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: rolpermiso; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.rolpermiso (
    idrol integer NOT NULL,
    idpermiso integer NOT NULL
);


ALTER TABLE public.rolpermiso OWNER TO burguer;

--
-- Name: sucursal; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.sucursal (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    direccion character varying(200) NOT NULL,
    telefono character varying(15) NOT NULL,
    latitud numeric(10,8) NOT NULL,
    longitud numeric(11,8) NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    idempleadogerente integer
);


ALTER TABLE public.sucursal OWNER TO burguer;

--
-- Name: sucursal_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.sucursal ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.sucursal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sucursal_producto; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.sucursal_producto (
    id integer NOT NULL,
    idsucursal integer NOT NULL,
    idproducto integer NOT NULL,
    precioproducto numeric(10,2) NOT NULL,
    disponibilidad boolean DEFAULT true NOT NULL,
    CONSTRAINT sucursal_producto_precioproducto_check CHECK ((precioproducto >= (0)::numeric))
);


ALTER TABLE public.sucursal_producto OWNER TO burguer;

--
-- Name: sucursal_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.sucursal_producto ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.sucursal_producto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: unidadmedida; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.unidadmedida (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    abreviatura character varying(10) NOT NULL,
    tipo character varying(20) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.unidadmedida OWNER TO burguer;

--
-- Name: unidadmedida_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.unidadmedida ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.unidadmedida_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: venta; Type: TABLE; Schema: public; Owner: burguer
--

CREATE TABLE public.venta (
    id integer NOT NULL,
    fechaventa timestamp without time zone NOT NULL,
    totalventa numeric(10,2) NOT NULL,
    estadoventa public.tipoestadoventa DEFAULT 'PENDIENTE'::public.tipoestadoventa NOT NULL,
    idempleado integer NOT NULL,
    idpedido integer
);


ALTER TABLE public.venta OWNER TO burguer;

--
-- Name: venta_id_seq; Type: SEQUENCE; Schema: public; Owner: burguer
--

ALTER TABLE public.venta ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.venta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Data for Name: aperturacaja; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.aperturacaja (id, fechaapertura, montoinicial, estado, idcaja, idempleado) FROM stdin;
83	2026-09-19 14:13:28	50.00	CERRADA	3	15
84	2026-09-19 21:24:20	50.00	CERRADA	3	15
85	2026-09-19 21:31:05	0.00	CERRADA	3	39
34	2026-09-18 06:26:48	50.00	CERRADA	3	45
86	2026-09-25 14:16:09	0.00	CERRADA	3	57
87	2026-09-25 16:54:36	50.00	CERRADA	3	57
88	2026-09-26 10:59:14	100.00	CERRADA	3	56
89	2026-09-28 15:54:36	0.00	CERRADA	6	2
\.


--
-- Data for Name: bitacoraacceso; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.bitacoraacceso (id, idempleado, fechahora, tipoacceso, equipo, ip, resultado, detalle) FROM stdin;
1	2	2026-09-29 09:47:57.945702	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	181.115.244.154	EXITOSO	Acceso correcto
2	2	2026-10-07 15:39:20.482839	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	FALLIDO	Contraseña incorrecta
3	2	2026-10-07 15:39:27.769068	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	FALLIDO	Cuenta bloqueada
4	2	2026-10-07 15:41:29.425719	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	EXITOSO	Acceso correcto
5	2	2026-10-07 15:43:57.22834	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	EXITOSO	Sesión cerrada
6	2	2026-10-07 16:01:30.94085	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	EXITOSO	Acceso correcto
7	2	2026-10-07 16:40:57.571303	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	EXITOSO	Sesión cerrada
8	2	2026-10-07 16:49:58.813028	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.65	EXITOSO	Acceso correcto
9	2	2026-10-11 11:20:48.466228	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.205	EXITOSO	Acceso correcto
10	2	2026-10-12 21:45:12.071683	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	166.114.161.205	EXITOSO	Acceso correcto
\.


--
-- Data for Name: bitacoraaccion; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.bitacoraaccion (id, idempleado, idpermiso, fechahora, accion, tablaafectada, idregistroafectado, equipo, ip, resultado) FROM stdin;
1	2	18	2026-09-24 09:21:09.593142	UPDATE	cuentausuario	2	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
2	2	18	2026-09-24 09:21:22.527501	UPDATE	cuentausuario	2	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
3	2	92	2026-09-24 17:46:41.904716	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	189.28.70.203	EXITOSO
4	2	92	2026-09-24 17:46:54.244751	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	189.28.70.203	EXITOSO
5	2	92	2026-09-24 17:46:58.512539	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	189.28.70.203	EXITOSO
6	2	14	2026-09-25 13:51:37.242818	CREATE	empleado	56	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
7	2	26	2026-09-25 13:56:35.293965	UPDATE	sucursal	6	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
8	56	93	2026-09-25 14:00:20.894041	CREATE	movimientoinventario	12	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
9	56	14	2026-09-25 14:08:15.031317	CREATE	empleado	57	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
10	56	14	2026-09-25 14:09:33.179022	CREATE	empleado	58	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
11	57	69	2026-09-25 14:16:09.594676	CREATE	aperturacaja	86	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
12	57	87	2026-09-25 14:17:26.379363	CREATE	pedido	186	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
13	57	90	2026-09-25 14:18:14.677627	DELETE	pedido	186	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
14	57	93	2026-09-25 14:41:44.917812	CREATE	movimientoinventario	13	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
15	56	15	2026-09-25 14:43:26.812231	UPDATE	empleado	58	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
16	58	87	2026-09-25 14:44:57.719365	CREATE	pedido	187	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
17	58	88	2026-09-25 14:45:54.992428	UPDATE	pedido	187	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
18	58	88	2026-09-25 14:46:12.921012	UPDATE	pedido	187	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
19	57	80	2026-09-25 14:52:22.911469	CREATE	venta	122	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
20	57	88	2026-09-25 14:52:22.911469	UPDATE	pedido	187	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
21	57	93	2026-09-25 15:14:54.796588	CREATE	movimientoinventario	14	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
22	57	95	2026-09-25 15:15:10.757247	UPDATE	inventariosucursal	14	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
23	2	43	2026-09-25 16:11:39.046376	CREATE	producto	51	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
24	57	77	2026-09-25 16:42:23.554748	CREATE	cierrecaja	82	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
25	57	69	2026-09-25 16:54:36.368091	CREATE	aperturacaja	87	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
26	57	87	2026-09-25 16:55:32.712722	CREATE	pedido	188	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
27	57	87	2026-09-25 16:55:51.016515	CREATE	pedido	189	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.114.25	EXITOSO
28	56	77	2026-09-26 10:10:26.403717	CREATE	cierrecaja	83	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
29	56	69	2026-09-26 10:59:14.357161	CREATE	aperturacaja	88	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
30	56	87	2026-09-26 10:59:27.376557	CREATE	pedido	190	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
31	56	80	2026-09-26 10:59:46.744077	CREATE	venta	123	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
32	56	88	2026-09-26 10:59:46.744077	UPDATE	pedido	190	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
33	56	47	2026-09-26 11:12:55.129795	CREATE	sucursal_producto	11	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
34	56	50	2026-09-26 11:13:45.134683	UPDATE	sucursal_producto	6	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
35	56	50	2026-09-26 11:14:24.666212	UPDATE	sucursal_producto	4	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
36	56	57	2026-09-26 11:27:42.686179	DELETE	insumo	2	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
37	56	55	2026-09-26 11:27:47.097141	CREATE	insumo	2	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
38	56	15	2026-09-26 16:42:00.13376	UPDATE	empleado	56	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
39	2	15	2026-09-26 17:17:30.298892	UPDATE	empleado	15	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
40	56	17	2026-09-26 19:30:55.805703	UPDATE	cuentausuario	46	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
41	56	17	2026-09-26 19:32:04.381303	UPDATE	cuentausuario	48	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
42	56	17	2026-09-26 19:32:11.904672	UPDATE	cuentausuario	37	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
43	56	17	2026-09-26 19:32:15.665158	UPDATE	cuentausuario	45	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
44	56	17	2026-09-26 19:32:28.005597	UPDATE	cuentausuario	48	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
45	56	17	2026-09-26 19:34:49.274745	UPDATE	cuentausuario	48	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
46	56	17	2026-09-26 19:35:04.363089	UPDATE	cuentausuario	48	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
47	2	26	2026-09-26 19:56:21.163369	UPDATE	sucursal	6	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
48	2	17	2026-09-26 20:50:49.147786	UPDATE	cuentausuario	41	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
49	2	17	2026-09-26 20:50:52.755894	UPDATE	cuentausuario	41	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
50	2	14	2026-09-26 20:54:12.051246	CREATE	empleado	59	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
51	2	23	2026-09-26 21:26:06.020961	CREATE	sucursal	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
52	2	26	2026-09-26 21:26:06.03103	UPDATE	sucursal	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
53	2	24	2026-09-26 21:28:59.132323	UPDATE	sucursal	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
54	2	31	2026-09-26 21:36:17.851564	CREATE	mesa	22	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
55	2	46	2026-09-26 21:39:58.894641	DELETE	producto	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
56	2	43	2026-09-26 21:40:02.359282	CREATE	producto	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.112.236	EXITOSO
57	56	35	2026-09-27 10:02:20.364934	CREATE	horariosucursal	9	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
58	56	35	2026-09-27 10:05:02.843828	CREATE	horariosucursal	10	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
59	2	39	2026-09-27 11:21:03.81132	CREATE	categoria	7	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
60	2	46	2026-09-27 11:24:40.54301	DELETE	producto	10	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
61	2	49	2026-09-27 12:02:14.293715	UPDATE	sucursal_producto	8	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
62	2	50	2026-09-27 12:03:02.802119	UPDATE	sucursal_producto	4	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.222	EXITOSO
63	2	77	2026-09-27 18:49:20.931858	CREATE	cierrecaja	84	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36	192.223.108.247	EXITOSO
64	2	69	2026-09-28 15:54:36.835268	CREATE	aperturacaja	89	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
65	2	17	2026-09-28 15:55:47.57513	UPDATE	cuentausuario	45	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
66	2	18	2026-09-28 15:56:10.834255	UPDATE	cuentausuario	45	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
67	2	17	2026-09-28 15:56:16.915141	UPDATE	cuentausuario	45	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
68	2	17	2026-09-28 15:56:19.699473	UPDATE	cuentausuario	45	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
69	2	15	2026-09-28 15:56:29.464838	UPDATE	empleado	45	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
70	2	26	2026-09-28 15:57:48.426364	UPDATE	sucursal	7	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
71	45	47	2026-09-28 15:58:59.397458	CREATE	sucursal_producto	12	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
72	45	47	2026-09-28 15:59:12.339766	CREATE	sucursal_producto	13	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
73	45	47	2026-09-28 15:59:34.397403	CREATE	sucursal_producto	14	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
74	45	87	2026-09-28 16:00:04.001451	CREATE	pedido	191	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
75	45	88	2026-09-28 16:00:37.965896	UPDATE	pedido	191	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
76	45	80	2026-09-28 16:02:46.098441	CREATE	venta	124	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
77	45	88	2026-09-28 16:02:46.098441	UPDATE	pedido	191	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
78	45	77	2026-09-28 16:04:06.496049	CREATE	cierrecaja	85	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	166.114.160.98	EXITOSO
\.


--
-- Data for Name: caja; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.caja (id, nombre, activo, idsucursal) FROM stdin;
3	Caja principal	t	6
7	Caja2	t	7
6	Caja1	t	7
\.


--
-- Data for Name: categoria; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.categoria (id, nombre, estado) FROM stdin;
1	HAMBURGUESAS	t
2	VARIADOS	t
3	PORCIONES	t
4	GASEOSAS	t
5	BEBIDA	t
6	ASADO	t
7	Parrilladas	t
\.


--
-- Data for Name: cierrecaja; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.cierrecaja (id, fechacierre, fondoinicial, totalventasefectivo, totalventasqr, totalventastarjeta, totalventas, totalingresos, totalegresos, efectivoesperado, efectivocontado, diferencia, observacion, idaperturacaja, idempleado) FROM stdin;
30	2026-09-19 14:09:31	50.00	196.00	0.00	0.00	196.00	70.00	0.00	316.00	190.00	-126.00	total en caja cuento con eso, pero me pagaron 6 bs por QR	34	45
79	2026-09-19 15:59:48	50.00	478.00	0.00	0.00	478.00	50.00	20.00	558.00	558.00	0.00	sin observacion	83	15
80	2026-09-19 21:28:35	50.00	261.00	0.00	0.00	261.00	50.00	20.00	341.00	420.00	79.00	\N	84	15
81	2026-09-20 01:03:58	0.00	187.00	0.00	0.00	187.00	100.00	30.00	257.00	250.00	-7.00	\N	85	39
82	2026-09-25 16:42:23	0.00	61.00	0.00	0.00	61.00	0.00	0.00	61.00	61.00	0.00	\N	86	57
83	2026-09-26 10:10:26	50.00	0.00	0.00	0.00	0.00	0.00	0.00	50.00	50.00	0.00	\N	87	57
84	2026-09-27 18:49:20	100.00	32.00	0.00	0.00	32.00	0.00	0.00	132.00	132.00	0.00	\N	88	56
85	2026-09-28 16:04:06	0.00	55.00	0.00	0.00	55.00	0.00	0.00	55.00	50.00	-5.00	Me faltó aadjdkxj	89	2
\.


--
-- Data for Name: configuracion_sistema; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.configuracion_sistema (id, clave, valor, descripcion, estado) FROM stdin;
7	SITIO_HABILITAR_CARRUSEL	1	Muestra el carrusel de imágenes en el inicio	t
13	TEMA_COLOR_PRIMARIO	#4b5563	Color principal (botones, barra de navegación)	t
14	TEMA_COLOR_PRIMARIO_OSCURO	#374151	Color principal oscuro (hover de botones)	t
15	TEMA_COLOR_FONDO	#f4f5f7	Color de fondo de la página	t
16	TEMA_COLOR_TEXTO	#1f2937	Color de texto	t
19	SITIO_MANTENIMIENTO_MENSAJE	Estamos realizando mejoras en la página. Volveremos muy pronto. Gracias por tu paciencia.	Mensaje que verán los visitantes mientras el sitio está en mantenimiento	t
17	TEMA_COLOR_DETALLE	#10b981	Color de detalle (acentos, degradados)	t
2	SITIO_ESLOGAN	Sabores que se quedan	Eslogan que aparece en el inicio	t
4	SITIO_EMAIL	contacto@burguerwilly.com	Correo de contacto	t
5	SITIO_DIRECCION	Av. Principal 123, Lima	Dirección principal	t
6	SITIO_HORARIO	Lun - Dom: 12:00 p.m. - 11:00 p.m.	Horario de atención general	t
11	IMAGENES_CARRUSEL	[{"titulo":"Salchipapas","subtitulo":"Papas rusticas willi, salchicha viena Santa Fe bañadas de salsa de la casa","imagen":"https:\\/\\/lh3.googleusercontent.com\\/gps-cs-s\\/AHRPTWlAe2Mgkh5jAsg_geFM6s1Dy9e1xzfCeLogvX4GhN4bHTpJGFgRrWHurshNy-4sj9iCOTaJwQ5jWn4kqplFBHKAJps1vh0tj8Bybhj0X7iFWFruaqlALVDT7iPuqssSPU8H50-7jA=s680-w680-h510-rw","boton_texto":"Salchipapas","boton_url":"https:\\/\\/lh3.googleusercontent.com\\/gps-cs-s\\/AHRPTWlAe2Mgkh5jAsg_geFM6s1Dy9e1xzfCeLogvX4GhN4bHTpJGFgRrWHurshNy-4sj9iCOTaJwQ5jWn4kqplFBHKAJps1vh0tj8Bybhj0X7iFWFruaqlALVDT7iPuqssSPU8H50-7jA=s680-w680-h510-rw","orden":1},{"titulo":"lomitos","subtitulo":"lomitos es el metodofd","imagen":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQ3WyIx8squ1a9hAjCFBx1X_fi75pX1PktFH0iJZL1ak6_XRtP7YSAH2RwTtLWjwu3tNLVK5pswmbNljKeuklDJRmszciOwavBG5ZYWncY&s=10","boton_texto":"lomitos","boton_url":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQ3WyIx8squ1a9hAjCFBx1X_fi75pX1PktFH0iJZL1ak6_XRtP7YSAH2RwTtLWjwu3tNLVK5pswmbNljKeuklDJRmszciOwavBG5ZYWncY&s=10","orden":2},{"titulo":"","subtitulo":"","imagen":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcS7Gjf6iILZ5eUsVJZ4Q2FvjymhDUibOKWgQa7gZRZ9PA&s=10","boton_texto":"","boton_url":"","orden":3}]	Imágenes del carrusel del inicio (JSON)	t
1	SITIO_NOMBRE	🍔 Burger Willy and Grill	Nombre comercial del sistema/negocio	t
3	SITIO_TELEFONO	70005937	Teléfono de contacto principal	t
8	SITIO_HABILITAR_MENU	1	Muestra la sección de menú/productos	t
10	SITIO_HABILITAR_SUCURSALES	1	Muestra la sección de sucursales	t
9	SITIO_HABILITAR_PROMOCIONES	1	Muestra la sección de promociones	t
18	SITIO_MANTENIMIENTO_ACTIVO	0	Activa o desactiva el modo mantenimiento de la página	t
\.


--
-- Data for Name: cuentausuario; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.cuentausuario (id, idempleado, usuario, correo, passwordhash, googlesub, intentosfallidos, bloqueado, bloqueadohasta, fechacreacion, ultimoacceso) FROM stdin;
36	38	tatiana	tatiana@burguer.com	$2y$12$OlkThx7VUK8XiBKJXR3AcuNWjFCDtEjRCom2kqPWgMKetCVz5zVza	\N	0	f	\N	2026-09-14 02:14:44	2026-09-14 02:14:56
40	40	ramon.nogales	ramon.nogales@gmail.com	$2y$12$xIPXftlpojs29G08/gPzbu98cGE0YKtpNxWMUKslTSgSGaaWI4oX2	\N	0	f	\N	2026-09-14 12:02:48	2026-09-14 12:02:56
15	15	laura	laura@burger.com	$2y$12$bjXArvvqbJL8rI/RCIdWYus.9AOusbyLcIZ/R/RjPk.du9QuBAyrG	\N	3	t	2026-09-21 10:38:25	2026-09-13 00:38:54	2026-09-14 16:17:01
49	58	vanesa	vaness@gmail.com	$2y$12$mfTfI4qOkAqA3mp2SFXTDO8A.BkJXq2SQXdbwkXFf3DmTR218xQza	\N	0	f	\N	2026-09-25 14:09:33	\N
47	56	nicol.honor.caceres	pwendynicol@gmail.com	$2y$12$48/SlDZc0qC/vcwEl0XAa.WgiUw.bVCWPbjRdOL5zZhtf1wpsPvCq	\N	2	f	\N	2026-09-25 13:51:37	\N
46	46	andres.guzman	andres.guzman@gmail.com	$2y$12$6kONaY9gHHzWrrcIuv4G2uGiC.5o5xh6pk55pb2gWXRWMdrTALQXa	\N	0	f	\N	2026-09-14 16:28:21	2026-09-15 13:26:44
37	39	juan.pinto	juan.pinto@gmail.com	$2y$12$.1QU.pEnPRbVevkJlW8ogOMtd6WKCdKdLGLzNE9.JpG54QhaZyw7G	\N	0	t	\N	2026-09-14 02:26:07	2026-09-14 17:24:34
41	41	wendy.follow	wendy.follow@gmail.com	$2y$12$3CPtzQJzbMvtHurgL/uT6uzKYfHw7FxK5ksch.Sa/iZER9yo/MNo.	\N	1	f	\N	2026-09-14 15:29:08	2026-09-14 15:46:04
50	59	leidy	leidymara@gmail.com	$2y$12$l5LbYBlGTnjN3kwOqA9nA.NwP1fzborUQay5tFGp0KPzNeaoX6aau	\N	0	f	\N	2026-09-26 20:54:12	\N
45	45	elena.nogales	elena.nogales@gmail.com	$2y$12$s0jtiYtZ2thNqiWGA6sdaOH1zoIXnjWZCyEfQSgp.XNR8wBn8gWMu	\N	2	f	\N	2026-09-14 16:16:21	\N
48	57	oliver	shinigami@gmail.com	$2y$12$18FJL1QkNfgcJS8dDwWPuOqHZJH5fC2e55bVHDBQTM4kn.JEv0F9u	\N	0	f	\N	2026-09-25 14:08:15	\N
2	2	admin	marcelo.cruz10@hotmail.com	$2y$12$KDkpCgR1JaSnhAgqGtoK0.ZM7R3u3mS9AnKSqeQagFNm8pUsSjxvG	\N	1	f	\N	2026-09-12 23:29:22.975277	2026-09-16 21:27:07
\.


--
-- Data for Name: detallepedido; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.detallepedido (id, cantidad, preciounitario, subtotal, descuento, total, idpedido, idproductosucursal, idpromocion) FROM stdin;
164	1	8.00	8.00	0.00	8.00	112	8	\N
165	1	25.00	25.00	0.00	25.00	112	5	\N
232	1	8.00	8.00	0.00	8.00	159	8	\N
233	1	15.00	15.00	5.00	10.00	159	7	\N
241	2	32.00	64.00	4.00	60.00	165	4	\N
242	2	15.00	30.00	5.00	25.00	165	7	\N
243	2	32.00	64.00	0.00	64.00	166	4	\N
244	2	25.00	50.00	0.00	50.00	166	5	\N
245	1	8.00	8.00	0.00	8.00	167	8	\N
246	1	35.00	35.00	0.00	35.00	167	3	\N
247	2	15.00	30.00	5.00	25.00	168	7	\N
248	2	32.00	64.00	4.00	60.00	168	4	\N
249	2	15.00	30.00	5.00	25.00	169	7	\N
250	1	35.00	35.00	0.00	35.00	169	3	\N
251	1	8.00	8.00	0.00	8.00	170	8	\N
252	1	8.00	8.00	0.00	8.00	171	8	\N
253	1	8.00	8.00	0.00	8.00	172	8	\N
254	2	15.00	30.00	5.00	25.00	172	7	\N
255	2	32.00	64.00	4.00	60.00	172	4	\N
256	1	35.00	35.00	0.00	35.00	173	3	\N
257	1	32.00	32.00	0.00	32.00	173	4	\N
264	1	25.00	25.00	0.00	25.00	174	5	\N
265	1	23.00	23.00	0.00	23.00	174	6	\N
266	1	32.00	32.00	0.00	32.00	174	4	\N
267	1	35.00	35.00	0.00	35.00	174	3	\N
268	1	15.00	15.00	0.00	15.00	174	7	\N
269	1	18.00	18.00	0.00	18.00	174	9	\N
270	2	15.00	30.00	5.00	25.00	174	7	\N
271	2	32.00	64.00	4.00	60.00	174	4	\N
272	2	15.00	30.00	5.00	25.00	175	7	\N
273	2	32.00	64.00	4.00	60.00	175	4	\N
215	1	8.00	8.00	0.00	8.00	148	8	\N
216	1	32.00	32.00	0.00	32.00	148	4	\N
217	1	25.00	25.00	0.00	25.00	148	5	\N
274	2	15.00	30.00	5.00	25.00	176	7	\N
275	2	32.00	64.00	4.00	60.00	176	4	\N
276	1	15.00	15.00	0.00	15.00	177	7	\N
277	1	18.00	18.00	0.00	18.00	177	9	\N
278	1	28.00	28.00	0.00	28.00	177	10	\N
279	1	25.00	25.00	0.00	25.00	177	5	\N
280	1	23.00	23.00	0.00	23.00	177	6	\N
281	1	32.00	32.00	0.00	32.00	177	4	\N
282	1	35.00	35.00	0.00	35.00	177	3	\N
283	2	15.00	30.00	5.00	25.00	178	7	\N
284	2	32.00	64.00	4.00	60.00	178	4	\N
285	2	15.00	30.00	5.00	25.00	179	7	\N
286	2	32.00	64.00	4.00	60.00	179	4	\N
287	1	35.00	35.00	0.00	35.00	180	3	\N
288	1	32.00	32.00	0.00	32.00	180	4	\N
289	2	35.00	70.00	0.00	70.00	181	3	\N
290	2	15.00	30.00	5.00	25.00	182	7	\N
291	2	15.00	30.00	5.00	25.00	183	7	\N
292	2	15.00	30.00	5.00	25.00	184	7	\N
293	2	25.00	50.00	0.00	50.00	185	\N	6
137	1	32.00	32.00	0.00	32.00	96	4	\N
138	1	15.00	15.00	5.00	10.00	96	7	\N
139	1	8.00	8.00	0.00	8.00	97	8	\N
140	1	25.00	25.00	0.00	25.00	97	5	\N
141	1	23.00	23.00	0.00	23.00	97	6	\N
142	1	35.00	35.00	0.00	35.00	98	3	\N
143	1	32.00	32.00	0.00	32.00	98	4	\N
144	1	25.00	25.00	0.00	25.00	98	5	\N
294	1	32.00	32.00	0.00	32.00	186	4	\N
297	1	35.00	35.00	0.00	35.00	187	3	\N
298	1	18.00	18.00	0.00	18.00	187	9	\N
299	1	8.00	8.00	0.00	8.00	187	8	\N
300	1	32.00	32.00	0.00	32.00	188	4	\N
301	1	18.00	18.00	0.00	18.00	189	9	\N
302	1	28.00	28.00	0.00	28.00	189	10	\N
303	1	15.00	15.00	0.00	15.00	189	7	\N
304	1	32.00	32.00	0.00	32.00	190	4	\N
305	1	25.00	25.00	0.00	25.00	191	14	\N
306	1	30.00	30.00	0.00	30.00	191	13	\N
\.


--
-- Data for Name: detallereceta; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.detallereceta (id, idproducto, idinsumo, cantidad) FROM stdin;
3	39	5	1.000
4	1	2	0.250
5	33	6	1.000
6	9	3	1.000
12	6	2	1.000
13	5	3	1.000
14	5	2	270.000
16	3	3	1.000
7	11	2	135.000
17	11	3	1.000
8	13	2	135.000
18	13	3	1.000
9	12	2	135.000
19	12	3	1.000
10	10	2	135.000
20	10	3	1.000
11	4	2	135.000
21	4	3	1.000
22	38	7	1.000
23	44	8	300.000
24	49	8	400.000
25	50	8	350.000
15	3	2	120.000
26	3	9	25.000
27	3	10	1.000
28	3	11	1.000
29	3	12	2.000
30	3	13	10.000
31	3	14	3.000
\.


--
-- Data for Name: diaatencion; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.diaatencion (id, nombredia, numerodia) FROM stdin;
1	Lunes	1
2	Martes	2
3	Miércoles	3
4	Jueves	4
5	Viernes	5
6	Sábado	6
7	Domingo	7
\.


--
-- Data for Name: empleado; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.empleado (id, nombre, apellido, telefono, estado, fecharegistro, idjefe, idrol, idsucursal) FROM stdin;
55	GerentePrueba	PDF	00000000	t	2026-09-15 00:25:01	2	3	7
2	Marcelo	Cruz Nogales	70005937	t	2026-09-12 23:29:22.512172	\N	2	\N
58	Vanesa	Figueroa Sol	77334491	t	2026-09-25 14:09:32	56	5	6
15	Laura	Castro	73130624	t	2026-09-13 00:38:54	\N	5	\N
46	Andres	Guzman	\N	t	2026-09-14 16:28:21	56	5	6
39	Juan	Pinto	123456	f	2026-09-14 02:26:06	56	4	6
57	Oliver	Borda Salazar	75619466	t	2026-09-25 14:08:14	56	4	6
56	Wendy Nicol	Honor Caceres	62836139	t	2026-09-25 13:51:36	2	3	6
41	Wendy	Salazar	123456	t	2026-09-14 15:29:08	56	5	6
59	Leidy	Marañon Rosas	72618090	t	2026-09-26 20:54:11	2	3	\N
38	tatiana	guzman	\N	t	2026-09-14 02:14:44	45	5	\N
40	ramon	nogales	\N	t	2026-09-14 12:02:48	\N	3	7
45	elena	nogales	\N	t	2026-09-14 16:16:21	2	3	7
\.


--
-- Data for Name: historialproducto; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.historialproducto (id, fecha_registro, precioanterior, precionuevo, idproductosucursal) FROM stdin;
1	2026-09-17 04:10:39	32.00	35.00	3
2	2026-09-17 13:34:48	26.00	28.00	10
3	2026-09-27 12:02:14	8.00	10.00	8
\.


--
-- Data for Name: horariosucursal; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.horariosucursal (idhorariosucursal, idsucursal, horaapertura, horacierre, activo, iddiaatencion) FROM stdin;
7	6	16:00:00	22:30:00	t	3
6	6	16:00:00	22:30:00	t	2
5	6	16:00:00	22:30:00	t	1
8	7	10:30:00	15:00:00	t	7
9	6	11:00:00	22:00:00	t	7
10	6	00:06:00	23:00:00	t	6
\.


--
-- Data for Name: insumo; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.insumo (id, nombre, descripcion, idunidadmedida, estado) FROM stdin;
3	Pan de hamburguesa	Pan brioche	4	t
5	Gaseosa 1L	Gaseosa de 1 litro	6	t
6	Gaseosa 2L	Gaseosa de 2 Litros	6	t
7	Gaseosa 1.5L	Gaseosa de 1.5 Litro	6	t
8	Carne de Res	Carne de Res para Asado	3	t
9	Chorizo Viena	Chorizo Viena para dogor	3	t
10	Cebolla	Cebolla de rodaja	7	t
11	Pepinillo	Rodaja de Pepinillo	7	t
12	Lechuga	Rodaja de Lechuga	7	t
13	Choclo	cuchara de choclo	3	t
14	Tomate	Rodaja de Tomate	7	t
2	Carne de Burguer	Carne molida	3	t
\.


--
-- Data for Name: inventariosucursal; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.inventariosucursal (id, idsucursal, idinsumo, cantidad, cantidadminima, fechaactualizacion) FROM stdin;
6	6	2	40000.000	100.000	2026-09-15 00:31:52
5	6	6	52.000	5.000	2026-09-15 00:31:57
8	6	7	39.000	5.000	2026-09-15 00:59:16
10	7	5	20.000	50.000	2026-09-17 02:31:01
9	7	6	50.000	49.000	2026-09-17 02:31:16
11	6	12	200.000	30.000	2026-09-17 14:30:44
12	6	3	200.000	50.000	2026-09-25 14:00:20
13	6	13	200.000	100.000	2026-09-25 14:41:44
14	6	14	5.000	10.000	2026-09-25 15:15:10
\.


--
-- Data for Name: mesa; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.mesa (id, numeromesa, capacidad, estado, idsucursal) FROM stdin;
12	1	4	t	6
18	1	4	t	7
19	2	4	t	7
20	2	4	t	6
21	3	4	t	6
22	1	7	t	8
\.


--
-- Data for Name: metodopago; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.metodopago (id, nombre, activo, gateway) FROM stdin;
2	Efectivo	t	EFECTIVO
3	Tarjeta de crédito	t	TARJETA
4	Transferencia bancaria	t	BANCO
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_09_13_000001_create_configuracion_sistema_table	1
\.


--
-- Data for Name: movimientocaja; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.movimientocaja (id, fechamovimiento, tipomovimiento, concepto, monto, observacion, idaperturacaja, idempleado) FROM stdin;
120	2026-09-19 14:07:24	INGRESO	Ingresar monedas	50.00	\N	34	15
121	2026-09-19 14:08:30	INGRESO	falto monedas	20.00	\N	34	15
122	2026-09-19 14:36:30	INGRESO	Ingresar monedas	50.00	\N	83	15
123	2026-09-19 14:36:49	EGRESO	se necesito comprar pan	20.00	\N	83	15
124	2026-09-19 21:24:49	INGRESO	Ingresar monedas	50.00	\N	84	15
125	2026-09-19 21:25:00	EGRESO	se necesito comprar pan	20.00	\N	84	15
126	2026-09-19 21:31:22	INGRESO	monedas	50.00	\N	85	39
127	2026-09-19 21:31:39	INGRESO	mas monedas	50.00	\N	85	39
128	2026-09-19 21:32:01	EGRESO	plata para llevar comida	30.00	\N	85	39
\.


--
-- Data for Name: movimientoinventario; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.movimientoinventario (id, idinventariosucursal, tipomovimiento, cantidad, cantidadanterior, cantidadnueva, fechamovimiento, idempleado, observacion) FROM stdin;
5	5	ENTRADA	20.000	0.000	20.000	2026-09-14 23:39:10	39	Coca de 2 litros
6	6	ENTRADA	40000.000	0.000	40000.000	2026-09-14 23:39:53	39	40 Kg de carne
7	5	SALIDA	1.000	20.000	19.000	2026-09-14 23:40:25	39	El gerente saca una Coca
8	5	AJUSTE	1.000	19.000	1.000	2026-09-14 23:42:10	2	Debo una Coca de 2 litro
9	5	ENTRADA	1.000	1.000	2.000	2026-09-14 23:42:50	2	\N
10	5	ENTRADA	50.000	2.000	52.000	2026-09-14 23:46:35	2	50 litros de coca se ingresa
17	8	ENTRADA	60.000	0.000	60.000	2026-09-15 00:57:51	15	gaseosa coca de 1.5L
18	8	SALIDA	5.000	60.000	55.000	2026-09-15 00:58:25	15	\N
19	8	SALIDA	6.000	55.000	49.000	2026-09-15 00:58:54	39	\N
20	8	SALIDA	10.000	49.000	39.000	2026-09-15 00:59:16	2	\N
21	9	ENTRADA	50.000	0.000	50.000	2026-09-17 02:26:12	40	\N
22	10	ENTRADA	20.000	0.000	20.000	2026-09-17 02:30:31	2	\N
23	11	ENTRADA	200.000	0.000	200.000	2026-09-17 14:30:45	15	Por bola de de lechuga serian 10 y se estan añadiendo 20 bolas de lechuga siendo total en rogadas 200
24	12	ENTRADA	200.000	0.000	200.000	2026-09-25 14:00:20	56	\N
25	13	ENTRADA	200.000	0.000	200.000	2026-09-25 14:41:44	57	\N
26	14	ENTRADA	5.000	0.000	5.000	2026-09-25 15:14:54	57	\N
\.


--
-- Data for Name: pago; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.pago (id, idventa, idmetodopago, idaperturacaja, montopago, estadopago, montorecibido, cambioentregado, referenciaexterna, gateway, codigoautorizacion, idtransaccion, ultimos4, marcatarjeta, fechapago, observacionpago) FROM stdin;
84	109	2	34	43.00	APROBADO	50.00	7.00	\N	EFECTIVO	EF-A2ZOFFEA	\N	\N	\N	2026-09-19 10:22:22	Pago en efectivo
85	110	2	34	85.00	APROBADO	100.00	15.00	\N	EFECTIVO	EF-IQYD7LXF	\N	\N	\N	2026-09-19 10:23:43	Pago en efectivo
86	111	2	34	60.00	APROBADO	100.00	40.00	\N	EFECTIVO	EF-WJHVPWFK	\N	\N	\N	2026-09-19 13:21:59	Pago en efectivo
87	112	2	34	8.00	APROBADO	8.00	0.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 14:05:53	Pago en efectivo
88	113	2	83	93.00	APROBADO	100.00	7.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 14:31:18	Pago en efectivo
89	114	2	83	67.00	APROBADO	100.00	33.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 14:32:50	Pago en efectivo
90	115	2	83	233.00	APROBADO	250.00	17.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 14:34:22	Pago en efectivo
91	116	2	83	85.00	APROBADO	100.00	15.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 15:35:28	Pago en efectivo
92	117	2	84	176.00	APROBADO	200.00	24.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 21:26:39	Pago en efectivo
93	118	2	84	85.00	APROBADO	100.00	15.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-19 21:27:16	Pago en efectivo
94	119	2	85	50.00	APROBADO	50.00	0.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-20 00:45:40	Pago en efectivo
95	120	2	85	70.00	APROBADO	100.00	30.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-20 00:46:18	Pago en efectivo
96	121	2	85	67.00	APROBADO	100.00	33.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-20 00:46:32	Pago en efectivo
97	122	2	86	61.00	APROBADO	70.00	9.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-25 14:52:22	Pago en efectivo
98	123	2	88	32.00	APROBADO	40.00	8.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-26 10:59:46	Pago en efectivo
99	124	2	89	55.00	APROBADO	100.00	45.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-28 16:02:46	Pago en efectivo
\.


--
-- Data for Name: pedido; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.pedido (id, nropedido, fecha, total, estadopedido, tipopedido, idsucursal, idempleado, idmesa, subtotalpedido, descuentopedido, horarecogida, observaciones, idaperturacaja) FROM stdin;
173	2	2026-09-19 14:32:11	67.00	EJECUTADO	MESA	6	15	12	67.00	0.00	\N	\N	83
174	3	2026-09-19 14:33:07	233.00	EJECUTADO	MESA	6	15	20	242.00	9.00	\N	\N	83
175	4	2026-09-19 15:34:52	85.00	EJECUTADO	PARA_LLEVAR	6	15	\N	94.00	9.00	\N	\N	83
177	2	2026-09-19 21:26:25	176.00	EJECUTADO	MESA	6	39	12	176.00	0.00	\N	\N	84
98	3	2026-09-18 16:09:45	92.00	EJECUTADO	MESA	6	45	20	92.00	0.00	\N	\N	\N
159	6	2026-09-18 18:46:39	18.00	PENDIENTE	PARA_LLEVAR	6	15	\N	23.00	5.00	\N	\N	\N
176	1	2026-09-19 21:25:39	85.00	EJECUTADO	PARA_LLEVAR	6	41	\N	94.00	9.00	\N	\N	84
165	7	2026-09-18 21:57:52	85.00	PENDIENTE	PARA_LLEVAR	6	15	\N	94.00	9.00	\N	\N	\N
97	2	2026-09-18 16:08:52	56.00	ANULADO	PARA_LLEVAR	6	45	\N	56.00	0.00	\N	\N	\N
148	5	2026-09-18 17:38:36	65.00	ANULADO	PARA_LLEVAR	6	15	\N	65.00	0.00	\N	\N	\N
166	8	2026-09-18 22:14:59	114.00	PENDIENTE	PARA_LLEVAR	6	15	\N	114.00	0.00	\N	\N	\N
96	1	2026-09-18 16:07:58	42.00	EJECUTADO	MESA	6	45	12	47.00	5.00	\N	\N	\N
184	7	2026-09-19 22:44:22	25.00	ANULADO	PARA_LLEVAR	6	39	\N	30.00	5.00	\N	[Anulado el 20/09/2026 00:44]	85
183	6	2026-09-19 22:43:51	25.00	ANULADO	PARA_LLEVAR	6	39	\N	30.00	5.00	\N	[Anulado el 20/09/2026 00:44]	85
182	5	2026-09-19 22:43:23	25.00	ANULADO	PARA_LLEVAR	6	39	\N	30.00	5.00	\N	[Anulado el 20/09/2026 00:44]	85
185	8	2026-09-20 00:43:42	50.00	EJECUTADO	PARA_LLEVAR	6	39	\N	50.00	0.00	\N	\N	85
112	4	2026-09-18 16:23:00	33.00	EJECUTADO	MESA	6	15	21	33.00	0.00	\N	\N	\N
178	1	2026-09-19 21:33:03	85.00	ANULADO	PARA_LLEVAR	6	41	\N	94.00	9.00	\N	[Anulado el 20/09/2026 00:46]	85
179	2	2026-09-19 21:33:49	85.00	ANULADO	PARA_LLEVAR	6	39	\N	94.00	9.00	\N	[Anulado el 20/09/2026 00:46]	85
181	4	2026-09-19 21:36:25	70.00	EJECUTADO	PARA_LLEVAR	6	39	\N	70.00	0.00	\N	\N	85
180	3	2026-09-19 21:35:58	67.00	EJECUTADO	PARA_LLEVAR	6	39	\N	67.00	0.00	\N	\N	85
171	13	2026-09-19 14:10:06	8.00	ANULADO	PARA_LLEVAR	6	15	\N	8.00	0.00	\N	[Anulado el 19/09/2026 14:10]	\N
167	9	2026-09-19 00:31:35	43.00	EJECUTADO	PARA_LLEVAR	6	15	\N	43.00	0.00	\N	\N	34
168	10	2026-09-19 10:23:07	85.00	EJECUTADO	MESA	6	15	12	94.00	9.00	\N	\N	34
169	11	2026-09-19 13:20:31	60.00	EJECUTADO	MESA	6	15	12	65.00	5.00	\N	\N	34
170	12	2026-09-19 13:23:03	8.00	EJECUTADO	PARA_LLEVAR	6	15	\N	8.00	0.00	\N	\N	34
172	1	2026-09-19 14:30:14	93.00	EJECUTADO	PARA_LLEVAR	6	15	\N	102.00	9.00	\N	\N	83
186	1	2026-09-25 14:17:26	32.00	ANULADO	MESA	6	57	12	32.00	0.00	\N	[Anulado el 25/09/2026 14:18]: Tardo demasiado en la entrega	86
187	2	2026-09-25 14:44:57	61.00	EJECUTADO	MESA	6	58	20	61.00	0.00	\N	\N	86
188	1	2026-09-25 16:55:32	32.00	PENDIENTE	MESA	6	57	20	32.00	0.00	\N	\N	87
189	2	2026-09-25 16:55:51	61.00	PENDIENTE	MESA	6	57	12	61.00	0.00	\N	\N	87
190	1	2026-09-26 10:59:27	32.00	EJECUTADO	MESA	6	56	12	32.00	0.00	\N	\N	88
191	1	2026-09-28 16:00:04	55.00	EJECUTADO	MESA	7	45	18	55.00	0.00	\N	\N	89
\.


--
-- Data for Name: permiso; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.permiso (id, codigo, nombre, estado) FROM stdin;
38	225	Dar de baja a horario de atención de sucursal	t
94	347	Listar inventario y movimientos de sucursal	t
95	348	Establecer cantidad mínima de inventario	t
83	445	Anular venta	t
84	446	Registrar método de pago	t
85	447	Dar de baja a método de pago	t
86	448	Listar métodos de pago	t
87	500	Crear pedido	t
88	501	Editar pedido	t
89	502	Listar pedidos	t
90	505	Dar de baja a pedido	t
91	506	Editar detalle de pedido	t
14	100	Crear nuevo empleado	t
15	101	Editar empleados	t
16	102	Listar empleados	t
17	105	Dar de baja a empleado	t
18	106	Modificar contraseña de empleado	t
19	107	Listar empleados activos	t
20	108	Listar empleados inactivos	t
21	150	Listar bitácora de acceso de empleados	t
22	151	Listar bitácora de acciones de empleados	t
23	200	Crear nueva sucursal	t
24	201	Editar datos de sucursal	t
25	202	Listar sucursales	t
26	204	Asignar gerente a sucursal	t
27	205	Dar de baja a sucursal	t
28	206	Asignar empleado a sucursal	t
29	207	Quitar empleado de sucursal	t
30	208	Listar empleados de sucursal	t
31	210	Registrar mesa	t
32	211	Editar mesa	t
33	212	Listar mesas	t
34	215	Dar de baja a mesa	t
35	220	Registrar horario de atención de sucursal	t
36	221	Editar horario de atención de sucursal	t
37	222	Listar horario de atención de sucursal	t
39	300	Crear nueva categoría	t
40	301	Editar categoría	t
41	302	Listar categorías	t
42	305	Dar de baja a categoría	t
43	310	Crear producto	t
44	311	Editar producto	t
45	312	Listar productos	t
46	315	Dar de baja a producto	t
47	316	Registrar precio y disponibilidad de producto por sucursal	t
48	317	Listar productos disponibles de sucursal	t
49	318	Editar precio de producto de sucursal	t
50	319	Deshabilitar disponibilidad de producto	t
51	320	Listar historial de producto por sucursal	t
52	330	Crear unidad de medida	t
53	331	Editar unidad de medida	t
54	335	Dar de baja unidad de medida	t
55	336	Crear insumo	t
56	337	Editar datos de insumo	t
57	338	Dar de baja insumo	t
58	340	Crear detalle de receta	t
59	341	Modificar detalle de receta	t
60	345	Dar de baja detalle de receta	t
93	346	Registrar movimiento de inventario	t
61	350	Crear promoción en una sucursal	t
62	351	Editar promoción de sucursal	t
63	352	Listar promociones	t
64	355	Dar de baja a promoción de sucursal	t
65	400	Crear nueva caja	t
66	401	Editar datos de caja	t
67	402	Listar cajas	t
68	405	Dar de baja a caja	t
69	410	Crear apertura de caja	t
70	411	Editar apertura de caja	t
71	412	Listar historial de aperturas por caja	t
72	415	Dar de baja a apertura de caja	t
73	420	Registrar movimiento de caja	t
74	421	Editar movimiento de caja	t
75	422	Listar movimientos de caja	t
76	425	Dar de baja a movimiento de caja	t
77	430	Crear cierre de caja	t
78	436	Ver detalle de cierre de caja	t
79	437	Listar historial de cierre de caja	t
80	440	Registrar venta	t
81	441	Editar venta	t
82	442	Listar ventas	t
92	50	Registrar o modificar configuración del sistema	t
\.


--
-- Data for Name: producto; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.producto (id, nombre, descripcion, photo, estado, idcategoria) FROM stdin;
1	TOXICA	Deliciosa carne premium 135 gr.,tocino ahumado, pico de gallo, nachos picantes, jalapeño, papas rusticas Wili, Pan brioche de pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	\N	t	1
7	CHIKILOCO	Deliciosa carne de pollo broaster, papas rusticas willi, tocino ahumado. pico de gallo, nachos, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	t	1
15	Crispy king Wili	Pollo a la Broaster acompañado con arroz primavera y porcion de papas rusticas y porcion de papas rusticas willi con su deliciosa salsa de la casa	\N	t	2
16	Willi Tacos	Tortillas de maiz tostadas acompañada con carne de hamburguesa, queso muzarela, pico de gallo, choclitos, tiras de lechuga, papas rusticas y su salsa de la casa	\N	t	2
17	Willi Nachos	Nachos picantes acompañadado con carne de hamburguesa, pico de gallo, choclito, queso muzzarela, limones en gajos y su salsa de la casa	\N	t	2
18	Pepito	Pan de oregano, carne picada de hamburguesa, salchicha viena, choclito, bañada de salsa y espolvoreada de queso rallado con papa rustica y su salsa	\N	t	2
19	Lomitos	Pan de oregano, carne lomito, lechuga, tomate, cebolla, papas rusticas wili y su salsa de la casa	\N	t	2
21	Salchipapas	Papas rusticas willi, salchicja Viena Santa Fe bañadas de salsa de la casa y espolvoreada de queso rallado	\N	t	2
22	Salchicha	Porcion de salchicha crocantes	\N	t	3
23	Queso	Porcion de queso crocantes	\N	t	3
24	Lomito	Porcion de carne de lomito frito crocantes	\N	t	3
25	Huevo	Porcion de huevo frito crocantes	\N	t	3
26	Nuggets	Porcion de nuggets frito crocantes	\N	t	3
27	Tocino	Porcion de tocino frito crocantes	\N	t	3
28	Hamburguesas	Porcion de de carne de Hamburguesa	\N	t	3
29	Papas	Porcion de papas fritas crocantes	\N	t	3
30	Corona	Gaseosa Alcoholica Fria	\N	t	4
31	Aquarios	Gaseosa de 2L Fria	\N	t	4
32	Popular	Gaseosa de 500ml Fria	\N	t	4
33	Soda 2L	Gaseosa de 2L Fria	\N	t	4
34	Paceña	Bebida alcoholica Fria	\N	t	4
35	Peque	Gaseosa de 250ml Fria	\N	t	4
36	Simba	Gaseosa de 2L Fria	\N	t	4
37	Personal	Gaseosa de 500ml Fria	\N	t	4
38	Soda 1.5L	Gaseosa de 1.5L Fria	\N	t	4
40	Soda 2.5L	Gaseosa de 2.5L Fria	\N	t	4
41	Refresco vaso	bebida en vaso	\N	t	5
42	Refresco Jarra Mediana	bebida de jarra Mediana	\N	t	5
43	Refresco Jarra Grande	bebida de jarra grande	\N	t	5
11	ECO FULL	Deliciosa carne Premium 135gr, tocino ahumado, huevo frito, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/Komcso1OY1ktR2CsuAOvXBAwzcRXuxJNIuFlKzBa.jpg	t	1
20	Willi Nuggets	Tiritas de pollo willi rebosada acompañado con papas rusticas y su salsa de la casa	productos/CFU6ybGgc5q16QZWjfDGDakCG8fuAxPxpIbjKxBO.jpg	t	2
4	FULL	Deliciosa carne Premium 135gr, tocino ahumado, huevo frito, papas rusticas Willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/mx0zIgPjMkXCPCFLotDuXo8NpZoqqvPqIV5yLCfD.jpg	t	1
44	Entraña	La entraña es un corte de diafragma de la res, famoso por su intenso sabor y textura jugosa, ideal para quienes disfrutam un carne una carne con mucho caracter	\N	t	6
49	Bandera	Corte obtenido de la falta de la res, de fibras largas y gran sabor. Perfecto para quienes buscan una porcion abundante y muy jugosa	\N	t	6
50	Ribeye	Extraído de la parte alta del lomo (costillar). Destaca por su marmoleo de grasa, que le aporta un textura extremadamente tierna y un sabor intenso	\N	t	6
14	Burrito	Tortillas de maiz tostadas acompañada con carne de hamburguesa, pollo o mixto, queso criollo, pico de gallo, chiclito, toras de lechuga, papas rusticas y su salsa de la casa	productos/kyNk0MH537PqnmFapU1tEQ7RXYxmcQFHlilnyAJZ.jpg	t	2
2	SUPER	Deliciosa carne premium 135 gr.,tocino ahumado, papas rusticas Willi,  Pan brioche de pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	productos/WLf1YeFX4zFAaeWpNn87zjQgs0Xvd4tApysGaVWP.jpg	t	1
39	Agua Vital	gaseosa vital	productos/x0ewBLe31NolZv05C9RegUCkw9xlYbrpQZKIMNAg.jpg	t	4
6	SIMPLE	Deliciosa carne Premium 135gr, papas rusticas willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/Vl4UFRAUXgSHTfBoO8TMqENTG68eE9ioMkxxT8ix.jpg	t	1
3	DOGOR	Deliciosa carne Premium 135gr, choclito salchicha Viena, queso extra, papas rusticas Willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/2f7eNefpfYyfikqfG26xLpbgOQoqwOYrxabHG9F3.jpg	t	1
13	ECO SIMPLE	Deliciosa carne Premium 135gr, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/TawQ8CJ1O5T1jfbr4vdgUyZZk5bxlzrAchIAsH6S.jpg	t	1
12	ECO SUPER	Deliciosa carne premium 135 gr.,tocino,ahumado, pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	productos/UFja2NVYZFd87tdi8gInTr5IjZRWqGa7VVmpah5U.jpg	t	1
9	ECO DOGOR	Deliciosa carne Premium 135gr, choclito, salchica, viena, queso extra, pan tostado , tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/rdYodDuToltjkJqAdVLIEGdSJz6z5r2lbrZHTJW9.jpg	t	1
5	DOBLE	Deliciosa carne Premium 270gr, papas rusticas willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/wersXHK1zFQBAoq6pNUn3Bs7fM8kjKBkqmjrK1tn.jpg	t	1
51	Coca Cola 3L	Gaseosa de 3L	productos/B1jZYKTED5MQCsUVa2E0nlKdPj5F58i5PH3u8b9B.jpg	t	4
10	EL TOXICO	Deliciosa carne premium, papas rusticas willi, tocino ahumado, mermelada de locoto, cebolla crispy, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	f	1
8	CHIKIWILLI	Deliciosa carne de pollo broaster, papas rusticas willy, Pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	t	1
\.


--
-- Data for Name: promocion; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.promocion (id, nombre, tipodescuento, valordescuento, fechainicio, fechafin, activo, photo) FROM stdin;
6	PromoEco 2EcoSimple a 25 Bs	MONTO_FIJO	5.00	2026-09-18 01:47:00	2026-09-20 01:47:00	f	promociones/p70Go1gIJCtSPX7KiSucz75pQ9GCZr3qtP9hgOO4.jpg
3	Promo line	MONTO_FIJO	25.00	2026-09-17 12:57:00	2026-09-17 23:00:00	f	\N
4	Promo tragon	MONTO_FIJO	3.00	2026-09-17 12:59:00	2026-09-17 23:59:00	f	\N
116	Promo Dogor 2x60	MONTO_FIJO	4.00	2026-09-16 21:10:00	2026-09-22 21:10:00	f	\N
\.


--
-- Data for Name: promocion_sucursal_producto; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.promocion_sucursal_producto (idpromocion, idsucursalproducto, cantidad_producto) FROM stdin;
3	8	1
3	9	1
4	6	1
116	4	2
6	7	2
\.


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.rol (id, claverol, nombrerol) FROM stdin;
2	ADMINISTRADOR	Administrador
3	GERENTE	Gerente
4	CAJERO	Cajero
5	MESERO	Mesero
\.


--
-- Data for Name: rolpermiso; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.rolpermiso (idrol, idpermiso) FROM stdin;
3	87
3	88
3	89
3	90
3	91
5	18
5	33
5	41
5	87
5	88
5	89
5	90
5	91
4	18
4	31
4	32
4	33
4	34
4	37
4	41
4	48
4	50
4	63
4	67
4	69
4	70
4	71
4	73
4	74
4	75
4	76
4	77
4	78
4	79
4	93
4	94
4	95
4	80
4	81
4	82
4	83
4	86
4	87
4	88
4	89
4	90
4	91
3	14
3	15
3	16
3	17
3	18
3	24
3	28
3	29
3	30
3	31
3	32
3	33
3	34
3	35
3	36
3	37
3	38
3	47
3	48
3	49
3	50
3	51
3	52
3	53
3	54
3	55
3	56
3	57
3	58
3	59
3	60
3	93
3	94
3	95
3	61
3	62
3	63
3	64
3	65
3	66
3	67
3	68
3	69
3	70
3	71
3	72
3	73
3	74
3	75
3	76
3	77
3	78
3	79
3	80
3	81
3	82
3	83
3	86
2	92
2	14
2	15
2	16
2	17
2	18
2	19
2	20
2	21
2	22
2	23
2	24
2	25
2	26
2	27
2	28
2	29
2	30
2	31
2	32
2	33
2	34
2	35
2	36
2	37
2	38
2	39
2	40
2	41
2	42
2	43
2	44
2	45
2	46
2	47
2	48
2	49
2	50
2	51
2	52
2	53
2	54
2	55
2	56
2	57
2	58
2	59
2	60
2	93
2	94
2	95
2	61
2	62
2	63
2	64
2	65
2	66
2	67
2	68
2	69
2	70
2	71
2	72
2	73
2	74
2	75
2	76
2	77
2	78
2	79
2	80
2	81
2	82
2	83
2	84
2	85
2	86
2	87
2	88
2	89
2	90
2	91
\.


--
-- Data for Name: sucursal; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.sucursal (id, nombre, direccion, telefono, latitud, longitud, estado, idempleadogerente) FROM stdin;
6	Sucursal Centro	Calle Ruben Arrdondo, Nro 9	75535749	-17.88923300	-63.32837100	t	56
8	Sucursal El torno	Santa Cruz, El torno	+59176650201	-17.99886400	-63.38731500	t	59
7	Sucursal km 9	km 9	57567	-17.88171900	-63.28588500	t	45
\.


--
-- Data for Name: sucursal_producto; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.sucursal_producto (id, idsucursal, idproducto, precioproducto, disponibilidad) FROM stdin;
3	6	5	35.00	t
5	6	9	25.00	t
7	6	13	15.00	t
9	6	12	18.00	t
10	6	10	28.00	t
11	6	51	21.00	t
6	6	11	23.00	f
8	6	39	10.00	t
4	6	3	32.00	t
12	7	7	25.00	t
13	7	5	30.00	t
14	7	9	25.00	t
\.


--
-- Data for Name: unidadmedida; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.unidadmedida (id, nombre, abreviatura, tipo, estado) FROM stdin;
3	Gramos	GR	PESO	t
4	Unidad	UND	UNIDAD	t
5	Mililitro	ML	VOLUMEN	t
6	Litro	L	VOLUMEN	t
7	Rodajas	ROD	UNIDAD	t
\.


--
-- Data for Name: venta; Type: TABLE DATA; Schema: public; Owner: burguer
--

COPY public.venta (id, fechaventa, totalventa, estadoventa, idempleado, idpedido) FROM stdin;
109	2026-09-19 10:22:22	43.00	PAGADA	15	167
110	2026-09-19 10:23:43	85.00	PAGADA	15	168
111	2026-09-19 13:21:59	60.00	PAGADA	15	169
112	2026-09-19 14:05:53	8.00	PAGADA	15	170
113	2026-09-19 14:31:17	93.00	PAGADA	15	172
114	2026-09-19 14:32:50	67.00	PAGADA	15	173
115	2026-09-19 14:34:22	233.00	PAGADA	15	174
116	2026-09-19 15:35:28	85.00	PAGADA	15	175
117	2026-09-19 21:26:39	176.00	PAGADA	39	177
118	2026-09-19 21:27:16	85.00	PAGADA	39	176
119	2026-09-20 00:45:40	50.00	PAGADA	39	185
120	2026-09-20 00:46:18	70.00	PAGADA	39	181
121	2026-09-20 00:46:31	67.00	PAGADA	39	180
122	2026-09-25 14:52:22	61.00	PAGADA	57	187
123	2026-09-26 10:59:46	32.00	PAGADA	56	190
124	2026-09-28 16:02:46	55.00	PAGADA	45	191
\.


--
-- Name: aperturacaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.aperturacaja_id_seq', 89, true);


--
-- Name: bitacoraacceso_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.bitacoraacceso_id_seq', 10, true);


--
-- Name: bitacoraaccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.bitacoraaccion_id_seq', 78, true);


--
-- Name: caja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.caja_id_seq', 7, true);


--
-- Name: categoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.categoria_id_seq', 7, true);


--
-- Name: cierrecaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.cierrecaja_id_seq', 85, true);


--
-- Name: configuracion_sistema_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.configuracion_sistema_id_seq', 19, true);


--
-- Name: cuentausuario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.cuentausuario_id_seq', 50, true);


--
-- Name: detallepedido_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.detallepedido_id_seq', 306, true);


--
-- Name: detallereceta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.detallereceta_id_seq', 31, true);


--
-- Name: diaatencion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.diaatencion_id_seq', 7, true);


--
-- Name: empleado_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.empleado_id_seq', 59, true);


--
-- Name: historialproducto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.historialproducto_id_seq', 3, true);


--
-- Name: horariosucursal_idhorariosucursal_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.horariosucursal_idhorariosucursal_seq', 10, true);


--
-- Name: insumo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.insumo_id_seq', 14, true);


--
-- Name: inventariosucursal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.inventariosucursal_id_seq', 14, true);


--
-- Name: mesa_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.mesa_id_seq', 22, true);


--
-- Name: metodopago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.metodopago_id_seq', 4, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.migrations_id_seq', 1, true);


--
-- Name: movimientocaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.movimientocaja_id_seq', 128, true);


--
-- Name: movimientoinventario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.movimientoinventario_id_seq', 26, true);


--
-- Name: pago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.pago_id_seq', 99, true);


--
-- Name: pedido_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.pedido_id_seq', 191, true);


--
-- Name: permiso_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.permiso_id_seq', 95, true);


--
-- Name: producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.producto_id_seq', 51, true);


--
-- Name: promocion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.promocion_id_seq', 116, true);


--
-- Name: rol_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.rol_id_seq', 5, true);


--
-- Name: sucursal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.sucursal_id_seq', 8, true);


--
-- Name: sucursal_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.sucursal_producto_id_seq', 14, true);


--
-- Name: unidadmedida_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.unidadmedida_id_seq', 7, true);


--
-- Name: venta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: burguer
--

SELECT pg_catalog.setval('public.venta_id_seq', 124, true);


--
-- Name: aperturacaja aperturacaja_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_pkey PRIMARY KEY (id);


--
-- Name: bitacoraacceso bitacoraacceso_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.bitacoraacceso
    ADD CONSTRAINT bitacoraacceso_pkey PRIMARY KEY (id);


--
-- Name: bitacoraaccion bitacoraaccion_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT bitacoraaccion_pkey PRIMARY KEY (id);


--
-- Name: caja caja_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.caja
    ADD CONSTRAINT caja_pkey PRIMARY KEY (id);


--
-- Name: categoria categoria_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_pkey PRIMARY KEY (id);


--
-- Name: cierrecaja cierrecaja_idaperturacaja_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idaperturacaja_key UNIQUE (idaperturacaja);


--
-- Name: cierrecaja cierrecaja_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_pkey PRIMARY KEY (id);


--
-- Name: configuracion_sistema configuracion_sistema_clave_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.configuracion_sistema
    ADD CONSTRAINT configuracion_sistema_clave_key UNIQUE (clave);


--
-- Name: configuracion_sistema configuracion_sistema_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.configuracion_sistema
    ADD CONSTRAINT configuracion_sistema_pkey PRIMARY KEY (id);


--
-- Name: cuentausuario cuentausuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_correo_key UNIQUE (correo);


--
-- Name: cuentausuario cuentausuario_googlesub_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_googlesub_key UNIQUE (googlesub);


--
-- Name: cuentausuario cuentausuario_idempleado_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_idempleado_key UNIQUE (idempleado);


--
-- Name: cuentausuario cuentausuario_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_pkey PRIMARY KEY (id);


--
-- Name: cuentausuario cuentausuario_usuario_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_usuario_key UNIQUE (usuario);


--
-- Name: detallepedido detallepedido_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_pkey PRIMARY KEY (id);


--
-- Name: detallereceta detallereceta_idproducto_idinsumo_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idproducto_idinsumo_key UNIQUE (idproducto, idinsumo);


--
-- Name: detallereceta detallereceta_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_pkey PRIMARY KEY (id);


--
-- Name: diaatencion diaatencion_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.diaatencion
    ADD CONSTRAINT diaatencion_pkey PRIMARY KEY (id);


--
-- Name: empleado empleado_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_pkey PRIMARY KEY (id);


--
-- Name: historialproducto historialproducto_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.historialproducto
    ADD CONSTRAINT historialproducto_pkey PRIMARY KEY (id);


--
-- Name: horariosucursal horariosucursal_idsucursal_iddiaatencion_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_idsucursal_iddiaatencion_key UNIQUE (idsucursal, iddiaatencion);


--
-- Name: horariosucursal horariosucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_pkey PRIMARY KEY (idhorariosucursal);


--
-- Name: insumo insumo_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.insumo
    ADD CONSTRAINT insumo_pkey PRIMARY KEY (id);


--
-- Name: inventariosucursal inventariosucursal_idsucursal_idinsumo_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idsucursal_idinsumo_key UNIQUE (idsucursal, idinsumo);


--
-- Name: inventariosucursal inventariosucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_pkey PRIMARY KEY (id);


--
-- Name: mesa mesa_idsucursal_numeromesa_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_idsucursal_numeromesa_key UNIQUE (idsucursal, numeromesa);


--
-- Name: mesa mesa_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_pkey PRIMARY KEY (id);


--
-- Name: metodopago metodopago_nombre_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.metodopago
    ADD CONSTRAINT metodopago_nombre_key UNIQUE (nombre);


--
-- Name: metodopago metodopago_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.metodopago
    ADD CONSTRAINT metodopago_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: movimientocaja movimientocaja_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_pkey PRIMARY KEY (id);


--
-- Name: movimientoinventario movimientoinventario_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_pkey PRIMARY KEY (id);


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id);


--
-- Name: pedido pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_pkey PRIMARY KEY (id);


--
-- Name: permiso permiso_codigo_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT permiso_codigo_key UNIQUE (codigo);


--
-- Name: permiso permiso_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT permiso_pkey PRIMARY KEY (id);


--
-- Name: producto producto_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_pkey PRIMARY KEY (id);


--
-- Name: promocion promocion_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.promocion
    ADD CONSTRAINT promocion_pkey PRIMARY KEY (id);


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_pkey PRIMARY KEY (idpromocion, idsucursalproducto);


--
-- Name: rol rol_claverol_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_claverol_key UNIQUE (claverol);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id);


--
-- Name: rolpermiso rolpermiso_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_pkey PRIMARY KEY (idrol, idpermiso);


--
-- Name: sucursal sucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal
    ADD CONSTRAINT sucursal_pkey PRIMARY KEY (id);


--
-- Name: sucursal_producto sucursal_producto_idsucursal_idproducto_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idsucursal_idproducto_key UNIQUE (idsucursal, idproducto);


--
-- Name: sucursal_producto sucursal_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_pkey PRIMARY KEY (id);


--
-- Name: unidadmedida unidadmedida_abreviatura_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.unidadmedida
    ADD CONSTRAINT unidadmedida_abreviatura_key UNIQUE (abreviatura);


--
-- Name: unidadmedida unidadmedida_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.unidadmedida
    ADD CONSTRAINT unidadmedida_pkey PRIMARY KEY (id);


--
-- Name: venta venta_idpedido_key; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idpedido_key UNIQUE (idpedido);


--
-- Name: venta venta_pkey; Type: CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_pkey PRIMARY KEY (id);


--
-- Name: cierrecaja trg_calcular_parametros_cierre; Type: TRIGGER; Schema: public; Owner: burguer
--

CREATE TRIGGER trg_calcular_parametros_cierre BEFORE INSERT OR UPDATE ON public.cierrecaja FOR EACH ROW EXECUTE FUNCTION public.fn_calcular_parametros_cierre();


--
-- Name: aperturacaja trg_crear_cierre_inicial; Type: TRIGGER; Schema: public; Owner: burguer
--

CREATE TRIGGER trg_crear_cierre_inicial AFTER INSERT ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION public.fn_crear_cierre_inicial();


--
-- Name: movimientocaja trg_refrescar_cierre_por_movimiento; Type: TRIGGER; Schema: public; Owner: burguer
--

CREATE TRIGGER trg_refrescar_cierre_por_movimiento AFTER INSERT OR DELETE OR UPDATE ON public.movimientocaja FOR EACH ROW EXECUTE FUNCTION public.fn_refrescar_cierre_por_movimiento();


--
-- Name: pago trg_refrescar_cierre_por_pago; Type: TRIGGER; Schema: public; Owner: burguer
--

CREATE TRIGGER trg_refrescar_cierre_por_pago AFTER INSERT OR DELETE OR UPDATE ON public.pago FOR EACH ROW EXECUTE FUNCTION public.fn_refrescar_cierre_por_pago();


--
-- Name: aperturacaja trg_validar_apertura_caja; Type: TRIGGER; Schema: public; Owner: burguer
--

CREATE TRIGGER trg_validar_apertura_caja BEFORE INSERT OR UPDATE ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION public.fn_validar_apertura_caja();


--
-- Name: aperturacaja aperturacaja_idcaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_idcaja_fkey FOREIGN KEY (idcaja) REFERENCES public.caja(id);


--
-- Name: aperturacaja aperturacaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: bitacoraacceso bitacoraacceso_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.bitacoraacceso
    ADD CONSTRAINT bitacoraacceso_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: bitacoraaccion bitacoraaccion_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT bitacoraaccion_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: bitacoraaccion bitacoraaccion_idpermiso_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT bitacoraaccion_idpermiso_fkey FOREIGN KEY (idpermiso) REFERENCES public.permiso(id);


--
-- Name: caja caja_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.caja
    ADD CONSTRAINT caja_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: cierrecaja cierrecaja_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: cierrecaja cierrecaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: cuentausuario cuentausuario_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: detallepedido detallepedido_idpedido_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES public.pedido(id);


--
-- Name: detallepedido detallepedido_idproductosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES public.sucursal_producto(id);


--
-- Name: detallepedido detallepedido_idpromocion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idpromocion_fkey FOREIGN KEY (idpromocion) REFERENCES public.promocion(id);


--
-- Name: detallereceta detallereceta_idinsumo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES public.insumo(id);


--
-- Name: detallereceta detallereceta_idproducto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES public.producto(id);


--
-- Name: empleado empleado_idjefe_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_idjefe_fkey FOREIGN KEY (idjefe) REFERENCES public.empleado(id);


--
-- Name: empleado empleado_idrol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_idrol_fkey FOREIGN KEY (idrol) REFERENCES public.rol(id);


--
-- Name: historialproducto historialproducto_idproductosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.historialproducto
    ADD CONSTRAINT historialproducto_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES public.sucursal_producto(id);


--
-- Name: horariosucursal horariosucursal_iddiaatencion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_iddiaatencion_fkey FOREIGN KEY (iddiaatencion) REFERENCES public.diaatencion(id);


--
-- Name: horariosucursal horariosucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: insumo insumo_idunidadmedida_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.insumo
    ADD CONSTRAINT insumo_idunidadmedida_fkey FOREIGN KEY (idunidadmedida) REFERENCES public.unidadmedida(id);


--
-- Name: inventariosucursal inventariosucursal_idinsumo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES public.insumo(id);


--
-- Name: inventariosucursal inventariosucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: mesa mesa_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: movimientocaja movimientocaja_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: movimientocaja movimientocaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: movimientoinventario movimientoinventario_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: movimientoinventario movimientoinventario_idinventariosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_idinventariosucursal_fkey FOREIGN KEY (idinventariosucursal) REFERENCES public.inventariosucursal(id);


--
-- Name: pago pago_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: pago pago_idmetodopago_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idmetodopago_fkey FOREIGN KEY (idmetodopago) REFERENCES public.metodopago(id);


--
-- Name: pago pago_idventa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idventa_fkey FOREIGN KEY (idventa) REFERENCES public.venta(id);


--
-- Name: pedido pedido_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: pedido pedido_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: pedido pedido_idmesa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idmesa_fkey FOREIGN KEY (idmesa) REFERENCES public.mesa(id);


--
-- Name: pedido pedido_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: producto producto_idcategoria_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_idcategoria_fkey FOREIGN KEY (idcategoria) REFERENCES public.categoria(id);


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_idpromocion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_idpromocion_fkey FOREIGN KEY (idpromocion) REFERENCES public.promocion(id);


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_idsucursalproducto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_idsucursalproducto_fkey FOREIGN KEY (idsucursalproducto) REFERENCES public.sucursal_producto(id);


--
-- Name: rolpermiso rolpermiso_idpermiso_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_idpermiso_fkey FOREIGN KEY (idpermiso) REFERENCES public.permiso(id);


--
-- Name: rolpermiso rolpermiso_idrol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_idrol_fkey FOREIGN KEY (idrol) REFERENCES public.rol(id);


--
-- Name: sucursal sucursal_idempleadogerente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal
    ADD CONSTRAINT sucursal_idempleadogerente_fkey FOREIGN KEY (idempleadogerente) REFERENCES public.empleado(id);


--
-- Name: sucursal_producto sucursal_producto_idproducto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES public.producto(id);


--
-- Name: sucursal_producto sucursal_producto_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: venta venta_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: venta venta_idpedido_fkey; Type: FK CONSTRAINT; Schema: public; Owner: burguer
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES public.pedido(id);


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: burguer
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;


--
-- PostgreSQL database dump complete
--

\unrestrict 6DlmzPd9YRupvJJGGvHNmhuQffnXfC40Ysxk8Z3RzhRs4FNcKkbFCWM909OrmBc

