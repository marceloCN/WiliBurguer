--
-- PostgreSQL database dump
--

\restrict HPxushSTt5pd34rJwJwAVxOYXMiDmACmWlJeOniDgglx0rQpGD1EWx742KNBRks

-- Dumped from database version 16.14 (Debian 16.14-1.pgdg13+1)
-- Dumped by pg_dump version 16.14 (Debian 16.14-1.pgdg13+1)

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
-- Name: tipo_pedido; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipo_pedido AS ENUM (
    'MESA',
    'PARA_LLEVAR'
);


ALTER TYPE public.tipo_pedido OWNER TO postgres;

--
-- Name: tipodescuento; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipodescuento AS ENUM (
    'PORCENTAJE',
    'DESCUENTO_MONTO',
    'PRECIO_PROMOCIONAL'
);


ALTER TYPE public.tipodescuento OWNER TO postgres;

--
-- Name: tipoestado_pedido; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipoestado_pedido AS ENUM (
    'PENDIENTE',
    'ANULADO',
    'EJECUTADO'
);


ALTER TYPE public.tipoestado_pedido OWNER TO postgres;

--
-- Name: tipoestadocaja; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipoestadocaja AS ENUM (
    'ABIERTA',
    'CERRADA'
);


ALTER TYPE public.tipoestadocaja OWNER TO postgres;

--
-- Name: tipoestadopago; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipoestadopago AS ENUM (
    'PENDIENTE',
    'APROBADO',
    'RECHAZADO',
    'ANULADO'
);


ALTER TYPE public.tipoestadopago OWNER TO postgres;

--
-- Name: tipoestadoventa; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipoestadoventa AS ENUM (
    'PENDIENTE',
    'PAGADA',
    'RECHAZADO',
    'ANULADO'
);


ALTER TYPE public.tipoestadoventa OWNER TO postgres;

--
-- Name: tipomovimientocaja; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipomovimientocaja AS ENUM (
    'INGRESO',
    'EGRESO'
);


ALTER TYPE public.tipomovimientocaja OWNER TO postgres;

--
-- Name: tipomovimientoinventario; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipomovimientoinventario AS ENUM (
    'ENTRADA',
    'SALIDA',
    'AJUSTE'
);


ALTER TYPE public.tipomovimientoinventario OWNER TO postgres;

--
-- Name: actualizar_caja(integer, integer, character varying, boolean); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.actualizar_caja(p_id integer, p_idsucursal integer, p_nombre character varying, p_activo boolean) OWNER TO postgres;

--
-- Name: actualizar_mesa(integer, integer, integer, integer, boolean, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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
        RAISE EXCEPTION 'Ya existe una mesa con ese n├║mero en esta sucursal.';
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


ALTER PROCEDURE public.actualizar_mesa(IN p_id integer, IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: actualizar_perfil(integer, character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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


ALTER PROCEDURE public.actualizar_perfil(IN p_id_empleado integer, IN p_nombre character varying, IN p_apellido character varying, IN p_telefono character varying, IN p_usuario character varying, IN p_correo character varying, IN p_password_hash character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: dar_baja_caja(integer, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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


ALTER PROCEDURE public.dar_baja_caja(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: dar_baja_mesa(integer, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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


ALTER PROCEDURE public.dar_baja_mesa(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: fn_calcular_parametros_cierre(); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.fn_calcular_parametros_cierre() OWNER TO postgres;

--
-- Name: fn_crear_cierre_inicial(); Type: FUNCTION; Schema: public; Owner: postgres
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
        'Cierre provisional: la jornada a├║n est├í en curso.', NEW.id, NEW.idempleado
    );
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_crear_cierre_inicial() OWNER TO postgres;

--
-- Name: fn_refrescar_cierre_por_movimiento(); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.fn_refrescar_cierre_por_movimiento() OWNER TO postgres;

--
-- Name: fn_refrescar_cierre_por_pago(); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.fn_refrescar_cierre_por_pago() OWNER TO postgres;

--
-- Name: fn_validar_apertura_caja(); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.fn_validar_apertura_caja() OWNER TO postgres;

--
-- Name: reasignar_subordinados(integer, integer, integer); Type: PROCEDURE; Schema: public; Owner: postgres
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

    -- Si el nuevo gerente depend├¡a del antiguo (idjefe = antiguo), romper
    -- la referencia circular: el nuevo jefe queda sin jefe.
    IF p_jefe_nuevo IS NOT NULL THEN
        UPDATE empleado
           SET idjefe = NULL
         WHERE id = p_jefe_nuevo
           AND idjefe IS NOT DISTINCT FROM p_jefe_anterior;
    END IF;

    -- Reasignar al nuevo gerente TODOS los cajeros/meseros que depend├¡an
    -- del antiguo gerente (est├⌐n en esta u otra sucursal).
    UPDATE empleado
       SET idjefe = p_jefe_nuevo
     WHERE idjefe IS NOT DISTINCT FROM p_jefe_anterior
       AND idrol IN (v_id_rol_cajero, v_id_rol_mesero)
       AND (p_jefe_nuevo IS NULL OR id <> p_jefe_nuevo);

    -- Los cajeros/meseros de ESTA sucursal que est├⌐n sin gerente (idjefe NULL)
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


ALTER PROCEDURE public.reasignar_subordinados(IN p_jefe_anterior integer, IN p_jefe_nuevo integer, IN p_idsucursal integer) OWNER TO postgres;

--
-- Name: registrar_acceso(integer, character varying, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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


ALTER PROCEDURE public.registrar_acceso(IN p_id_empleado integer, IN p_tipo_acceso character varying, IN p_equipo character varying, IN p_ip character varying, IN p_resultado character varying, IN p_detalle character varying) OWNER TO postgres;

--
-- Name: registrar_accion(integer, character varying, character varying, bigint, character varying, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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


ALTER PROCEDURE public.registrar_accion(IN p_id_empleado integer, IN p_accion character varying, IN p_tabla_afectada character varying, IN p_id_registro_afectado bigint, IN p_codigo_permiso character varying, IN p_resultado character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: registrar_caja(integer, character varying, boolean); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.registrar_caja(p_idsucursal integer, p_nombre character varying, p_activo boolean) OWNER TO postgres;

--
-- Name: registrar_mesa(integer, integer, integer, boolean, integer, character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: postgres
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
        RAISE EXCEPTION 'Ya existe una mesa con ese n├║mero en esta sucursal.';
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


ALTER PROCEDURE public.registrar_mesa(IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying) OWNER TO postgres;

--
-- Name: verificar_bloqueo_y_desbloquear(integer); Type: FUNCTION; Schema: public; Owner: postgres
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


ALTER FUNCTION public.verificar_bloqueo_y_desbloquear(p_id_cuenta integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: aperturacaja; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.aperturacaja OWNER TO postgres;

--
-- Name: aperturacaja_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: bitacoraacceso; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacoraacceso (
    id bigint NOT NULL,
    idempleado integer,
    fechahora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tipoacceso character varying(20) NOT NULL,
    equipo character varying(100),
    ip character varying(45),
    resultado character varying(20) NOT NULL,
    detalle character varying(255),
    CONSTRAINT ck_bitacora_acceso_resultado CHECK (((resultado)::text = ANY ((ARRAY['EXITOSO'::character varying, 'FALLIDO'::character varying])::text[]))),
    CONSTRAINT ck_bitacora_acceso_tipo CHECK (((tipoacceso)::text = ANY ((ARRAY['LOGIN'::character varying, 'LOGOUT'::character varying])::text[])))
);


ALTER TABLE public.bitacoraacceso OWNER TO postgres;

--
-- Name: bitacoraacceso_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: bitacoraaccion; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.bitacoraaccion OWNER TO postgres;

--
-- Name: bitacoraaccion_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: caja; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.caja (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    idsucursal integer NOT NULL
);


ALTER TABLE public.caja OWNER TO postgres;

--
-- Name: caja_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: categoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categoria (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.categoria OWNER TO postgres;

--
-- Name: categoria_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: cierrecaja; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.cierrecaja OWNER TO postgres;

--
-- Name: cierrecaja_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: configuracion_sistema; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.configuracion_sistema (
    id bigint NOT NULL,
    clave character varying(100) NOT NULL,
    valor text,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.configuracion_sistema OWNER TO postgres;

--
-- Name: configuracion_sistema_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.configuracion_sistema_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.configuracion_sistema_id_seq OWNER TO postgres;

--
-- Name: configuracion_sistema_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.configuracion_sistema_id_seq OWNED BY public.configuracion_sistema.id;


--
-- Name: cuentausuario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cuentausuario (
    id integer NOT NULL,
    idempleado integer NOT NULL,
    usuario character varying(50) NOT NULL,
    correo character varying(150) NOT NULL,
    passwordhash character varying(255),
    intentosfallidos integer DEFAULT 0 NOT NULL,
    bloqueado boolean DEFAULT false NOT NULL,
    bloqueadohasta timestamp without time zone,
    fechacreacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ultimoacceso timestamp without time zone,
    remember_token character varying(100)
);


ALTER TABLE public.cuentausuario OWNER TO postgres;

--
-- Name: cuentausuario_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: detallepedido; Type: TABLE; Schema: public; Owner: postgres
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
    idpromocionsucursal integer,
    CONSTRAINT chk_detallepedido_item CHECK (((idproductosucursal IS NOT NULL) OR (idpromocionsucursal IS NOT NULL))),
    CONSTRAINT detallepedido_cantidad_check CHECK ((cantidad > 0)),
    CONSTRAINT detallepedido_descuento_check CHECK ((descuento >= (0)::numeric)),
    CONSTRAINT detallepedido_preciounitario_check CHECK ((preciounitario >= (0)::numeric)),
    CONSTRAINT detallepedido_subtotal_check CHECK ((subtotal >= (0)::numeric)),
    CONSTRAINT detallepedido_total_check CHECK ((total >= (0)::numeric))
);


ALTER TABLE public.detallepedido OWNER TO postgres;

--
-- Name: detallepedido_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: detallereceta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detallereceta (
    id integer NOT NULL,
    idproducto integer NOT NULL,
    idinsumo integer NOT NULL,
    cantidad numeric(12,3) NOT NULL,
    CONSTRAINT detallereceta_cantidad_check CHECK ((cantidad > (0)::numeric))
);


ALTER TABLE public.detallereceta OWNER TO postgres;

--
-- Name: detallereceta_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: empleado; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.empleado OWNER TO postgres;

--
-- Name: empleado_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: historialproducto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.historialproducto (
    id integer NOT NULL,
    fecha_registro timestamp without time zone NOT NULL,
    precioanterior numeric(10,2) NOT NULL,
    precionuevo numeric(10,2) NOT NULL,
    idproductosucursal integer NOT NULL
);


ALTER TABLE public.historialproducto OWNER TO postgres;

--
-- Name: historialproducto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: horariosucursal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.horariosucursal (
    idhorariosucursal integer NOT NULL,
    idsucursal integer NOT NULL,
    horaapertura time without time zone NOT NULL,
    horacierre time without time zone NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    diasemana smallint NOT NULL,
    CONSTRAINT horariosucursal_diasemana_check CHECK (((diasemana >= 1) AND (diasemana <= 7)))
);


ALTER TABLE public.horariosucursal OWNER TO postgres;

--
-- Name: horariosucursal_idhorariosucursal_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: insumo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.insumo (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255),
    idunidadmedida integer NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.insumo OWNER TO postgres;

--
-- Name: insumo_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: inventariosucursal; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.inventariosucursal OWNER TO postgres;

--
-- Name: inventariosucursal_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: mesa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mesa (
    id integer NOT NULL,
    numeromesa integer NOT NULL,
    capacidad integer DEFAULT 4 NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    idsucursal integer NOT NULL
);


ALTER TABLE public.mesa OWNER TO postgres;

--
-- Name: mesa_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: metodopago; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.metodopago (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    gateway character varying(50)
);


ALTER TABLE public.metodopago OWNER TO postgres;

--
-- Name: metodopago_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: movimientocaja; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.movimientocaja OWNER TO postgres;

--
-- Name: movimientocaja_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: movimientoinventario; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.movimientoinventario OWNER TO postgres;

--
-- Name: movimientoinventario_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: pago; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.pago OWNER TO postgres;

--
-- Name: pago_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: pedido; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.pedido OWNER TO postgres;

--
-- Name: pedido_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: permiso; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permiso (
    id integer NOT NULL,
    codigo character varying(100) NOT NULL,
    nombre character varying(100) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.permiso OWNER TO postgres;

--
-- Name: permiso_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(200) NOT NULL,
    photo character varying(255),
    estado boolean DEFAULT true NOT NULL,
    idcategoria integer NOT NULL
);


ALTER TABLE public.producto OWNER TO postgres;

--
-- Name: producto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: promocion; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promocion (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    photo character varying(255),
    activo boolean DEFAULT true NOT NULL
);


ALTER TABLE public.promocion OWNER TO postgres;

--
-- Name: promocion_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: promocion_sucursal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promocion_sucursal (
    id integer NOT NULL,
    idpromocion integer NOT NULL,
    idsucursal integer NOT NULL,
    tipodescuento public.tipodescuento NOT NULL,
    valordescuento numeric(10,2) NOT NULL,
    fechainicio timestamp without time zone NOT NULL,
    fechafin timestamp without time zone NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT promocion_sucursal_check CHECK (((tipodescuento <> 'PORCENTAJE'::public.tipodescuento) OR (valordescuento <= (100)::numeric))),
    CONSTRAINT promocion_sucursal_check1 CHECK ((fechafin > fechainicio)),
    CONSTRAINT promocion_sucursal_valordescuento_check CHECK ((valordescuento >= (0)::numeric))
);


ALTER TABLE public.promocion_sucursal OWNER TO postgres;

--
-- Name: promocion_sucursal_dia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promocion_sucursal_dia (
    idpromocionsucursal integer NOT NULL,
    diasemana smallint NOT NULL,
    CONSTRAINT promocion_sucursal_dia_diasemana_check CHECK (((diasemana >= 1) AND (diasemana <= 7)))
);


ALTER TABLE public.promocion_sucursal_dia OWNER TO postgres;

--
-- Name: promocion_sucursal_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.promocion_sucursal ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.promocion_sucursal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: promocion_sucursal_producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promocion_sucursal_producto (
    idpromocionsucursal integer NOT NULL,
    idsucursalproducto integer NOT NULL,
    idsucursal integer NOT NULL,
    cantidad_producto integer DEFAULT 1 NOT NULL,
    CONSTRAINT promocion_sucursal_producto_cantidad_producto_check CHECK ((cantidad_producto > 0))
);


ALTER TABLE public.promocion_sucursal_producto OWNER TO postgres;

--
-- Name: rol; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rol (
    id integer NOT NULL,
    claverol character varying(50) NOT NULL,
    nombrerol character varying(50) NOT NULL
);


ALTER TABLE public.rol OWNER TO postgres;

--
-- Name: rol_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: rolpermiso; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rolpermiso (
    idrol integer NOT NULL,
    idpermiso integer NOT NULL
);


ALTER TABLE public.rolpermiso OWNER TO postgres;

--
-- Name: sucursal; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.sucursal OWNER TO postgres;

--
-- Name: sucursal_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: sucursal_producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sucursal_producto (
    id integer NOT NULL,
    idsucursal integer NOT NULL,
    idproducto integer NOT NULL,
    precioproducto numeric(10,2) NOT NULL,
    disponibilidad boolean DEFAULT true NOT NULL,
    CONSTRAINT sucursal_producto_precioproducto_check CHECK ((precioproducto >= (0)::numeric))
);


ALTER TABLE public.sucursal_producto OWNER TO postgres;

--
-- Name: sucursal_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: unidadmedida; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.unidadmedida (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL,
    abreviatura character varying(10) NOT NULL,
    tipo character varying(20) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.unidadmedida OWNER TO postgres;

--
-- Name: unidadmedida_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: venta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.venta (
    id integer NOT NULL,
    fechaventa timestamp without time zone NOT NULL,
    totalventa numeric(10,2) NOT NULL,
    estadoventa public.tipoestadoventa DEFAULT 'PENDIENTE'::public.tipoestadoventa NOT NULL,
    idempleado integer NOT NULL,
    idpedido integer
);


ALTER TABLE public.venta OWNER TO postgres;

--
-- Name: venta_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: configuracion_sistema id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.configuracion_sistema ALTER COLUMN id SET DEFAULT nextval('public.configuracion_sistema_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Data for Name: aperturacaja; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.aperturacaja (id, fechaapertura, montoinicial, estado, idcaja, idempleado) FROM stdin;
83	2026-09-19 14:13:28	50.00	CERRADA	3	15
84	2026-09-19 21:24:20	50.00	CERRADA	3	15
85	2026-09-19 21:31:05	0.00	CERRADA	3	39
86	2026-09-26 21:33:12	50.00	ABIERTA	3	15
34	2026-09-18 06:26:48	50.00	CERRADA	3	45
\.


--
-- Data for Name: bitacoraacceso; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacoraacceso (id, idempleado, fechahora, tipoacceso, equipo, ip, resultado, detalle) FROM stdin;
121	2	2026-09-14 07:03:08.222417	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
123	2	2026-09-14 19:19:47.755585	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
125	2	2026-09-14 20:05:23.693498	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
127	2	2026-09-14 20:06:57.113203	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
129	2	2026-09-14 20:59:24.009436	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
131	2	2026-09-14 21:00:30.639111	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
133	2	2026-09-14 21:44:36.325146	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
135	2	2026-09-14 21:57:11.919147	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
137	2	2026-09-14 22:09:21.267594	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
139	2	2026-09-14 22:43:32.816669	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
141	2	2026-09-14 22:43:47.945282	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
143	2	2026-09-14 22:44:03.02854	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
145	2	2026-09-14 22:47:09.848048	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
147	2	2026-09-14 22:47:53.994161	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
149	2	2026-09-14 22:49:27.614968	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
153	2	2026-09-14 22:59:52.206844	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
155	2	2026-09-14 23:03:07.105779	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
157	2	2026-09-14 23:20:55.212735	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
159	2	2026-09-14 23:32:18.558507	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
161	2	2026-09-14 23:32:34.914425	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
165	2	2026-09-14 23:46:59.141461	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
167	2	2026-09-14 23:47:20.517424	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
169	2	2026-09-14 23:47:41.811931	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
171	2	2026-09-14 23:48:58.550939	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
173	2	2026-09-14 23:52:24.491443	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
175	2	2026-09-14 23:54:31.421975	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
177	2	2026-09-15 00:58:42.571916	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
179	2	2026-09-15 00:58:57.212625	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
181	2	2026-09-15 00:59:22.886486	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
183	2	2026-09-15 00:59:50.219397	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
185	2	2026-09-15 01:00:19.705044	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
187	2	2026-09-15 01:00:47.035087	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
193	2	2026-09-15 02:15:29.537213	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
195	2	2026-09-15 02:17:48.295621	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
197	2	2026-09-15 02:28:38.953884	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
199	2	2026-09-15 02:40:41.760996	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
201	2	2026-09-15 03:06:04.723191	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
205	2	2026-09-15 03:09:23.901507	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
206	2	2026-09-15 03:53:31.698156	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
207	2	2026-09-15 03:55:41.035786	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
208	2	2026-09-15 03:55:57.093004	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
209	2	2026-09-15 03:56:40.314557	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
210	2	2026-09-15 04:24:10.21114	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
122	2	2026-09-14 14:34:21.733975	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
124	2	2026-09-14 19:22:43.624283	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
126	2	2026-09-14 20:06:21.14963	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
134	2	2026-09-14 21:45:07.321861	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
136	2	2026-09-14 21:57:43.85109	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
138	2	2026-09-14 22:41:43.3113	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
140	2	2026-09-14 22:43:38.995017	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
142	2	2026-09-14 22:43:55.404753	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
144	2	2026-09-14 22:44:58.24968	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
146	2	2026-09-14 22:47:28.345497	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
148	2	2026-09-14 22:48:04.165145	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
152	2	2026-09-14 22:54:41.621096	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
154	2	2026-09-14 23:00:05.698491	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
156	2	2026-09-14 23:20:37.535609	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
158	2	2026-09-14 23:21:03.541698	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
160	2	2026-09-14 23:32:28.758257	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
162	2	2026-09-14 23:32:45.705059	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
164	2	2026-09-14 23:36:35.835338	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
166	2	2026-09-14 23:47:06.276011	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
168	2	2026-09-14 23:47:30.110388	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
170	2	2026-09-14 23:48:49.533682	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
172	2	2026-09-14 23:49:06.074888	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
174	2	2026-09-14 23:54:23.260073	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
176	2	2026-09-14 23:54:42.684427	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
178	2	2026-09-15 00:58:48.441401	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
180	2	2026-09-15 00:59:04.766424	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
182	2	2026-09-15 00:59:35.10505	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
184	2	2026-09-15 01:00:07.887355	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
186	2	2026-09-15 01:00:35.327776	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
188	2	2026-09-15 01:00:59.742808	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
190	39	2026-09-15 01:03:55.371806	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
192	2	2026-09-15 01:08:32.793657	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
194	2	2026-09-15 02:17:23.240068	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
196	2	2026-09-15 02:27:12.240346	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
198	2	2026-09-15 02:40:25.556732	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
200	2	2026-09-15 03:04:45.883959	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
202	2	2026-09-15 03:06:30.492543	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
117	2	2026-09-14 04:52:03.455562	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
118	2	2026-09-14 04:52:13.53151	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
119	2	2026-09-14 04:52:26.037656	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
120	2	2026-09-14 04:52:32.488916	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
211	2	2026-09-15 04:24:25.465733	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
212	2	2026-09-15 04:24:32.376157	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
213	2	2026-09-15 04:24:46.940959	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
217	2	2026-09-15 04:35:19.193632	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
218	2	2026-09-15 04:36:35.85706	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
219	2	2026-09-15 04:41:53.199466	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
220	2	2026-09-15 04:48:58.411416	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
221	2	2026-09-15 04:59:50.615201	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
222	2	2026-09-15 16:39:59.813418	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
223	2	2026-09-15 17:26:23.040633	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
224	46	2026-09-15 17:26:29.323571	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
225	46	2026-09-15 17:26:44.788498	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
226	46	2026-09-15 17:37:25.741601	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
227	2	2026-09-15 17:37:37.309822	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
228	2	2026-09-15 17:38:04.624319	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
229	2	2026-09-15 17:39:05.331037	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
230	2	2026-09-15 17:39:42.351974	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
231	2	2026-09-15 17:39:59.321628	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
232	2	2026-09-15 17:40:03.808785	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
233	2	2026-09-15 17:41:43.073493	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
234	\N	2026-09-15 17:41:48.933097	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta no encontrada
235	\N	2026-09-15 17:42:26.879197	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta no encontrada
236	2	2026-09-15 17:44:46.21427	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
237	2	2026-09-15 17:44:50.45195	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
238	2	2026-09-15 17:49:58.561155	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
239	2	2026-09-15 17:50:05.490534	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
240	2	2026-09-15 18:21:36.146985	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
241	2	2026-09-15 18:21:43.398713	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
242	2	2026-09-15 18:22:46.620944	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
243	2	2026-09-15 18:44:40.150269	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
244	2	2026-09-15 18:44:44.108697	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
245	2	2026-09-15 18:46:34.486502	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
246	2	2026-09-15 18:46:42.973881	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
247	2	2026-09-15 18:46:45.051424	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
248	2	2026-09-15 18:52:46.756715	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
249	2	2026-09-15 18:53:07.29024	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
250	2	2026-09-15 18:53:11.084914	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
251	2	2026-09-15 15:12:03.268363	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
252	2	2026-09-15 15:12:08.795982	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
253	2	2026-09-15 15:29:20.210933	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
254	2	2026-09-15 15:33:44.235342	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
255	2	2026-09-15 15:48:45.697666	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
256	2	2026-09-15 15:48:50.118635	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
257	2	2026-09-15 15:48:51.611869	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
258	2	2026-09-15 15:48:53.53141	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
259	2	2026-09-15 15:48:58.08154	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
260	2	2026-09-15 15:48:59.049586	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
261	2	2026-09-15 15:48:59.912987	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
262	2	2026-09-15 15:49:00.647362	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
263	2	2026-09-15 15:49:01.26214	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
264	2	2026-09-15 15:49:01.916021	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
265	2	2026-09-15 15:49:02.469387	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
266	2	2026-09-15 15:49:03.130233	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
267	2	2026-09-15 15:49:03.405738	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
268	2	2026-09-15 15:49:03.81773	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
269	2	2026-09-15 15:49:04.213282	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
270	2	2026-09-15 15:49:04.507621	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
271	2	2026-09-15 15:49:05.133233	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
272	2	2026-09-15 15:49:14.830001	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
273	2	2026-09-15 15:49:43.158596	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
274	2	2026-09-15 19:51:33.3055	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
275	2	2026-09-15 19:51:36.887702	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
276	2	2026-09-15 15:52:02.723057	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
277	2	2026-09-15 15:52:10.581985	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
278	2	2026-09-15 15:52:25.539555	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
279	2	2026-09-15 15:52:27.412033	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
280	2	2026-09-15 15:52:28.471997	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
281	2	2026-09-15 15:52:29.520591	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
282	2	2026-09-15 15:53:29.691274	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
283	2	2026-09-15 15:53:31.188096	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
284	2	2026-09-15 15:53:35.835693	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
285	2	2026-09-15 15:53:36.890969	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
286	2	2026-09-15 15:53:37.647552	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
287	2	2026-09-15 15:53:37.951361	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
288	2	2026-09-15 15:53:38.216647	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
289	2	2026-09-15 15:54:52.393655	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
290	2	2026-09-15 15:54:54.241653	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
291	2	2026-09-15 15:54:55.793129	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
292	2	2026-09-15 15:57:39.947957	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
293	2	2026-09-15 15:58:11.845166	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
294	2	2026-09-15 15:59:30.386086	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
295	2	2026-09-15 15:59:31.704217	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
296	2	2026-09-15 15:59:32.714301	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada temporalmente
297	2	2026-09-15 15:59:34.616603	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	192.168.10.2	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
298	2	2026-09-15 20:00:27.013549	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
299	2	2026-09-15 20:00:31.219382	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
300	2	2026-09-15 20:03:50.812418	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
301	2	2026-09-15 20:03:58.872325	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
302	2	2026-09-15 20:04:03.18673	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
303	2	2026-09-15 20:04:07.619425	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
304	2	2026-09-15 20:04:12.132653	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
305	2	2026-09-15 20:04:52.509345	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
306	2	2026-09-15 20:04:56.508209	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
307	2	2026-09-15 20:05:19.682867	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
308	2	2026-09-15 20:06:04.625874	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
309	2	2026-09-15 20:06:09.157124	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
310	2	2026-09-15 20:06:13.294083	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
311	2	2026-09-15 20:06:17.228614	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
312	2	2026-09-15 20:06:18.84658	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
313	2	2026-09-15 20:06:20.057373	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
314	2	2026-09-15 20:07:12.529882	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
315	2	2026-09-15 20:13:49.725867	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
316	2	2026-09-15 20:14:23.418375	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
317	2	2026-09-15 20:14:33.739752	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
318	2	2026-09-15 20:14:41.100552	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
319	2	2026-09-15 20:15:27.270726	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
320	2	2026-09-15 20:15:31.933868	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
321	2	2026-09-15 20:15:37.709671	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
322	2	2026-09-15 20:15:42.70117	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
323	2	2026-09-15 20:15:46.437915	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
324	2	2026-09-15 20:16:49.154985	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
325	2	2026-09-15 20:17:06.749394	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
326	2	2026-09-15 20:17:12.311243	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
327	2	2026-09-15 20:17:18.978383	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
328	2	2026-09-15 20:17:37.911364	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada temporalmente
329	2	2026-09-15 20:17:43.345993	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
330	2	2026-09-15 20:20:49.665629	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
331	2	2026-09-15 20:22:05.416621	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
332	2	2026-09-15 20:22:09.239178	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
333	2	2026-09-15 20:22:15.851557	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada por 3 intentos fallidos
334	2	2026-09-17 01:06:45.482342	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
335	2	2026-09-17 01:20:23.191726	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
336	2	2026-09-17 01:20:33.286406	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
337	2	2026-09-17 01:27:07.880186	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
338	2	2026-09-17 01:27:48.304046	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
339	2	2026-09-17 01:37:27.285034	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
340	2	2026-09-17 01:37:29.855755	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
341	2	2026-09-17 01:37:35.100464	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
342	2	2026-09-17 01:37:38.496645	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
343	2	2026-09-17 01:37:41.238102	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
344	2	2026-09-17 01:41:02.257341	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
345	2	2026-09-17 01:41:12.654799	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
346	2	2026-09-17 01:41:20.536777	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
347	2	2026-09-17 01:41:28.963566	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
348	2	2026-09-16 21:57:21.818963	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
349	2	2026-09-16 21:57:30.197626	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
350	2	2026-09-16 21:57:33.959898	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
351	2	2026-09-16 21:57:36.222361	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
352	2	2026-09-16 21:58:15.653859	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
353	2	2026-09-16 21:58:18.351594	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
354	2	2026-09-16 21:59:28.175044	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
355	2	2026-09-16 21:59:33.458954	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	FALLIDO	Cuenta bloqueada
356	2	2026-09-16 22:01:48.866304	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
357	2	2026-09-16 23:09:02.904146	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
358	15	2026-09-16 23:09:11.143237	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
359	15	2026-09-16 23:09:29.762131	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
360	2	2026-09-16 23:09:34.384905	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
361	2	2026-09-17 00:16:44.849847	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
362	2	2026-09-17 00:16:53.834955	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
363	2	2026-09-17 00:17:13.401734	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
364	2	2026-09-17 00:17:34.769944	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
365	2	2026-09-17 00:38:58.34722	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
366	2	2026-09-17 00:40:19.678757	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
367	2	2026-09-17 00:55:58.932476	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
368	2	2026-09-17 00:56:10.594527	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
369	2	2026-09-17 01:38:43.829592	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
370	2	2026-09-17 01:39:05.928237	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
373	2	2026-09-17 02:24:55.535588	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
374	2	2026-09-17 02:28:16.195861	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
375	2	2026-09-17 02:37:22.013203	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
376	2	2026-09-17 02:40:29.251255	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
377	2	2026-09-17 03:41:18.913224	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
378	40	2026-09-17 03:41:20.246733	LOGIN	\N	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
379	39	2026-09-17 03:41:21.27481	LOGIN	\N	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
380	41	2026-09-17 03:41:22.175462	LOGIN	\N	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
381	2	2026-09-17 03:44:40.421266	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
385	2	2026-09-17 03:45:19.430915	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
389	2	2026-09-17 03:45:36.36625	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
390	2	2026-09-17 03:45:55.534611	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
394	2	2026-09-17 03:46:41.185979	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
398	2	2026-09-17 03:50:52.730494	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
402	2	2026-09-17 04:07:38.762689	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
403	2	2026-09-17 04:21:06.404217	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
404	2	2026-09-17 04:21:20.778468	LOGIN	\N	127.0.0.1	EXITOSO	Acceso correcto
405	2	2026-09-17 12:58:01.89507	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO	Acceso correcto
406	2	2026-09-18 01:32:05.61641	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
407	2	2026-09-18 02:05:45.957179	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
408	45	2026-09-18 02:05:52.152504	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
409	45	2026-09-18 06:24:49.718512	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
410	45	2026-09-18 11:22:38.063135	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
411	45	2026-09-18 16:11:07.152837	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
412	15	2026-09-18 16:11:16.560801	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
413	15	2026-09-18 16:49:50.520578	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
414	15	2026-09-18 19:12:24.230336	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
415	2	2026-09-18 19:12:36.474051	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
416	2	2026-09-18 20:45:38.328993	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
417	45	2026-09-18 20:45:56.323659	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
418	15	2026-09-18 20:57:28.968804	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
419	15	2026-09-19 00:31:04.537806	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
420	15	2026-09-19 00:31:14.053799	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
421	15	2026-09-19 10:21:18.598213	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
422	15	2026-09-19 13:20:12.696992	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
423	15	2026-09-19 20:46:20.626188	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
424	15	2026-09-20 00:45:09.527673	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
425	2	2026-09-21 09:50:32.010945	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
426	2	2026-09-21 09:50:39.824351	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
427	2	2026-09-21 10:16:25.957512	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
428	2	2026-09-21 10:16:36.040661	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
429	2	2026-09-21 10:20:59.681697	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
430	15	2026-09-21 10:21:09.423102	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
431	15	2026-09-21 10:23:04.05204	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
432	15	2026-09-21 10:23:14.150894	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
433	15	2026-09-21 10:23:19.730416	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
434	15	2026-09-21 10:23:25.201257	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
435	15	2026-09-21 10:23:31.46033	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Cuenta bloqueada
436	2	2026-09-21 10:30:51.302718	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
437	2	2026-09-26 14:15:11.357527	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
438	2	2026-09-26 15:36:07.730008	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
439	40	2026-09-26 15:36:14.40146	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
440	2	2026-09-26 15:36:23.904519	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
441	2	2026-09-26 15:36:38.222898	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
442	40	2026-09-26 15:36:46.5106	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
443	40	2026-09-26 16:32:44.389153	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
444	2	2026-09-26 16:32:52.04924	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
445	2	2026-09-26 16:39:38.418996	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
446	2	2026-09-26 16:39:44.064033	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
447	2	2026-09-26 16:48:44.302871	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
448	15	2026-09-26 16:48:49.164034	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
449	15	2026-09-26 17:01:15.192884	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
450	2	2026-09-26 17:01:54.272588	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
451	2	2026-09-26 17:01:59.679178	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
452	15	2026-09-26 17:02:04.420406	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
453	15	2026-09-26 17:07:37.969209	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
454	2	2026-09-26 17:07:46.143404	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
455	2	2026-09-26 18:11:58.949216	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
456	2	2026-09-26 18:19:58.033362	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
457	2	2026-09-26 18:26:43.035484	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
458	2	2026-09-26 18:28:06.137297	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
459	2	2026-09-26 18:28:11.120135	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
460	2	2026-09-26 18:30:10.958206	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
461	2	2026-09-26 18:47:43.56368	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
462	2	2026-09-26 18:49:40.922339	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
463	2	2026-09-26 19:14:23.226962	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
464	2	2026-09-26 19:14:27.75426	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
465	2	2026-09-26 19:25:37.625157	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
466	15	2026-09-26 19:25:42.805581	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
467	15	2026-09-26 19:26:59.876996	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
468	2	2026-09-26 19:27:12.275175	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
469	2	2026-09-26 19:30:05.286469	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
470	2	2026-09-26 19:30:11.318543	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
471	2	2026-09-26 19:30:15.666102	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
472	2	2026-09-26 19:30:24.652448	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
473	15	2026-09-26 19:30:31.606304	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
474	15	2026-09-26 19:34:53.287295	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
475	2	2026-09-26 19:34:58.065619	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
476	2	2026-09-26 19:35:03.117126	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Cuenta bloqueada
477	2	2026-09-26 19:35:13.553422	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Cuenta bloqueada
478	2	2026-09-26 19:36:02.368367	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
479	2	2026-09-26 19:36:06.859929	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
480	2	2026-09-26 20:14:24.856288	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
481	15	2026-09-26 20:14:39.277642	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
482	15	2026-09-26 20:20:32.164726	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
483	2	2026-09-26 20:20:36.975191	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
484	2	2026-09-26 21:06:27.40546	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
485	15	2026-09-26 21:06:32.881763	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
486	15	2026-09-26 22:18:59.517775	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
487	2	2026-09-26 22:19:05.334523	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
488	2	2026-09-26 23:38:59.779324	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
489	45	2026-09-26 23:39:06.876985	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
490	45	2026-09-26 23:40:26.819551	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
491	45	2026-09-26 23:40:32.287997	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
492	45	2026-09-27 00:43:59.958967	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
493	45	2026-09-27 00:44:06.402595	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
494	45	2026-09-27 01:56:00.123133	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
495	2	2026-09-27 01:56:15.926229	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
496	2	2026-09-28 17:06:28.168223	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
497	2	2026-09-28 18:21:43.789129	LOGIN	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO	Acceso correcto
498	2	2026-09-28 18:53:02.020615	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
499	2	2026-09-28 18:57:26.828515	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
500	73	2026-09-28 19:06:49.74085	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
501	73	2026-09-28 19:13:10.215053	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
502	2	2026-09-28 19:13:16.988077	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
503	2	2026-09-29 09:22:15.617941	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
504	2	2026-09-29 09:24:17.850797	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
505	2	2026-09-29 09:25:36.039276	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
506	2	2026-09-29 09:26:35.550229	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
507	\N	2026-09-29 09:26:47.778154	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Cuenta no encontrada
508	73	2026-09-29 09:26:56.402079	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
509	2	2026-09-29 09:51:45.738909	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
510	2	2026-09-29 09:51:55.398524	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
511	2	2026-09-29 09:52:00.062414	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
512	2	2026-09-29 09:52:03.975523	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	FALLIDO	Cuenta bloqueada
513	2	2026-09-29 09:54:35.478585	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO	Acceso correcto
514	2	2026-10-03 20:04:50.92929	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
515	2	2026-10-03 20:05:02.517817	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
516	2	2026-10-03 20:10:33.44343	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
517	2	2026-10-03 20:13:20.173282	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
518	\N	2026-10-03 20:39:22.963584	LOGIN	curl/8.21.0	127.0.0.1	FALLIDO	Cuenta no encontrada
519	\N	2026-10-03 20:39:28.208365	LOGIN	curl/8.21.0	127.0.0.1	FALLIDO	Cuenta no encontrada
520	15	2026-10-03 20:44:17.741466	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
521	15	2026-10-03 20:44:23.349484	LOGIN	curl/8.21.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
522	2	2026-10-03 20:51:51.280485	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
523	2	2026-10-03 20:52:02.782817	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
524	2	2026-10-03 20:53:11.599369	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
525	2	2026-10-03 20:53:17.389695	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
526	2	2026-10-03 20:53:24.102266	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
527	15	2026-10-03 20:54:56.887979	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
528	15	2026-10-03 20:55:11.437067	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
529	2	2026-10-03 20:55:16.809144	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
530	2	2026-10-03 21:27:49.741277	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
531	2	2026-10-03 21:27:57.151898	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
532	2	2026-10-03 21:28:03.137446	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
533	2	2026-10-03 21:28:28.413965	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
534	2	2026-10-03 21:28:40.737751	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
535	2	2026-10-03 21:28:42.68406	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
536	2	2026-10-03 21:29:38.158325	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
537	2	2026-10-03 21:33:38.863041	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
538	15	2026-10-03 21:38:40.879914	LOGIN	curl/8.21.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
539	15	2026-10-03 21:38:41.58654	LOGIN	curl/8.21.0	127.0.0.1	EXITOSO	Acceso correcto
540	2	2026-10-03 22:14:26.639137	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
541	2	2026-10-03 22:14:37.08078	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
542	2	2026-10-03 22:25:16.608131	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
543	2	2026-10-03 22:25:22.098657	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
544	2	2026-10-03 22:25:27.441319	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
545	2	2026-10-03 22:26:06.926877	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
546	2	2026-10-03 22:26:14.318219	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
547	2	2026-10-03 22:26:21.658371	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
548	2	2026-10-03 22:26:30.924424	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
549	2	2026-10-03 22:34:28.956437	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
550	2	2026-10-03 22:34:34.946978	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
551	2	2026-10-03 22:34:53.194065	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
552	2	2026-10-03 22:34:59.315284	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
553	2	2026-10-03 22:35:02.947885	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
554	2	2026-10-03 22:35:27.972112	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
555	2	2026-10-03 22:35:31.183534	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
556	2	2026-10-03 22:35:40.302398	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
557	2	2026-10-03 22:35:51.204631	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
558	2	2026-10-03 22:36:27.85395	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
559	2	2026-10-03 22:36:33.281716	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
560	2	2026-10-03 22:36:35.351942	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
561	2	2026-10-03 22:36:41.848943	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
562	2	2026-10-03 22:38:30.640175	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
563	2	2026-10-03 22:41:10.467332	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
564	2	2026-10-03 22:41:30.464914	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
565	2	2026-10-03 22:41:49.815031	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
566	15	2026-10-03 22:50:43.657144	LOGIN	Mozilla	127.0.0.1	FALLIDO	Contrasena incorrecta
567	2	2026-10-03 22:53:48.994088	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
568	2	2026-10-03 22:53:55.771045	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
569	2	2026-10-03 22:53:59.273998	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
570	2	2026-10-03 22:54:26.434078	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
571	2	2026-10-03 22:54:31.409797	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
572	2	2026-10-03 22:54:34.134967	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
573	2	2026-10-03 22:54:40.803582	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
574	2	2026-10-03 22:57:35.732886	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
575	2	2026-10-03 22:57:47.864056	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
576	2	2026-10-03 22:57:50.623014	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
577	2	2026-10-03 22:58:02.555846	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
578	2	2026-10-03 22:58:10.859437	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
579	2	2026-10-03 22:59:00.830246	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
580	2	2026-10-03 22:59:07.960712	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
581	2	2026-10-03 22:59:16.878453	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
582	2	2026-10-03 23:00:47.285467	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
583	2	2026-10-03 23:00:54.901239	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
584	2	2026-10-03 23:07:01.677715	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
585	2	2026-10-03 23:07:10.971677	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
586	2	2026-10-03 23:07:15.614313	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
587	2	2026-10-03 23:08:02.39735	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
588	2	2026-10-03 23:09:07.486453	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
589	2	2026-10-03 23:09:12.475178	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
590	2	2026-10-03 23:09:15.027212	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
591	2	2026-10-03 23:09:21.713448	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
592	2	2026-10-03 23:10:26.690906	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
593	2	2026-10-03 23:10:41.86439	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
594	2	2026-10-03 23:10:50.322759	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
595	2	2026-10-03 23:22:08.080273	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
596	2	2026-10-03 23:23:32.598568	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
597	2	2026-10-03 23:29:25.449355	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
598	2	2026-10-03 23:29:31.59261	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
599	2	2026-10-03 23:34:16.822167	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
600	2	2026-10-03 23:48:49.475934	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
601	2	2026-10-04 00:13:31.69776	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
602	2	2026-10-04 00:13:36.457301	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
603	2	2026-10-04 01:02:09.41876	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
604	2	2026-10-04 01:02:28.615478	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
605	2	2026-10-04 01:02:59.231856	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
606	2	2026-10-04 01:03:02.817267	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
607	2	2026-10-04 01:03:05.95294	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
608	2	2026-10-04 01:03:14.583454	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
609	2	2026-10-04 01:03:17.942418	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
610	2	2026-10-04 01:03:33.483701	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
611	2	2026-10-04 01:04:43.371003	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
612	2	2026-10-04 01:04:55.054498	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
613	2	2026-10-04 01:08:45.739682	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
614	2	2026-10-04 01:08:49.33897	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
615	2	2026-10-04 01:08:57.107454	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
616	2	2026-10-04 01:10:31.691808	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
617	2	2026-10-04 01:10:34.934789	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
618	2	2026-10-04 01:10:42.734754	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
619	2	2026-10-04 01:12:25.350421	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
620	2	2026-10-04 01:13:52.700367	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
621	2	2026-10-04 01:13:54.886865	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
622	2	2026-10-04 01:14:00.876826	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
623	2	2026-10-04 01:17:51.927037	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
624	2	2026-10-04 01:17:54.961705	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
625	2	2026-10-04 01:18:02.007952	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
626	2	2026-10-04 01:18:07.910467	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
627	2	2026-10-04 01:18:49.038414	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
628	2	2026-10-04 01:19:14.516719	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
629	2	2026-10-04 01:19:33.146698	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
630	2	2026-10-04 01:19:49.100441	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
631	2	2026-10-04 01:19:55.152038	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
632	2	2026-10-04 01:19:58.036377	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
633	2	2026-10-04 01:20:03.893562	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
634	2	2026-10-04 01:20:07.247556	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
635	2	2026-10-04 01:20:14.69092	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
636	2	2026-10-04 01:20:20.083497	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
637	2	2026-10-04 01:20:24.412314	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
638	2	2026-10-04 01:23:28.558446	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
639	2	2026-10-04 01:23:35.635694	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
640	2	2026-10-04 01:23:37.799466	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Cuenta bloqueada
641	2	2026-10-04 01:23:53.268826	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
642	2	2026-10-04 01:23:56.151964	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
643	2	2026-10-04 01:24:04.638108	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
644	2	2026-10-04 01:24:10.236508	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	FALLIDO	Contrase├▒a incorrecta
645	2	2026-10-04 01:24:20.641096	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
646	2	2026-10-04 02:47:56.699778	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
647	2	2026-10-04 11:20:17.183745	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
648	2	2026-10-04 20:30:05.699072	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
649	2	2026-10-05 18:41:20.147528	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
650	2	2026-10-07 14:58:59.802661	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
651	2	2026-10-07 15:26:02.76667	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
652	2	2026-10-07 15:43:45.712784	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
653	2	2026-10-07 15:48:55.422954	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
654	2	2026-10-07 15:54:27.611966	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
655	2	2026-10-07 16:07:43.35745	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
656	2	2026-10-07 16:46:52.808541	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
657	2	2026-10-07 16:47:37.422405	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
658	2	2026-10-11 23:28:44.529229	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
659	2	2026-10-11 23:55:03.083543	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
660	2	2026-10-12 01:00:27.778582	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
661	45	2026-10-12 01:00:34.087468	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
662	45	2026-10-12 01:00:43.176542	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
693	2	2026-10-12 08:28:19.926832	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
694	2	2026-10-12 09:21:43.62293	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
695	2	2026-10-12 09:46:00.083777	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
696	2	2026-10-13 11:23:33.314593	LOGOUT	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Sesi├│n cerrada
697	2	2026-10-13 11:32:19.107042	LOGIN	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO	Acceso correcto
\.


--
-- Data for Name: bitacoraaccion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacoraaccion (id, idempleado, idpermiso, fechahora, accion, tablaafectada, idregistroafectado, equipo, ip, resultado) FROM stdin;
75	2	36	2026-09-14 17:56:30.944136	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
77	2	32	2026-09-14 19:01:09.868254	UPDATE	mesa	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
79	2	36	2026-09-14 19:07:12.799205	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
81	2	36	2026-09-14 19:10:53.325179	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
83	2	36	2026-09-14 19:11:18.701845	UPDATE	horariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
85	2	36	2026-09-14 19:11:31.751736	UPDATE	horariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
87	2	36	2026-09-14 19:24:23.739373	UPDATE	horariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
89	2	26	2026-09-14 19:26:55.922438	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
91	2	14	2026-09-14 20:05:25.341096	CREATE	empleado	42	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
93	2	15	2026-09-14 20:06:58.211753	UPDATE	empleado	43	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
848	45	87	2026-09-18 16:07:58.138119	CREATE	pedido	96	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
97	40	17	2026-09-14 20:14:17.828705	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
99	40	18	2026-09-14 20:15:42.18234	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
101	2	26	2026-09-14 20:17:06.528254	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
103	15	15	2026-09-14 20:20:03.797951	UPDATE	empleado	41	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
105	15	15	2026-09-14 20:27:10.788393	UPDATE	empleado	41	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
107	2	26	2026-09-14 20:29:10.900235	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
109	2	26	2026-09-14 20:50:58.470574	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
111	2	14	2026-09-14 20:59:26.204601	CREATE	empleado	47	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
1063	15	87	2026-09-18 17:38:36.982369	CREATE	pedido	148	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
115	40	15	2026-09-14 21:02:26.299892	UPDATE	empleado	39	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
117	2	31	2026-09-14 21:09:24.901413	CREATE	mesa	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
119	15	34	2026-09-14 21:40:37.405635	DELETE	mesa	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1106	15	87	2026-09-18 18:46:39.584719	CREATE	pedido	159	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1108	2	92	2026-09-18 18:54:50.432621	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
125	15	31	2026-09-14 21:53:53.184445	CREATE	mesa	12	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1130	2	18	2026-09-18 19:01:10.360064	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1132	15	61	2026-09-18 21:10:49.600079	CREATE	promocion	116	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1134	15	62	2026-09-18 21:29:39.800297	UPDATE	promocion	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1136	15	90	2026-09-18 22:13:25.656006	DELETE	pedido	97	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1138	15	87	2026-09-18 22:14:59.465722	CREATE	pedido	166	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1140	15	80	2026-09-19 10:22:21.888342	CREATE	venta	109	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
139	2	23	2026-09-14 23:08:40.875351	CREATE	sucursal	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
354	15	59	2026-09-17 14:05:05.829246	UPDATE	detallereceta	15	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
143	15	24	2026-09-14 23:22:25.743585	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
360	15	56	2026-09-17 14:14:15.837835	UPDATE	insumo	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
147	2	46	2026-09-14 23:39:40.174024	DELETE	producto	44	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
365	15	55	2026-09-17 14:18:26.759819	CREATE	insumo	13	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
370	15	93	2026-09-17 14:30:45.034838	CREATE	movimientoinventario	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
384	15	62	2026-09-18 01:52:51.30379	UPDATE	promocion	6	Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36	127.0.0.1	EXITOSO
1141	15	88	2026-09-19 10:22:21.888342	UPDATE	pedido	167	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
76	2	36	2026-09-14 17:56:43.078624	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
78	2	36	2026-09-14 19:04:57.850679	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
80	2	36	2026-09-14 19:08:24.112003	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
82	2	35	2026-09-14 19:11:10.499811	CREATE	horariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
84	2	36	2026-09-14 19:11:23.828443	UPDATE	horariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
86	2	35	2026-09-14 19:24:15.768063	CREATE	horariosucursal	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
88	2	36	2026-09-14 19:24:31.797353	UPDATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
90	40	14	2026-09-14 19:29:08.778986	CREATE	empleado	41	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
92	2	14	2026-09-14 20:05:27.102884	CREATE	empleado	43	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
94	2	26	2026-09-14 20:06:58.995272	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
96	2	26	2026-09-14 20:07:02.646455	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
98	40	17	2026-09-14 20:15:29.197683	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
100	40	14	2026-09-14 20:16:21.811143	CREATE	empleado	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
102	15	15	2026-09-14 20:19:26.472866	UPDATE	empleado	41	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
104	15	15	2026-09-14 20:23:29.404647	UPDATE	empleado	41	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
106	15	14	2026-09-14 20:28:21.721615	CREATE	empleado	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
108	2	26	2026-09-14 20:49:40.747397	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
110	2	26	2026-09-14 20:52:02.860376	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
112	2	26	2026-09-14 21:00:31.749884	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
114	2	26	2026-09-14 21:01:45.607842	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
116	2	26	2026-09-14 21:02:52.234864	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
118	15	34	2026-09-14 21:40:30.906074	DELETE	mesa	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
120	15	65	2026-09-14 21:41:47.052557	CREATE	caja	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
349	15	61	2026-09-17 12:57:32.506341	CREATE	promocion	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
59	2	18	2026-09-14 01:03:48	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
355	15	55	2026-09-17 14:06:19.894728	CREATE	insumo	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
126	2	92	2026-09-14 21:57:05.077185	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
64	2	15	2026-09-14 05:29:30.678866	UPDATE	empleado	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
65	2	18	2026-09-14 05:29:30.691395	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
67	2	14	2026-09-14 02:14:45	CREATE	empleado	38	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
68	2	23	2026-09-14 02:25:19	CREATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
69	2	26	2026-09-14 02:25:19	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
70	15	14	2026-09-14 02:26:07	CREATE	empleado	39	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
71	15	15	2026-09-14 15:13:49.768345	UPDATE	empleado	15	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
72	2	14	2026-09-14 16:02:48.498306	CREATE	empleado	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
73	2	35	2026-09-14 17:40:07.651301	CREATE	horariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
74	2	31	2026-09-14 17:40:21.903794	CREATE	mesa	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
140	2	92	2026-09-14 23:09:52.334487	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
142	15	24	2026-09-14 23:21:40.520699	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
144	2	44	2026-09-14 23:24:25.930684	UPDATE	producto	39	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
148	2	44	2026-09-14 23:40:00.272567	UPDATE	producto	44	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
350	2	92	2026-09-17 12:58:13.239895	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
356	15	58	2026-09-17 14:06:49.480475	CREATE	detallereceta	26	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
160	2	46	2026-09-14 23:51:06.497062	DELETE	producto	39	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
161	2	43	2026-09-14 23:51:10.058776	CREATE	producto	39	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
162	2	43	2026-09-14 23:52:03.678836	CREATE	producto	44	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
361	15	55	2026-09-17 14:14:51.919926	CREATE	insumo	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
366	15	58	2026-09-17 14:20:09.242623	CREATE	detallereceta	29	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
371	15	53	2026-09-17 14:33:37.881015	UPDATE	unidadmedida	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
166	2	42	2026-09-14 23:57:54.526066	DELETE	categoria	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
167	2	39	2026-09-14 23:57:57.653488	CREATE	categoria	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
168	2	42	2026-09-14 23:58:47.593721	DELETE	categoria	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
849	45	88	2026-09-18 16:08:10.359331	UPDATE	pedido	96	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
385	15	62	2026-09-18 01:54:45.564158	UPDATE	promocion	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
184	15	52	2026-09-15 01:10:47.246292	CREATE	unidadmedida	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
185	15	57	2026-09-15 01:12:15.175604	DELETE	insumo	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
186	15	55	2026-09-15 01:13:14.811827	CREATE	insumo	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
187	15	58	2026-09-15 02:10:36.757378	CREATE	detallereceta	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
188	2	58	2026-09-15 02:15:30.635703	CREATE	detallereceta	4	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
189	2	57	2026-09-15 02:15:32.068228	DELETE	insumo	2	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
190	2	55	2026-09-15 02:15:33.256278	CREATE	insumo	2	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
191	2	57	2026-09-15 02:17:49.8152	DELETE	insumo	2	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
192	2	55	2026-09-15 02:17:51.116295	CREATE	insumo	2	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
193	2	57	2026-09-15 02:18:40.209571	DELETE	insumo	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
194	2	46	2026-09-15 02:20:42.963728	DELETE	producto	44	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
195	2	43	2026-09-15 02:20:46.41109	CREATE	producto	44	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
196	2	55	2026-09-15 02:28:40.024146	CREATE	insumo	2	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
197	2	55	2026-09-15 02:30:20.625055	CREATE	insumo	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
198	2	58	2026-09-15 02:31:10.903485	CREATE	detallereceta	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
199	2	56	2026-09-15 02:34:41.391844	UPDATE	insumo	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
200	2	58	2026-09-15 02:35:13.60704	CREATE	detallereceta	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
201	2	58	2026-09-15 02:35:21.788699	CREATE	detallereceta	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
202	2	58	2026-09-15 02:35:31.131361	CREATE	detallereceta	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
203	2	58	2026-09-15 02:35:37.310558	CREATE	detallereceta	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
204	2	58	2026-09-15 02:35:44.832388	CREATE	detallereceta	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
205	2	58	2026-09-15 02:35:52.150732	CREATE	detallereceta	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
206	2	58	2026-09-15 02:35:59.41733	CREATE	detallereceta	12	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
207	2	58	2026-09-15 02:36:19.722787	CREATE	detallereceta	13	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
208	2	58	2026-09-15 02:36:40.119497	CREATE	detallereceta	14	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
209	2	58	2026-09-15 02:36:54.544258	CREATE	detallereceta	15	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
210	2	58	2026-09-15 02:37:00.913234	CREATE	detallereceta	16	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
211	2	59	2026-09-15 02:37:24.22214	UPDATE	detallereceta	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
212	2	58	2026-09-15 02:37:30.534664	CREATE	detallereceta	17	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
213	2	59	2026-09-15 02:37:41.08185	UPDATE	detallereceta	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
214	2	58	2026-09-15 02:37:46.213279	CREATE	detallereceta	18	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
215	2	59	2026-09-15 02:37:54.953731	UPDATE	detallereceta	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
216	2	58	2026-09-15 02:37:59.95874	CREATE	detallereceta	19	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
217	2	59	2026-09-15 02:38:08.900944	UPDATE	detallereceta	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
218	2	58	2026-09-15 02:38:15.126786	CREATE	detallereceta	20	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
219	2	59	2026-09-15 02:38:25.093358	UPDATE	detallereceta	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
220	2	58	2026-09-15 02:38:31.243632	CREATE	detallereceta	21	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
221	2	55	2026-09-15 02:46:01.916972	CREATE	insumo	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
222	2	58	2026-09-15 02:46:25.092316	CREATE	detallereceta	22	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
223	2	55	2026-09-15 02:47:54.221411	CREATE	insumo	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
224	2	58	2026-09-15 02:48:19.614805	CREATE	detallereceta	23	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
225	2	39	2026-09-15 02:48:51.555757	CREATE	categoria	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
226	2	43	2026-09-15 02:50:20.666948	CREATE	producto	49	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
227	2	58	2026-09-15 02:50:54.996675	CREATE	detallereceta	24	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
228	2	43	2026-09-15 02:52:16.488336	CREATE	producto	50	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
229	2	58	2026-09-15 02:52:59.371584	CREATE	detallereceta	25	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
351	15	61	2026-09-17 13:00:05.224567	CREATE	promocion	4	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
357	15	59	2026-09-17 14:11:57.967382	UPDATE	detallereceta	26	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
362	15	58	2026-09-17 14:15:44.131802	CREATE	detallereceta	27	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
367	15	58	2026-09-17 14:20:26.900675	CREATE	detallereceta	30	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
234	39	93	2026-09-15 03:39:10.140817	CREATE	movimientoinventario	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
235	39	93	2026-09-15 03:39:53.212926	CREATE	movimientoinventario	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
236	39	93	2026-09-15 03:40:25.756922	CREATE	movimientoinventario	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
237	2	93	2026-09-15 03:42:10.860484	CREATE	movimientoinventario	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
238	2	93	2026-09-15 03:42:50.397299	CREATE	movimientoinventario	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
239	2	93	2026-09-15 03:46:35.211481	CREATE	movimientoinventario	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
382	15	61	2026-09-18 01:47:59.427541	CREATE	promocion	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
386	15	62	2026-09-18 01:55:15.319488	UPDATE	promocion	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
352	15	47	2026-09-17 13:34:28.058195	CREATE	sucursal_producto	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
244	2	93	2026-09-15 03:55:42.120184	CREATE	movimientoinventario	7	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
245	2	93	2026-09-15 03:55:43.480979	CREATE	movimientoinventario	7	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
246	2	93	2026-09-15 03:55:44.761031	CREATE	movimientoinventario	7	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444	127.0.0.1	EXITOSO
358	15	52	2026-09-17 14:13:05.269067	CREATE	unidadmedida	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
363	15	58	2026-09-17 14:15:56.536782	CREATE	detallereceta	28	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
249	15	95	2026-09-15 04:31:52.035196	UPDATE	inventariosucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
250	15	95	2026-09-15 04:31:57.313722	UPDATE	inventariosucursal	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
251	15	93	2026-09-15 04:57:51.175383	CREATE	movimientoinventario	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
252	15	93	2026-09-15 04:58:25.46686	CREATE	movimientoinventario	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
253	39	93	2026-09-15 04:58:54.857131	CREATE	movimientoinventario	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
254	2	93	2026-09-15 04:59:16.808231	CREATE	movimientoinventario	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
255	2	92	2026-09-15 16:40:25.898673	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
256	2	18	2026-09-15 17:26:39.48119	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.137.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36	127.0.0.1	EXITOSO
257	2	17	2026-09-17 00:58:13.209691	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
258	2	92	2026-09-16 22:22:37.102691	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
259	2	92	2026-09-16 22:24:39.284475	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
260	2	92	2026-09-16 22:59:01.364665	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
261	2	17	2026-09-16 23:28:01.722455	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
262	2	17	2026-09-16 23:28:34.23634	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
263	2	18	2026-09-16 23:28:50.312632	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
264	2	17	2026-09-16 23:46:02.631605	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
265	2	17	2026-09-16 23:46:05.777992	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
266	2	15	2026-09-16 23:50:17.533731	UPDATE	empleado	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
267	2	26	2026-09-17 00:16:54.651908	UPDATE	sucursal	8	\N	127.0.0.1	EXITOSO
268	2	26	2026-09-17 00:17:14.274008	UPDATE	sucursal	8	\N	127.0.0.1	EXITOSO
269	2	26	2026-09-17 00:17:35.546978	UPDATE	sucursal	8	\N	127.0.0.1	EXITOSO
270	2	26	2026-09-17 00:27:52.46349	UPDATE	sucursal	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
271	40	65	2026-09-17 00:28:49.218783	CREATE	caja	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
272	40	65	2026-09-17 00:29:15.728587	CREATE	caja	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
273	2	65	2026-09-17 00:38:59.087332	CREATE	caja	13	\N	127.0.0.1	EXITOSO
274	2	66	2026-09-17 00:39:00.523419	UPDATE	caja	13	\N	127.0.0.1	EXITOSO
275	2	68	2026-09-17 00:39:01.128828	DELETE	caja	14	\N	127.0.0.1	EXITOSO
276	2	65	2026-09-17 00:40:21.40411	CREATE	caja	15	\N	127.0.0.1	EXITOSO
368	15	55	2026-09-17 14:20:54.244384	CREATE	insumo	14	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
283	2	31	2026-09-17 00:53:52.32176	CREATE	mesa	15	a	ip	EXITOSO
291	2	31	2026-09-17 01:09:14.836055	CREATE	mesa	18	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
292	2	31	2026-09-17 01:09:22.886596	CREATE	mesa	19	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
293	2	35	2026-09-17 01:09:51.377641	CREATE	horariosucursal	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
294	2	44	2026-09-17 01:13:42.700681	UPDATE	producto	14	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
295	2	44	2026-09-17 01:14:36.649945	UPDATE	producto	11	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
296	2	44	2026-09-17 01:15:16.153631	UPDATE	producto	20	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
297	2	44	2026-09-17 01:15:44.835719	UPDATE	producto	4	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
298	2	44	2026-09-17 01:16:10.747124	UPDATE	producto	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
299	2	44	2026-09-17 01:16:30.61766	UPDATE	producto	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
300	2	44	2026-09-17 01:17:01.037652	UPDATE	producto	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
301	2	44	2026-09-17 01:17:32.72233	UPDATE	producto	13	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
302	2	44	2026-09-17 01:17:50.94375	UPDATE	producto	12	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
303	2	44	2026-09-17 01:18:10.075205	UPDATE	producto	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
304	2	44	2026-09-17 01:19:17.651133	UPDATE	producto	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
305	2	92	2026-09-17 02:08:47.365975	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
353	15	49	2026-09-17 13:34:48.555802	UPDATE	sucursal_producto	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
359	15	55	2026-09-17 14:14:03.700593	CREATE	insumo	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
364	15	55	2026-09-17 14:16:42.150498	CREATE	insumo	12	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
369	15	58	2026-09-17 14:21:23.371327	CREATE	detallereceta	31	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
383	15	62	2026-09-18 01:49:06.532508	UPDATE	promocion	4	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
387	15	64	2026-09-18 01:55:36.388201	DELETE	promocion	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
399	15	62	2026-09-18 02:01:33.869918	UPDATE	promocion	4	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
708	45	69	2026-09-18 06:26:48.212522	CREATE	aperturacaja	34	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
850	45	87	2026-09-18 16:08:52.336079	CREATE	pedido	97	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
315	40	93	2026-09-17 02:26:12.31113	CREATE	movimientoinventario	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
316	2	93	2026-09-17 02:30:31.89309	CREATE	movimientoinventario	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
317	40	95	2026-09-17 02:31:01.522765	UPDATE	inventariosucursal	10	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
318	40	95	2026-09-17 02:31:08.388773	UPDATE	inventariosucursal	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
319	40	95	2026-09-17 02:31:16.409544	UPDATE	inventariosucursal	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
320	15	47	2026-09-17 03:56:52.302347	CREATE	sucursal_producto	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
321	2	49	2026-09-17 04:10:39.662355	UPDATE	sucursal_producto	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
322	15	18	2026-09-17 04:12:40.919172	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
323	2	47	2026-09-17 04:17:16.396044	CREATE	sucursal_producto	4	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
324	2	47	2026-09-17 04:17:29.517362	CREATE	sucursal_producto	5	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
325	2	47	2026-09-17 04:17:39.18828	CREATE	sucursal_producto	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
326	2	47	2026-09-17 04:17:59.204003	CREATE	sucursal_producto	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
327	2	47	2026-09-17 04:18:15.386503	CREATE	sucursal_producto	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0	127.0.0.1	EXITOSO
328	15	47	2026-09-17 04:28:00.204661	CREATE	sucursal_producto	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
977	15	88	2026-09-18 16:37:18.332228	UPDATE	pedido	112	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
851	45	87	2026-09-18 16:09:45.519967	CREATE	pedido	98	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
913	15	87	2026-09-18 16:23:00.534476	CREATE	pedido	112	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1107	15	62	2026-09-18 18:48:48.002679	UPDATE	promocion	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1020	15	88	2026-09-18 16:54:56.978198	UPDATE	pedido	98	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1131	15	62	2026-09-18 21:07:59.836454	UPDATE	promocion	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1133	15	62	2026-09-18 21:29:26.148568	UPDATE	promocion	116	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1135	15	87	2026-09-18 21:57:52.295078	CREATE	pedido	165	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1137	15	90	2026-09-18 22:13:51.232648	DELETE	pedido	148	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1139	15	87	2026-09-19 00:31:35.688612	CREATE	pedido	167	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1142	15	87	2026-09-19 10:23:07.188763	CREATE	pedido	168	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1143	15	88	2026-09-19 10:23:29.605333	UPDATE	pedido	168	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1144	15	80	2026-09-19 10:23:43.750733	CREATE	venta	110	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1145	15	88	2026-09-19 10:23:43.750733	UPDATE	pedido	168	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1146	15	87	2026-09-19 13:20:31.23109	CREATE	pedido	169	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1147	15	88	2026-09-19 13:20:41.786281	UPDATE	pedido	169	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1148	15	88	2026-09-19 13:21:12.170806	UPDATE	pedido	169	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1149	15	80	2026-09-19 13:21:59.755516	CREATE	venta	111	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1150	15	88	2026-09-19 13:21:59.755516	UPDATE	pedido	169	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
825	15	31	2026-09-18 13:46:02.555689	CREATE	mesa	20	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
826	15	31	2026-09-18 13:46:07.122811	CREATE	mesa	21	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1151	15	87	2026-09-19 13:23:03.573579	CREATE	pedido	170	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1152	15	88	2026-09-19 14:03:47.012952	UPDATE	pedido	170	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1153	15	80	2026-09-19 14:05:53.102186	CREATE	venta	112	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1154	15	88	2026-09-19 14:05:53.102186	UPDATE	pedido	170	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1155	15	73	2026-09-19 14:07:25.018182	CREATE	movimientocaja	120	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1156	15	73	2026-09-19 14:08:30.401019	CREATE	movimientocaja	121	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1157	15	77	2026-09-19 14:09:31.87426	CREATE	cierrecaja	30	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1158	15	87	2026-09-19 14:10:06.93148	CREATE	pedido	171	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1159	15	90	2026-09-19 14:10:52.107995	DELETE	pedido	171	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1160	15	69	2026-09-19 14:13:28.240304	CREATE	aperturacaja	83	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1161	15	87	2026-09-19 14:30:14.446035	CREATE	pedido	172	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1162	15	80	2026-09-19 14:31:17.979866	CREATE	venta	113	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1163	15	88	2026-09-19 14:31:17.979866	UPDATE	pedido	172	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1164	15	87	2026-09-19 14:32:11.894549	CREATE	pedido	173	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1165	15	88	2026-09-19 14:32:17.528453	UPDATE	pedido	173	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1166	15	80	2026-09-19 14:32:50.093431	CREATE	venta	114	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1167	15	88	2026-09-19 14:32:50.093431	UPDATE	pedido	173	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1168	15	87	2026-09-19 14:33:07.641415	CREATE	pedido	174	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1169	15	88	2026-09-19 14:33:27.397705	UPDATE	pedido	174	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1170	15	80	2026-09-19 14:34:22.485694	CREATE	venta	115	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1171	15	88	2026-09-19 14:34:22.485694	UPDATE	pedido	174	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1172	15	73	2026-09-19 14:36:30.665611	CREATE	movimientocaja	122	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1173	15	73	2026-09-19 14:36:49.121455	CREATE	movimientocaja	123	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1174	15	87	2026-09-19 15:34:52.648509	CREATE	pedido	175	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1175	15	88	2026-09-19 15:35:06.889833	UPDATE	pedido	175	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1176	15	80	2026-09-19 15:35:28.393356	CREATE	venta	116	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1177	15	88	2026-09-19 15:35:28.393356	UPDATE	pedido	175	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1178	15	77	2026-09-19 15:59:48.337829	CREATE	cierrecaja	79	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1179	15	69	2026-09-19 21:24:20.202543	CREATE	aperturacaja	84	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1180	15	73	2026-09-19 21:24:49.765913	CREATE	movimientocaja	124	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1181	15	73	2026-09-19 21:25:00.360794	CREATE	movimientocaja	125	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1182	15	74	2026-09-19 21:25:05.927631	UPDATE	movimientocaja	124	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1183	15	74	2026-09-19 21:25:15.38107	UPDATE	movimientocaja	124	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1184	15	74	2026-09-19 21:25:21.039085	UPDATE	movimientocaja	125	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1185	41	87	2026-09-19 21:25:39.916658	CREATE	pedido	176	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1186	39	87	2026-09-19 21:26:25.277664	CREATE	pedido	177	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1187	39	80	2026-09-19 21:26:39.333162	CREATE	venta	117	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1188	39	88	2026-09-19 21:26:39.333162	UPDATE	pedido	177	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1189	39	80	2026-09-19 21:27:16.106061	CREATE	venta	118	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1190	39	88	2026-09-19 21:27:16.106061	UPDATE	pedido	176	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1191	39	77	2026-09-19 21:28:36.013726	CREATE	cierrecaja	80	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1192	39	69	2026-09-19 21:31:05.448166	CREATE	aperturacaja	85	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1193	39	73	2026-09-19 21:31:22.984631	CREATE	movimientocaja	126	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1194	39	73	2026-09-19 21:31:39.071895	CREATE	movimientocaja	127	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1195	39	73	2026-09-19 21:32:01.428948	CREATE	movimientocaja	128	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1196	41	87	2026-09-19 21:33:03.155709	CREATE	pedido	178	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1197	39	87	2026-09-19 21:33:49.246925	CREATE	pedido	179	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1198	39	87	2026-09-19 21:35:58.491514	CREATE	pedido	180	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1199	39	87	2026-09-19 21:36:24.987525	CREATE	pedido	181	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1200	39	87	2026-09-19 22:43:23.62517	CREATE	pedido	182	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1201	39	87	2026-09-19 22:43:51.795729	CREATE	pedido	183	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1202	39	87	2026-09-19 22:44:22.219373	CREATE	pedido	184	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1203	39	87	2026-09-20 00:43:42.617816	CREATE	pedido	185	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1204	39	90	2026-09-20 00:44:23.718429	DELETE	pedido	184	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1205	39	90	2026-09-20 00:44:30.103804	DELETE	pedido	183	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1206	39	90	2026-09-20 00:44:36.111865	DELETE	pedido	182	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1207	39	80	2026-09-20 00:45:40.888436	CREATE	venta	119	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1208	39	88	2026-09-20 00:45:40.888436	UPDATE	pedido	185	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1209	39	90	2026-09-20 00:46:01.126716	DELETE	pedido	178	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1210	39	90	2026-09-20 00:46:06.629356	DELETE	pedido	179	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1211	39	80	2026-09-20 00:46:18.842176	CREATE	venta	120	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1212	39	88	2026-09-20 00:46:18.842176	UPDATE	pedido	181	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1213	39	80	2026-09-20 00:46:31.978077	CREATE	venta	121	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1214	39	88	2026-09-20 00:46:31.978077	UPDATE	pedido	180	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0	127.0.0.1	EXITOSO
1215	15	77	2026-09-20 01:03:58.068683	CREATE	cierrecaja	81	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1216	2	92	2026-09-21 09:56:59.995954	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1217	2	18	2026-09-26 15:36:35.581522	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1218	2	46	2026-09-26 16:48:22.719502	DELETE	producto	50	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1219	2	17	2026-09-26 17:21:01.875417	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1220	2	17	2026-09-26 17:21:10.71507	UPDATE	cuentausuario	37	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1221	2	18	2026-09-26 18:48:23.578128	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1222	2	18	2026-09-26 18:49:31.82875	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1223	15	69	2026-09-26 21:33:12.81081	CREATE	aperturacaja	86	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1224	15	87	2026-09-26 21:51:55.58981	CREATE	pedido	186	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1225	15	88	2026-09-26 21:52:08.537148	UPDATE	pedido	186	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1226	45	80	2026-09-27 00:39:18.836676	CREATE	venta	122	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1227	45	88	2026-09-27 00:39:18.836676	UPDATE	pedido	186	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1228	2	14	2026-09-28 17:35:08.081917	CREATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1229	2	17	2026-09-28 17:38:21.703815	UPDATE	cuentausuario	63	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1230	2	17	2026-09-28 17:39:07.77353	UPDATE	cuentausuario	63	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1231	2	15	2026-09-28 17:48:31.233851	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1232	2	15	2026-09-28 17:54:13.314235	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1233	2	15	2026-09-28 17:54:40.57344	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1234	2	15	2026-09-28 17:54:54.132105	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1235	2	15	2026-09-28 18:03:38.997529	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1236	2	18	2026-09-28 18:03:39.015607	UPDATE	cuentausuario	63	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1237	2	92	2026-09-28 18:50:20.110373	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1238	2	15	2026-09-28 19:13:35.256114	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1239	2	15	2026-09-28 19:13:49.591292	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1240	2	15	2026-09-28 19:13:56.824991	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1241	2	18	2026-09-29 09:24:01.62138	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1242	2	18	2026-09-29 09:26:14.721351	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1243	2	15	2026-09-29 09:28:33.525974	UPDATE	empleado	73	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1244	2	92	2026-09-29 09:31:18.20768	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1245	2	17	2026-09-29 09:55:50.319608	UPDATE	cuentausuario	63	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1246	2	17	2026-09-29 09:56:38.112207	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1247	2	92	2026-09-29 09:57:28.170241	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	127.0.0.1	EXITOSO
1248	2	17	2026-10-04 02:12:36.550916	UPDATE	cuentausuario	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1249	2	17	2026-10-04 02:12:40.43174	UPDATE	cuentausuario	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1250	2	15	2026-10-04 02:19:19.863476	UPDATE	empleado	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1251	2	17	2026-10-04 03:27:11.252471	UPDATE	cuentausuario	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1252	2	17	2026-10-04 03:27:14.550309	UPDATE	cuentausuario	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1253	2	15	2026-10-04 03:27:24.304135	UPDATE	empleado	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1254	2	14	2026-10-04 03:36:02.258519	CREATE	empleado	74	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1255	2	15	2026-10-04 03:45:21.595552	UPDATE	empleado	45	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1256	2	18	2026-10-04 03:46:16.027026	UPDATE	cuentausuario	40	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1257	2	17	2026-10-04 04:18:14.329029	UPDATE	cuentausuario	64	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1258	2	17	2026-10-04 04:18:19.812397	UPDATE	cuentausuario	64	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1259	2	14	2026-10-04 11:58:13.588414	CREATE	empleado	75	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1260	40	17	2026-10-04 12:04:40.379574	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1261	40	15	2026-10-04 12:04:57.779545	UPDATE	empleado	38	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1262	40	18	2026-10-04 12:05:24.718522	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1263	2	92	2026-10-04 12:45:55.65414	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1264	2	92	2026-10-04 12:46:15.552174	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1265	2	92	2026-10-04 12:48:50.202252	UPDATE	configuracion_sistema	\N	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1266	40	36	2026-10-05 01:00:13.060659	UPDATE	horariosucursal	8	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1267	2	17	2026-10-05 18:44:27.700234	UPDATE	cuentausuario	65	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1268	2	17	2026-10-05 18:44:30.264422	UPDATE	cuentausuario	65	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1269	40	17	2026-10-05 21:20:27.14601	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1270	40	17	2026-10-05 21:20:30.643141	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1271	40	17	2026-10-05 21:21:27.540501	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1272	40	29	2026-10-05 21:56:30.961071	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1273	2	17	2026-10-05 21:57:14.531759	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1274	2	17	2026-10-05 21:57:24.628737	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1275	40	29	2026-10-05 21:57:45.954459	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1276	40	29	2026-10-05 21:57:50.105979	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1277	40	29	2026-10-05 22:00:11.97751	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1278	40	29	2026-10-05 22:17:43.647013	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1279	40	29	2026-10-05 22:17:45.582306	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1280	40	29	2026-10-05 22:28:26.259743	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1281	40	29	2026-10-05 22:28:28.23398	UPDATE	cuentausuario	36	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1282	40	27	2026-10-06 16:37:35.709323	UPDATE	sucursal	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	127.0.0.1	EXITOSO
1283	2	27	2026-10-06 16:39:02.88934	UPDATE	sucursal	7	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1284	2	35	2026-10-11 21:56:55.314383	CREATE	horariosucursal	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1285	2	18	2026-10-12 09:01:58.443735	UPDATE	cuentausuario	2	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1286	2	14	2026-10-12 10:22:34.972981	CREATE	empleado	76	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1287	2	17	2026-10-12 11:23:22.334194	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1288	2	17	2026-10-12 11:23:24.79493	UPDATE	cuentausuario	46	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1289	2	27	2026-10-12 18:37:05.172396	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1290	2	27	2026-10-12 18:37:09.270694	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1291	2	38	2026-10-12 18:49:47.22007	DELETE	horariosucursal	9	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1292	2	68	2026-10-12 20:55:40.141618	DELETE	caja	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1293	2	66	2026-10-12 20:55:46.926108	UPDATE	caja	3	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1294	2	27	2026-10-12 21:17:14.939144	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1295	2	27	2026-10-12 21:18:04.875059	UPDATE	sucursal	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
1296	2	93	2026-10-13 12:15:07.002648	CREATE	movimientoinventario	6	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0	127.0.0.1	EXITOSO
\.


--
-- Data for Name: caja; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.caja (id, nombre, activo, idsucursal) FROM stdin;
7	Caja2	t	7
6	Caja1	t	7
3	Caja principal	t	6
\.


--
-- Data for Name: categoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categoria (id, nombre, estado) FROM stdin;
1	HAMBURGUESAS	t
2	VARIADOS	t
3	PORCIONES	t
4	GASEOSAS	t
5	BEBIDA	t
6	ASADO	t
\.


--
-- Data for Name: cierrecaja; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cierrecaja (id, fechacierre, fondoinicial, totalventasefectivo, totalventasqr, totalventastarjeta, totalventas, totalingresos, totalegresos, efectivoesperado, efectivocontado, diferencia, observacion, idaperturacaja, idempleado) FROM stdin;
30	2026-09-19 14:09:31	50.00	196.00	0.00	0.00	196.00	70.00	0.00	316.00	190.00	-126.00	total en caja cuento con eso, pero me pagaron 6 bs por QR	34	45
79	2026-09-19 15:59:48	50.00	478.00	0.00	0.00	478.00	50.00	20.00	558.00	558.00	0.00	sin observacion	83	15
80	2026-09-19 21:28:35	50.00	261.00	0.00	0.00	261.00	50.00	20.00	341.00	420.00	79.00	\N	84	15
81	2026-09-20 01:03:58	0.00	187.00	0.00	0.00	187.00	100.00	30.00	257.00	250.00	-7.00	\N	85	39
82	2026-09-26 21:33:12	50.00	92.00	0.00	0.00	92.00	0.00	0.00	142.00	50.00	-92.00	Cierre provisional: la jornada a├║n est├í en curso.	86	15
\.


--
-- Data for Name: configuracion_sistema; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.configuracion_sistema (id, clave, valor, descripcion, estado) FROM stdin;
7	SITIO_HABILITAR_CARRUSEL	1	Muestra el carrusel de im├ígenes en el inicio	t
19	SITIO_MANTENIMIENTO_MENSAJE	Estamos realizando mejoras en la p├ígina. Volveremos muy pronto. Gracias por tu paciencia.	Mensaje que ver├ín los visitantes mientras el sitio est├í en mantenimiento	t
2	SITIO_ESLOGAN	Sabores que se quedan	Eslogan que aparece en el inicio	t
11	IMAGENES_CARRUSEL	[{"titulo":"Salchipapas","subtitulo":"Papas rusticas willi, salchicha viena Santa Fe ba├▒adas de salsa de la casa","imagen":"https:\\/\\/lh3.googleusercontent.com\\/gps-cs-s\\/AHRPTWlAe2Mgkh5jAsg_geFM6s1Dy9e1xzfCeLogvX4GhN4bHTpJGFgRrWHurshNy-4sj9iCOTaJwQ5jWn4kqplFBHKAJps1vh0tj8Bybhj0X7iFWFruaqlALVDT7iPuqssSPU8H50-7jA=s680-w680-h510-rw","boton_texto":"Salchipapas","boton_url":"https:\\/\\/lh3.googleusercontent.com\\/gps-cs-s\\/AHRPTWlAe2Mgkh5jAsg_geFM6s1Dy9e1xzfCeLogvX4GhN4bHTpJGFgRrWHurshNy-4sj9iCOTaJwQ5jWn4kqplFBHKAJps1vh0tj8Bybhj0X7iFWFruaqlALVDT7iPuqssSPU8H50-7jA=s680-w680-h510-rw","orden":1},{"titulo":"lomitos","subtitulo":"lomitos es el metodofd","imagen":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQ3WyIx8squ1a9hAjCFBx1X_fi75pX1PktFH0iJZL1ak6_XRtP7YSAH2RwTtLWjwu3tNLVK5pswmbNljKeuklDJRmszciOwavBG5ZYWncY&s=10","boton_texto":"lomitos","boton_url":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQ3WyIx8squ1a9hAjCFBx1X_fi75pX1PktFH0iJZL1ak6_XRtP7YSAH2RwTtLWjwu3tNLVK5pswmbNljKeuklDJRmszciOwavBG5ZYWncY&s=10","orden":2},{"titulo":"","subtitulo":"","imagen":"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcS7Gjf6iILZ5eUsVJZ4Q2FvjymhDUibOKWgQa7gZRZ9PA&s=10","boton_texto":"","boton_url":"","orden":3}]	Im├ígenes del carrusel del inicio (JSON)	t
9	SITIO_HABILITAR_PROMOCIONES	0	Muestra la secci├│n de promociones	t
3	SITIO_TELEFONO	75535749	Tel├⌐fono de contacto principal	t
4	SITIO_EMAIL	ventas@burguerwilly.com	Correo de contacto	t
5	SITIO_DIRECCION	Santa Cruz, La Guardia	Direcci├│n principal	t
1	SITIO_NOMBRE	≡ƒìö Burger Willy and Grill	Nombre comercial del sistema/negocio	t
8	SITIO_HABILITAR_MENU	1	Muestra la secci├│n de men├║/productos	t
10	SITIO_HABILITAR_SUCURSALES	0	Muestra la secci├│n de sucursales	t
6	SITIO_HORARIO	Lun - Dom: 17:00 p.m. - 10:30 p.m.	Horario de atenci├│n general	t
13	TEMA_COLOR_PRIMARIO	#b23a3a	Color principal (botones, barra de navegaci├│n)	t
14	TEMA_COLOR_PRIMARIO_OSCURO	#8f2d2d	Color principal oscuro (hover de botones)	t
15	TEMA_COLOR_FONDO	#fdf5f3	Color de fondo de la p├ígina	t
16	TEMA_COLOR_TEXTO	#331d1d	Color de texto	t
17	TEMA_COLOR_DETALLE	#e8a87c	Color de detalle (acentos, degradados)	t
18	SITIO_MANTENIMIENTO_ACTIVO	0	Activa o desactiva el modo mantenimiento de la p├ígina	t
\.


--
-- Data for Name: cuentausuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cuentausuario (id, idempleado, usuario, correo, passwordhash, intentosfallidos, bloqueado, bloqueadohasta, fechacreacion, ultimoacceso, remember_token) FROM stdin;
36	38	tatiana	tatiana@burguer.com	$2y$12$lHoGmrgj0eBlxXo3XU3h4uffu8/Uw3ubQafVTaB9Avjh7S/dLTcXu	0	t	\N	2026-09-14 02:14:44	2026-10-04 16:05:41	\N
40	40	ramon.nogales	ramon.nogales@gmail.com	$2y$12$FmqhIpKcE9LwSWgEmiiOkuAuWepYpz7ttg4UADhvZmTyy6AjoW2EK	0	f	\N	2026-09-14 12:02:48	2026-10-06 18:47:11	D2QGxSHVGGZGbPVNyoztWnxDxOpMp6dAOOVaeJPgeq5LUAlc8vP4TyG2Iop5
63	73	wilson.cruz	cruzwilson10@gmail.com	$2y$12$3x.IIW/jIH5TOpV9Zv3xiu3zASQtSHyrYfXh1wNGRQr66Ybzu4Jty	2	t	\N	2026-09-28 21:35:08	2026-09-29 13:26:56	\N
64	74	candy.crush	candy.crush@gmail.com	$2y$12$NgK.dYvSLlAL0IJR0oqSku.lQjiVZ/I6I6szkfOheZdW6sXK5p2mO	0	f	\N	2026-10-04 07:36:02	\N	\N
65	75	bruno.marsh	bruno@gmail.com	$2y$12$/O2WZJMAO728m3QwdYfjZez42HNecZG7uMfJ6NhrGSWAhZ9g3odau	0	f	\N	2026-10-04 15:58:13	\N	\N
66	76	juan.perez	juan.perez@gmail.com	$2y$12$8PecgUPUOfiIFftxF6u16ePNvfE1ABgCLZQ5eaCVnTEkS5o8P8cKi	0	f	\N	2026-10-12 14:22:34	2026-10-12 14:23:05	\N
46	46	andres.guzman	andres.guzman@gmail.com	$2y$12$6kONaY9gHHzWrrcIuv4G2uGiC.5o5xh6pk55pb2gWXRWMdrTALQXa	0	t	\N	2026-09-14 16:28:21	2026-09-15 13:26:44	\N
15	15	laura	laura@burger.com	$2y$12$bjXArvvqbJL8rI/RCIdWYus.9AOusbyLcIZ/R/RjPk.du9QuBAyrG	0	f	\N	2026-09-13 00:38:54	2026-10-13 05:26:39	\N
2	2	admin	cruznogalesmarcelo@gmail.com	$2y$12$trfVKxxeihP5fe2LIsS8gOpK.3SdAtJcbG1agl1wOUWX7up8n1hPS	0	f	\N	2026-09-12 23:29:22.975277	2026-10-13 15:32:19	2kLIUF9BvX6m48Ms7gMde0N61qaA0XpUpccMQIXq79eKLJHs5y7WfkIHkbW7
45	45	elena.nogales	elena.nogales@gmail.com	$2y$12$uB/3EtbhYetNbbckNB3J0.HuJaBO44U1JVQqPYFv585prHXHpVTJ.	0	f	\N	2026-09-14 16:16:21	2026-10-12 05:00:34	qGyKKwJBXw1XFA9Hudjdonh0mdy3lvhFcUsquShLcK9fR4tahXU1Ycfm60XV
41	41	wendy.follow	wendy.follow@gmail.com	$2y$12$3CPtzQJzbMvtHurgL/uT6uzKYfHw7FxK5ksch.Sa/iZER9yo/MNo.	1	f	\N	2026-09-14 15:29:08	2026-09-14 15:46:04	\N
37	39	juan.pinto	juan.pinto@gmail.com	$2y$12$.1QU.pEnPRbVevkJlW8ogOMtd6WKCdKdLGLzNE9.JpG54QhaZyw7G	0	f	\N	2026-09-14 02:26:07	2026-09-14 17:24:34	\N
\.


--
-- Data for Name: detallepedido; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detallepedido (id, cantidad, preciounitario, subtotal, descuento, total, idpedido, idproductosucursal, idpromocionsucursal) FROM stdin;
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
294	1	35.00	35.00	0.00	35.00	186	3	\N
295	1	32.00	32.00	0.00	32.00	186	4	\N
296	1	25.00	25.00	0.00	25.00	186	5	\N
137	1	32.00	32.00	0.00	32.00	96	4	\N
138	1	15.00	15.00	5.00	10.00	96	7	\N
139	1	8.00	8.00	0.00	8.00	97	8	\N
140	1	25.00	25.00	0.00	25.00	97	5	\N
141	1	23.00	23.00	0.00	23.00	97	6	\N
142	1	35.00	35.00	0.00	35.00	98	3	\N
143	1	32.00	32.00	0.00	32.00	98	4	\N
144	1	25.00	25.00	0.00	25.00	98	5	\N
\.


--
-- Data for Name: detallereceta; Type: TABLE DATA; Schema: public; Owner: postgres
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
-- Data for Name: empleado; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.empleado (id, nombre, apellido, telefono, estado, fecharegistro, idjefe, idrol, idsucursal) FROM stdin;
41	Wendy	Salazar	123456	t	2026-09-14 15:29:08	15	5	6
40	ramon	nogales	\N	t	2026-09-14 12:02:48	\N	3	7
39	Juan	Pinto	123456	t	2026-09-14 02:26:06	15	4	6
15	Laura	Castro	73130624	t	2026-09-13 00:38:54	\N	3	\N
73	Wilson	Cruz Nogales	75535749	f	2026-09-28 21:35:07	\N	5	\N
45	elena	nogales	+591 71022036	t	2026-09-14 16:16:21	2	3	6
74	candy	crush	\N	t	2026-10-04 07:36:01	\N	4	\N
55	GerentePrueba	PDF	00000000	f	2026-09-15 00:25:01	2	3	7
75	Bruno	Marsh	\N	t	2026-10-04 15:58:13	\N	3	\N
38	tatiana	guzman	\N	f	2026-09-14 02:14:44	40	4	7
2	Marcelo	Cruz Nogales	70005937	t	2026-09-12 23:29:22.512172	\N	2	\N
76	juan	perez	\N	t	2026-10-12 14:22:34	\N	2	\N
46	Andres	Guzman	\N	f	2026-09-14 16:28:21	15	5	6
\.


--
-- Data for Name: historialproducto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historialproducto (id, fecha_registro, precioanterior, precionuevo, idproductosucursal) FROM stdin;
1	2026-09-17 04:10:39	32.00	35.00	3
2	2026-09-17 13:34:48	26.00	28.00	10
\.


--
-- Data for Name: horariosucursal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.horariosucursal (idhorariosucursal, idsucursal, horaapertura, horacierre, activo, diasemana) FROM stdin;
7	6	16:00:00	22:30:00	t	3
6	6	16:00:00	22:30:00	t	2
5	6	16:00:00	22:30:00	t	1
8	7	10:30:00	22:30:00	t	7
\.


--
-- Data for Name: insumo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.insumo (id, nombre, descripcion, idunidadmedida, estado) FROM stdin;
3	Pan de hamburguesa	Pan brioche	4	t
5	Gaseosa 1L	Gaseosa de 1 litro	6	t
6	Gaseosa 2L	Gaseosa de 2 Litros	6	t
7	Gaseosa 1.5L	Gaseosa de 1.5 Litro	6	t
9	Chorizo Viena	Chorizo Viena para dogor	3	t
10	Cebolla	Cebolla de rodaja	7	t
11	Pepinillo	Rodaja de Pepinillo	7	t
12	Lechuga	Rodaja de Lechuga	7	t
13	Choclo	cuchara de choclo	3	t
14	Tomate	Rodaja de Tomate	7	t
8	Carne de Res	Carne de Res para Asado	3	t
2	Carne de Burguer	Carne molida	3	t
\.


--
-- Data for Name: inventariosucursal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.inventariosucursal (id, idsucursal, idinsumo, cantidad, cantidadminima, fechaactualizacion) FROM stdin;
5	6	6	52.000	5.000	2026-09-15 00:31:57
8	6	7	39.000	5.000	2026-09-15 00:59:16
10	7	5	20.000	50.000	2026-09-17 02:31:01
9	7	6	50.000	49.000	2026-09-17 02:31:16
11	6	12	200.000	30.000	2026-09-17 14:30:44
6	6	2	60000.000	100.000	2026-10-13 16:15:06
\.


--
-- Data for Name: mesa; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mesa (id, numeromesa, capacidad, estado, idsucursal) FROM stdin;
12	1	4	t	6
18	1	4	t	7
19	2	4	t	7
20	2	4	t	6
21	3	4	t	6
\.


--
-- Data for Name: metodopago; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.metodopago (id, nombre, activo, gateway) FROM stdin;
2	Efectivo	t	EFECTIVO
3	Tarjeta de cr├⌐dito	t	TARJETA
4	Transferencia bancaria	t	BANCO
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_09_13_000001_create_configuracion_sistema_table	1
2	2026_09_26_182052_create_password_reset_tokens_table	2
3	2026_10_04_052209_add_remember_token_to_cuentausuario_table	3
4	2026_10_05_043105_insertar_permiso_203_informe_sucursal	4
5	2026_10_05_054520_insertar_permisos_152_153_bitacoras	5
6	2026_10_05_060627_insertar_permiso_103_pdf_usuarios	6
7	2026_10_12_001421_reestructurar_tablas_promociones_paso_uno	7
8	2026_10_12_001422_crear_nuevo_esquema_promociones	8
9	2026_10_12_013548_eliminar_diaatencion_usar_diasemana_en_horariosucursal	9
10	2026_10_13_022110_insertar_permiso_349_pdf_bitacora_insumos_sucursal	10
\.


--
-- Data for Name: movimientocaja; Type: TABLE DATA; Schema: public; Owner: postgres
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
-- Data for Name: movimientoinventario; Type: TABLE DATA; Schema: public; Owner: postgres
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
23	11	ENTRADA	200.000	0.000	200.000	2026-09-17 14:30:45	15	Por bola de de lechuga serian 10 y se estan a├▒adiendo 20 bolas de lechuga siendo total en rogadas 200
24	6	ENTRADA	20000.000	40000.000	60000.000	2026-10-13 16:15:06	2	mas carne de burguer
\.


--
-- Data for Name: pago; Type: TABLE DATA; Schema: public; Owner: postgres
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
97	122	2	86	92.00	APROBADO	100.00	8.00	\N	EFECTIVO	\N	\N	\N	\N	2026-09-27 00:39:18	Pago en efectivo
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: pedido; Type: TABLE DATA; Schema: public; Owner: postgres
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
186	1	2026-09-26 21:51:55	92.00	EJECUTADO	MESA	6	15	12	92.00	0.00	\N	\N	86
172	1	2026-09-19 14:30:14	93.00	EJECUTADO	PARA_LLEVAR	6	15	\N	102.00	9.00	\N	\N	83
\.


--
-- Data for Name: permiso; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permiso (id, codigo, nombre, estado) FROM stdin;
38	225	Dar de baja a horario de atenci├│n de sucursal	t
94	347	Listar inventario y movimientos de sucursal	t
95	348	Establecer cantidad m├¡nima de inventario	t
83	445	Anular venta	t
84	446	Registrar m├⌐todo de pago	t
85	447	Dar de baja a m├⌐todo de pago	t
86	448	Listar m├⌐todos de pago	t
87	500	Crear pedido	t
88	501	Editar pedido	t
89	502	Listar pedidos	t
90	505	Dar de baja a pedido	t
91	506	Editar detalle de pedido	t
96	203	Crear informe de datos de sucursal	t
97	152	Crear PDF de bit├ícora de accesos	t
98	153	Crear PDF de bit├ícora de acciones	t
99	103	Crear PDF de informe de auditor├¡a de usuarios	t
100	349	Crear PDF de bit├ícora de insumos de sucursal	t
14	100	Crear nuevo empleado	t
15	101	Editar empleados	t
16	102	Listar empleados	t
17	105	Dar de baja a empleado	t
18	106	Modificar contrase├▒a de empleado	t
19	107	Listar empleados activos	t
20	108	Listar empleados inactivos	t
21	150	Listar bit├ícora de acceso de empleados	t
22	151	Listar bit├ícora de acciones de empleados	t
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
35	220	Registrar horario de atenci├│n de sucursal	t
36	221	Editar horario de atenci├│n de sucursal	t
37	222	Listar horario de atenci├│n de sucursal	t
39	300	Crear nueva categor├¡a	t
40	301	Editar categor├¡a	t
41	302	Listar categor├¡as	t
42	305	Dar de baja a categor├¡a	t
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
61	350	Crear promoci├│n en una sucursal	t
62	351	Editar promoci├│n de sucursal	t
63	352	Listar promociones	t
64	355	Dar de baja a promoci├│n de sucursal	t
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
92	50	Registrar o modificar configuraci├│n del sistema	t
\.


--
-- Data for Name: producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto (id, nombre, descripcion, photo, estado, idcategoria) FROM stdin;
1	TOXICA	Deliciosa carne premium 135 gr.,tocino ahumado, pico de gallo, nachos picantes, jalape├▒o, papas rusticas Wili, Pan brioche de pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	\N	t	1
7	CHIKILOCO	Deliciosa carne de pollo broaster, papas rusticas willi, tocino ahumado. pico de gallo, nachos, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	t	1
8	CHIKIWILLI	Deliciosa carne de pollo broaster, papas rusticas willy, Pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	t	1
10	EL TOXICO	Deliciosa carne premium, papas rusticas willi, tocino ahumado, mermelada de locoto, cebolla crispy, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	\N	t	1
15	Crispy king Wili	Pollo a la Broaster acompa├▒ado con arroz primavera y porcion de papas rusticas y porcion de papas rusticas willi con su deliciosa salsa de la casa	\N	t	2
16	Willi Tacos	Tortillas de maiz tostadas acompa├▒ada con carne de hamburguesa, queso muzarela, pico de gallo, choclitos, tiras de lechuga, papas rusticas y su salsa de la casa	\N	t	2
17	Willi Nachos	Nachos picantes acompa├▒adado con carne de hamburguesa, pico de gallo, choclito, queso muzzarela, limones en gajos y su salsa de la casa	\N	t	2
18	Pepito	Pan de oregano, carne picada de hamburguesa, salchicha viena, choclito, ba├▒ada de salsa y espolvoreada de queso rallado con papa rustica y su salsa	\N	t	2
19	Lomitos	Pan de oregano, carne lomito, lechuga, tomate, cebolla, papas rusticas wili y su salsa de la casa	\N	t	2
21	Salchipapas	Papas rusticas willi, salchicja Viena Santa Fe ba├▒adas de salsa de la casa y espolvoreada de queso rallado	\N	t	2
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
34	Pace├▒a	Bebida alcoholica Fria	\N	t	4
35	Peque	Gaseosa de 250ml Fria	\N	t	4
36	Simba	Gaseosa de 2L Fria	\N	t	4
37	Personal	Gaseosa de 500ml Fria	\N	t	4
38	Soda 1.5L	Gaseosa de 1.5L Fria	\N	t	4
40	Soda 2.5L	Gaseosa de 2.5L Fria	\N	t	4
41	Refresco vaso	bebida en vaso	\N	t	5
42	Refresco Jarra Mediana	bebida de jarra Mediana	\N	t	5
43	Refresco Jarra Grande	bebida de jarra grande	\N	t	5
11	ECO FULL	Deliciosa carne Premium 135gr, tocino ahumado, huevo frito, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/Komcso1OY1ktR2CsuAOvXBAwzcRXuxJNIuFlKzBa.jpg	t	1
20	Willi Nuggets	Tiritas de pollo willi rebosada acompa├▒ado con papas rusticas y su salsa de la casa	productos/CFU6ybGgc5q16QZWjfDGDakCG8fuAxPxpIbjKxBO.jpg	t	2
4	FULL	Deliciosa carne Premium 135gr, tocino ahumado, huevo frito, papas rusticas Willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/mx0zIgPjMkXCPCFLotDuXo8NpZoqqvPqIV5yLCfD.jpg	t	1
44	Entra├▒a	La entra├▒a es un corte de diafragma de la res, famoso por su intenso sabor y textura jugosa, ideal para quienes disfrutam un carne una carne con mucho caracter	\N	t	6
49	Bandera	Corte obtenido de la falta de la res, de fibras largas y gran sabor. Perfecto para quienes buscan una porcion abundante y muy jugosa	\N	t	6
14	Burrito	Tortillas de maiz tostadas acompa├▒ada con carne de hamburguesa, pollo o mixto, queso criollo, pico de gallo, chiclito, toras de lechuga, papas rusticas y su salsa de la casa	productos/kyNk0MH537PqnmFapU1tEQ7RXYxmcQFHlilnyAJZ.jpg	t	2
2	SUPER	Deliciosa carne premium 135 gr.,tocino ahumado, papas rusticas Willi,  Pan brioche de pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	productos/WLf1YeFX4zFAaeWpNn87zjQgs0Xvd4tApysGaVWP.jpg	t	1
39	Agua Vital	gaseosa vital	productos/x0ewBLe31NolZv05C9RegUCkw9xlYbrpQZKIMNAg.jpg	t	4
6	SIMPLE	Deliciosa carne Premium 135gr, papas rusticas willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/Vl4UFRAUXgSHTfBoO8TMqENTG68eE9ioMkxxT8ix.jpg	t	1
3	DOGOR	Deliciosa carne Premium 135gr, choclito salchicha Viena, queso extra, papas rusticas Willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/2f7eNefpfYyfikqfG26xLpbgOQoqwOYrxabHG9F3.jpg	t	1
13	ECO SIMPLE	Deliciosa carne Premium 135gr, pan tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/TawQ8CJ1O5T1jfbr4vdgUyZZk5bxlzrAchIAsH6S.jpg	t	1
12	ECO SUPER	Deliciosa carne premium 135 gr.,tocino,ahumado, pan tostado, tomate, lechuga, cebolla, pepinillo y su salsa de la casa	productos/UFja2NVYZFd87tdi8gInTr5IjZRWqGa7VVmpah5U.jpg	t	1
9	ECO DOGOR	Deliciosa carne Premium 135gr, choclito, salchica, viena, queso extra, pan tostado , tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/rdYodDuToltjkJqAdVLIEGdSJz6z5r2lbrZHTJW9.jpg	t	1
5	DOBLE	Deliciosa carne Premium 270gr, papas rusticas willi, pan brioche de papa tostado, tomate, lechuga, cebolla, pepinillos y su salsa de la casa	productos/wersXHK1zFQBAoq6pNUn3Bs7fM8kjKBkqmjrK1tn.jpg	t	1
50	Ribeye	Extra├¡do de la parte alta del lomo (costillar). Destaca por su marmoleo de grasa, que le aporta un textura extremadamente tierna y un sabor intenso	\N	f	6
\.


--
-- Data for Name: promocion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promocion (id, nombre, photo, activo) FROM stdin;
\.


--
-- Data for Name: promocion_sucursal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promocion_sucursal (id, idpromocion, idsucursal, tipodescuento, valordescuento, fechainicio, fechafin, activo) FROM stdin;
\.


--
-- Data for Name: promocion_sucursal_dia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promocion_sucursal_dia (idpromocionsucursal, diasemana) FROM stdin;
\.


--
-- Data for Name: promocion_sucursal_producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promocion_sucursal_producto (idpromocionsucursal, idsucursalproducto, idsucursal, cantidad_producto) FROM stdin;
\.


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rol (id, claverol, nombrerol) FROM stdin;
2	ADMINISTRADOR	Administrador
3	GERENTE	Gerente
4	CAJERO	Cajero
5	MESERO	Mesero
\.


--
-- Data for Name: rolpermiso; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rolpermiso (idrol, idpermiso) FROM stdin;
3	87
3	88
3	89
3	90
3	91
2	14
2	15
2	16
2	99
2	17
5	18
5	33
5	41
2	18
2	19
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
2	20
3	18
2	21
2	22
2	97
2	98
3	31
3	32
2	23
3	34
3	35
3	36
2	24
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
2	25
2	96
3	95
3	61
3	62
3	63
3	64
3	65
3	66
2	26
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
2	54
2	55
2	57
2	58
2	59
2	60
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
2	92
2	87
2	88
2	89
2	90
2	91
2	93
2	100
3	99
3	19
3	28
3	29
3	24
3	23
3	25
3	30
3	33
3	37
3	67
3	100
3	94
3	93
\.


--
-- Data for Name: sucursal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sucursal (id, nombre, direccion, telefono, latitud, longitud, estado, idempleadogerente) FROM stdin;
7	Sucursal km 9	km 9	57567	-17.88171900	-63.28588500	t	40
6	Sucursal Centro	Calle Ruben Arrdondo, Nro 9	75535749	-17.88923300	-63.32837100	t	15
\.


--
-- Data for Name: sucursal_producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sucursal_producto (id, idsucursal, idproducto, precioproducto, disponibilidad) FROM stdin;
3	6	5	35.00	t
4	6	3	32.00	t
5	6	9	25.00	t
6	6	11	23.00	t
7	6	13	15.00	t
8	6	39	8.00	t
9	6	12	18.00	t
10	6	10	28.00	t
\.


--
-- Data for Name: unidadmedida; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.unidadmedida (id, nombre, abreviatura, tipo, estado) FROM stdin;
3	Gramos	GR	PESO	t
4	Unidad	UND	UNIDAD	t
5	Mililitro	ML	VOLUMEN	t
6	Litro	L	VOLUMEN	t
7	Rodajas	ROD	UNIDAD	t
\.


--
-- Data for Name: venta; Type: TABLE DATA; Schema: public; Owner: postgres
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
122	2026-09-27 00:39:18	92.00	PAGADA	45	186
\.


--
-- Name: aperturacaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.aperturacaja_id_seq', 86, true);


--
-- Name: bitacoraacceso_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacoraacceso_id_seq', 697, true);


--
-- Name: bitacoraaccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacoraaccion_id_seq', 1296, true);


--
-- Name: caja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.caja_id_seq', 17, true);


--
-- Name: categoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categoria_id_seq', 10, true);


--
-- Name: cierrecaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cierrecaja_id_seq', 82, true);


--
-- Name: configuracion_sistema_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.configuracion_sistema_id_seq', 19, true);


--
-- Name: cuentausuario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cuentausuario_id_seq', 66, true);


--
-- Name: detallepedido_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detallepedido_id_seq', 296, true);


--
-- Name: detallereceta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detallereceta_id_seq', 31, true);


--
-- Name: empleado_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.empleado_id_seq', 76, true);


--
-- Name: historialproducto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.historialproducto_id_seq', 2, true);


--
-- Name: horariosucursal_idhorariosucursal_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.horariosucursal_idhorariosucursal_seq', 9, true);


--
-- Name: insumo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.insumo_id_seq', 14, true);


--
-- Name: inventariosucursal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.inventariosucursal_id_seq', 11, true);


--
-- Name: mesa_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mesa_id_seq', 21, true);


--
-- Name: metodopago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.metodopago_id_seq', 4, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 10, true);


--
-- Name: movimientocaja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.movimientocaja_id_seq', 128, true);


--
-- Name: movimientoinventario_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.movimientoinventario_id_seq', 24, true);


--
-- Name: pago_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pago_id_seq', 97, true);


--
-- Name: pedido_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pedido_id_seq', 186, true);


--
-- Name: permiso_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permiso_id_seq', 100, true);


--
-- Name: producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_id_seq', 50, true);


--
-- Name: promocion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promocion_id_seq', 1, false);


--
-- Name: promocion_sucursal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promocion_sucursal_id_seq', 1, false);


--
-- Name: rol_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rol_id_seq', 5, true);


--
-- Name: sucursal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sucursal_id_seq', 11, true);


--
-- Name: sucursal_producto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sucursal_producto_id_seq', 10, true);


--
-- Name: unidadmedida_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.unidadmedida_id_seq', 7, true);


--
-- Name: venta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.venta_id_seq', 122, true);


--
-- Name: aperturacaja aperturacaja_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_pkey PRIMARY KEY (id);


--
-- Name: bitacoraacceso bitacoraacceso_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoraacceso
    ADD CONSTRAINT bitacoraacceso_pkey PRIMARY KEY (id);


--
-- Name: bitacoraaccion bitacoraaccion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT bitacoraaccion_pkey PRIMARY KEY (id);


--
-- Name: caja caja_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.caja
    ADD CONSTRAINT caja_pkey PRIMARY KEY (id);


--
-- Name: categoria categoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_pkey PRIMARY KEY (id);


--
-- Name: cierrecaja cierrecaja_idaperturacaja_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idaperturacaja_key UNIQUE (idaperturacaja);


--
-- Name: cierrecaja cierrecaja_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_pkey PRIMARY KEY (id);


--
-- Name: configuracion_sistema configuracion_sistema_clave_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.configuracion_sistema
    ADD CONSTRAINT configuracion_sistema_clave_unique UNIQUE (clave);


--
-- Name: configuracion_sistema configuracion_sistema_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.configuracion_sistema
    ADD CONSTRAINT configuracion_sistema_pkey PRIMARY KEY (id);


--
-- Name: cuentausuario cuentausuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_correo_key UNIQUE (correo);


--
-- Name: cuentausuario cuentausuario_idempleado_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_idempleado_key UNIQUE (idempleado);


--
-- Name: cuentausuario cuentausuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_pkey PRIMARY KEY (id);


--
-- Name: cuentausuario cuentausuario_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_usuario_key UNIQUE (usuario);


--
-- Name: detallepedido detallepedido_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_pkey PRIMARY KEY (id);


--
-- Name: detallereceta detallereceta_idproducto_idinsumo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idproducto_idinsumo_key UNIQUE (idproducto, idinsumo);


--
-- Name: detallereceta detallereceta_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_pkey PRIMARY KEY (id);


--
-- Name: empleado empleado_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_pkey PRIMARY KEY (id);


--
-- Name: historialproducto historialproducto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historialproducto
    ADD CONSTRAINT historialproducto_pkey PRIMARY KEY (id);


--
-- Name: horariosucursal horariosucursal_idsucursal_diasemana_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_idsucursal_diasemana_key UNIQUE (idsucursal, diasemana);


--
-- Name: horariosucursal horariosucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_pkey PRIMARY KEY (idhorariosucursal);


--
-- Name: insumo insumo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.insumo
    ADD CONSTRAINT insumo_pkey PRIMARY KEY (id);


--
-- Name: inventariosucursal inventariosucursal_idsucursal_idinsumo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idsucursal_idinsumo_key UNIQUE (idsucursal, idinsumo);


--
-- Name: inventariosucursal inventariosucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_pkey PRIMARY KEY (id);


--
-- Name: mesa mesa_idsucursal_numeromesa_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_idsucursal_numeromesa_key UNIQUE (idsucursal, numeromesa);


--
-- Name: mesa mesa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_pkey PRIMARY KEY (id);


--
-- Name: metodopago metodopago_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodopago
    ADD CONSTRAINT metodopago_nombre_key UNIQUE (nombre);


--
-- Name: metodopago metodopago_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.metodopago
    ADD CONSTRAINT metodopago_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: movimientocaja movimientocaja_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_pkey PRIMARY KEY (id);


--
-- Name: movimientoinventario movimientoinventario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_pkey PRIMARY KEY (id);


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: pedido pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_pkey PRIMARY KEY (id);


--
-- Name: permiso permiso_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT permiso_codigo_key UNIQUE (codigo);


--
-- Name: permiso permiso_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT permiso_pkey PRIMARY KEY (id);


--
-- Name: producto producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_pkey PRIMARY KEY (id);


--
-- Name: promocion promocion_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion
    ADD CONSTRAINT promocion_pkey PRIMARY KEY (id);


--
-- Name: promocion_sucursal_dia promocion_sucursal_dia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal_dia
    ADD CONSTRAINT promocion_sucursal_dia_pkey PRIMARY KEY (idpromocionsucursal, diasemana);


--
-- Name: promocion_sucursal promocion_sucursal_id_idsucursal_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal
    ADD CONSTRAINT promocion_sucursal_id_idsucursal_key UNIQUE (id, idsucursal);


--
-- Name: promocion_sucursal promocion_sucursal_idpromocion_idsucursal_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal
    ADD CONSTRAINT promocion_sucursal_idpromocion_idsucursal_key UNIQUE (idpromocion, idsucursal);


--
-- Name: promocion_sucursal promocion_sucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal
    ADD CONSTRAINT promocion_sucursal_pkey PRIMARY KEY (id);


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_pkey PRIMARY KEY (idpromocionsucursal, idsucursalproducto);


--
-- Name: rol rol_claverol_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_claverol_key UNIQUE (claverol);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id);


--
-- Name: rolpermiso rolpermiso_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_pkey PRIMARY KEY (idrol, idpermiso);


--
-- Name: sucursal sucursal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal
    ADD CONSTRAINT sucursal_pkey PRIMARY KEY (id);


--
-- Name: sucursal_producto sucursal_producto_id_idsucursal_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_id_idsucursal_unique UNIQUE (id, idsucursal);


--
-- Name: sucursal_producto sucursal_producto_idsucursal_idproducto_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idsucursal_idproducto_key UNIQUE (idsucursal, idproducto);


--
-- Name: sucursal_producto sucursal_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_pkey PRIMARY KEY (id);


--
-- Name: unidadmedida unidadmedida_abreviatura_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.unidadmedida
    ADD CONSTRAINT unidadmedida_abreviatura_key UNIQUE (abreviatura);


--
-- Name: unidadmedida unidadmedida_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.unidadmedida
    ADD CONSTRAINT unidadmedida_pkey PRIMARY KEY (id);


--
-- Name: venta venta_idpedido_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idpedido_key UNIQUE (idpedido);


--
-- Name: venta venta_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_pkey PRIMARY KEY (id);


--
-- Name: cierrecaja trg_calcular_parametros_cierre; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_calcular_parametros_cierre BEFORE INSERT OR UPDATE ON public.cierrecaja FOR EACH ROW EXECUTE FUNCTION public.fn_calcular_parametros_cierre();


--
-- Name: aperturacaja trg_crear_cierre_inicial; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_crear_cierre_inicial AFTER INSERT ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION public.fn_crear_cierre_inicial();


--
-- Name: movimientocaja trg_refrescar_cierre_por_movimiento; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_refrescar_cierre_por_movimiento AFTER INSERT OR DELETE OR UPDATE ON public.movimientocaja FOR EACH ROW EXECUTE FUNCTION public.fn_refrescar_cierre_por_movimiento();


--
-- Name: pago trg_refrescar_cierre_por_pago; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_refrescar_cierre_por_pago AFTER INSERT OR DELETE OR UPDATE ON public.pago FOR EACH ROW EXECUTE FUNCTION public.fn_refrescar_cierre_por_pago();


--
-- Name: aperturacaja trg_validar_apertura_caja; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_validar_apertura_caja BEFORE INSERT OR UPDATE ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION public.fn_validar_apertura_caja();


--
-- Name: aperturacaja aperturacaja_idcaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_idcaja_fkey FOREIGN KEY (idcaja) REFERENCES public.caja(id);


--
-- Name: aperturacaja aperturacaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aperturacaja
    ADD CONSTRAINT aperturacaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: caja caja_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.caja
    ADD CONSTRAINT caja_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: cierrecaja cierrecaja_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: cierrecaja cierrecaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cierrecaja
    ADD CONSTRAINT cierrecaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: cuentausuario cuentausuario_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cuentausuario
    ADD CONSTRAINT cuentausuario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: detallepedido detallepedido_idpedido_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES public.pedido(id);


--
-- Name: detallepedido detallepedido_idproductosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES public.sucursal_producto(id);


--
-- Name: detallepedido detallepedido_idpromocionsucursal_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallepedido
    ADD CONSTRAINT detallepedido_idpromocionsucursal_foreign FOREIGN KEY (idpromocionsucursal) REFERENCES public.promocion_sucursal(id);


--
-- Name: detallereceta detallereceta_idinsumo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES public.insumo(id);


--
-- Name: detallereceta detallereceta_idproducto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detallereceta
    ADD CONSTRAINT detallereceta_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES public.producto(id);


--
-- Name: empleado empleado_idjefe_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_idjefe_fkey FOREIGN KEY (idjefe) REFERENCES public.empleado(id);


--
-- Name: empleado empleado_idrol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_idrol_fkey FOREIGN KEY (idrol) REFERENCES public.rol(id);


--
-- Name: empleado empleado_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.empleado
    ADD CONSTRAINT empleado_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: bitacoraacceso fk_bitacora_acceso_empleado; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoraacceso
    ADD CONSTRAINT fk_bitacora_acceso_empleado FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: bitacoraaccion fk_bitacora_accion_empleado; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT fk_bitacora_accion_empleado FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: bitacoraaccion fk_bitacora_accion_permiso; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoraaccion
    ADD CONSTRAINT fk_bitacora_accion_permiso FOREIGN KEY (idpermiso) REFERENCES public.permiso(id);


--
-- Name: sucursal fk_sucursal_empleado; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal
    ADD CONSTRAINT fk_sucursal_empleado FOREIGN KEY (idempleadogerente) REFERENCES public.empleado(id);


--
-- Name: historialproducto historialproducto_idproductosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historialproducto
    ADD CONSTRAINT historialproducto_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES public.sucursal_producto(id);


--
-- Name: horariosucursal horariosucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.horariosucursal
    ADD CONSTRAINT horariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: insumo insumo_idunidadmedida_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.insumo
    ADD CONSTRAINT insumo_idunidadmedida_fkey FOREIGN KEY (idunidadmedida) REFERENCES public.unidadmedida(id);


--
-- Name: inventariosucursal inventariosucursal_idinsumo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES public.insumo(id);


--
-- Name: inventariosucursal inventariosucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventariosucursal
    ADD CONSTRAINT inventariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: mesa mesa_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mesa
    ADD CONSTRAINT mesa_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: movimientocaja movimientocaja_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: movimientocaja movimientocaja_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientocaja
    ADD CONSTRAINT movimientocaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: movimientoinventario movimientoinventario_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: movimientoinventario movimientoinventario_idinventariosucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.movimientoinventario
    ADD CONSTRAINT movimientoinventario_idinventariosucursal_fkey FOREIGN KEY (idinventariosucursal) REFERENCES public.inventariosucursal(id);


--
-- Name: pago pago_idaperturacaja_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: pago pago_idmetodopago_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idmetodopago_fkey FOREIGN KEY (idmetodopago) REFERENCES public.metodopago(id);


--
-- Name: pago pago_idventa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_idventa_fkey FOREIGN KEY (idventa) REFERENCES public.venta(id);


--
-- Name: pedido pedido_idaperturacaja_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idaperturacaja_foreign FOREIGN KEY (idaperturacaja) REFERENCES public.aperturacaja(id);


--
-- Name: pedido pedido_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: pedido pedido_idmesa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idmesa_fkey FOREIGN KEY (idmesa) REFERENCES public.mesa(id);


--
-- Name: pedido pedido_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pedido
    ADD CONSTRAINT pedido_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: producto producto_idcategoria_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_idcategoria_fkey FOREIGN KEY (idcategoria) REFERENCES public.categoria(id);


--
-- Name: promocion_sucursal_dia promocion_sucursal_dia_idpromocionsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal_dia
    ADD CONSTRAINT promocion_sucursal_dia_idpromocionsucursal_fkey FOREIGN KEY (idpromocionsucursal) REFERENCES public.promocion_sucursal(id) ON DELETE CASCADE;


--
-- Name: promocion_sucursal promocion_sucursal_idpromocion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal
    ADD CONSTRAINT promocion_sucursal_idpromocion_fkey FOREIGN KEY (idpromocion) REFERENCES public.promocion(id) ON DELETE CASCADE;


--
-- Name: promocion_sucursal promocion_sucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal
    ADD CONSTRAINT promocion_sucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id) ON DELETE CASCADE;


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_idpromocionsucursal_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_idpromocionsucursal_idsucursal_fkey FOREIGN KEY (idpromocionsucursal, idsucursal) REFERENCES public.promocion_sucursal(id, idsucursal);


--
-- Name: promocion_sucursal_producto promocion_sucursal_producto_idsucursalproducto_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promocion_sucursal_producto
    ADD CONSTRAINT promocion_sucursal_producto_idsucursalproducto_idsucursal_fkey FOREIGN KEY (idsucursalproducto, idsucursal) REFERENCES public.sucursal_producto(id, idsucursal);


--
-- Name: rolpermiso rolpermiso_idpermiso_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_idpermiso_fkey FOREIGN KEY (idpermiso) REFERENCES public.permiso(id);


--
-- Name: rolpermiso rolpermiso_idrol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rolpermiso
    ADD CONSTRAINT rolpermiso_idrol_fkey FOREIGN KEY (idrol) REFERENCES public.rol(id);


--
-- Name: sucursal_producto sucursal_producto_idproducto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES public.producto(id);


--
-- Name: sucursal_producto sucursal_producto_idsucursal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sucursal_producto
    ADD CONSTRAINT sucursal_producto_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES public.sucursal(id);


--
-- Name: venta venta_idempleado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES public.empleado(id);


--
-- Name: venta venta_idpedido_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.venta
    ADD CONSTRAINT venta_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES public.pedido(id);


--
-- PostgreSQL database dump complete
--

\unrestrict HPxushSTt5pd34rJwJwAVxOYXMiDmACmWlJeOniDgglx0rQpGD1EWx742KNBRks

