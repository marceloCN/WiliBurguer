CREATE TYPE tipo_pedido AS ENUM ('MESA', 'PARA_LLEVAR');
CREATE TYPE tipodescuento AS ENUM ('PORCENTAJE', 'DESCUENTO_MONTO', 'PRECIO_PROMOCIONAL');
CREATE TYPE tipoestado_pedido AS ENUM ('PENDIENTE', 'ANULADO', 'EJECUTADO');
CREATE TYPE tipoestadocaja AS ENUM ('ABIERTA', 'CERRADA');
CREATE TYPE tipoestadopago AS ENUM ('PENDIENTE', 'APROBADO', 'RECHAZADO', 'ANULADO');
CREATE TYPE tipoestadoventa AS ENUM ('PENDIENTE', 'PAGADA', 'RECHAZADO', 'ANULADO');
CREATE TYPE tipomovimientocaja AS ENUM ('INGRESO', 'EGRESO');
CREATE TYPE tipomovimientoinventario AS ENUM ('ENTRADA', 'SALIDA', 'AJUSTE');

CREATE SEQUENCE configuracion_sistema_id_seq AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
CREATE SEQUENCE migrations_id_seq AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE TABLE aperturacaja (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    fechaapertura TIMESTAMP NOT NULL,
    montoinicial NUMERIC(10,2) NOT NULL,
    estado tipoestadocaja NOT NULL DEFAULT 'ABIERTA'::tipoestadocaja,
    idcaja INT NOT NULL,
    idempleado INT NOT NULL,
    PRIMARY KEY (id),
    CHECK ((montoinicial >= (0)::numeric))
);

CREATE TABLE bitacoraacceso (
    id BIGINT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idempleado INT,
    fechahora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipoacceso VARCHAR(20) NOT NULL,
    equipo VARCHAR(100),
    ip VARCHAR(45),
    resultado VARCHAR(20) NOT NULL,
    detalle VARCHAR(255),
    PRIMARY KEY (id),
    CHECK (((tipoacceso)::text = ANY ((ARRAY['LOGIN'::character varying, 'LOGOUT'::character varying])::text[]))),
    CHECK (((resultado)::text = ANY ((ARRAY['EXITOSO'::character varying, 'FALLIDO'::character varying])::text[])))
);

CREATE TABLE bitacoraaccion (
    id BIGINT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idempleado INT NOT NULL,
    idpermiso INT NOT NULL,
    fechahora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accion VARCHAR(10) NOT NULL,
    tablaafectada VARCHAR(100),
    idregistroafectado BIGINT,
    equipo VARCHAR(150),
    ip VARCHAR(45),
    resultado VARCHAR(255) NOT NULL DEFAULT 'EXITOSO'::character varying,
    PRIMARY KEY (id)
);

CREATE TABLE caja (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT true,
    idsucursal INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE categoria (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id)
);

CREATE TABLE cierrecaja (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    fechacierre TIMESTAMP NOT NULL,
    fondoinicial NUMERIC(10,2) NOT NULL,
    totalventasefectivo NUMERIC(10,2) NOT NULL DEFAULT 0,
    totalventasqr NUMERIC(10,2) NOT NULL DEFAULT 0,
    totalventastarjeta NUMERIC(10,2) NOT NULL DEFAULT 0,
    totalventas NUMERIC(10,2) NOT NULL DEFAULT 0,
    totalingresos NUMERIC(10,2) NOT NULL DEFAULT 0,
    totalegresos NUMERIC(10,2) NOT NULL DEFAULT 0,
    efectivoesperado NUMERIC(10,2) NOT NULL,
    efectivocontado NUMERIC(10,2) NOT NULL,
    diferencia NUMERIC(10,2) NOT NULL,
    observacion VARCHAR(250),
    idaperturacaja INT NOT NULL,
    idempleado INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (idaperturacaja)
);

CREATE TABLE configuracion_sistema (
    id BIGINT NOT NULL DEFAULT nextval('configuracion_sistema_id_seq'::regclass),
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    descripcion VARCHAR(255),
    estado BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id),
    UNIQUE (clave)
);

CREATE TABLE cuentausuario (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idempleado INT NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    passwordhash VARCHAR(255),
    intentosfallidos INT NOT NULL DEFAULT 0,
    bloqueado BOOLEAN NOT NULL DEFAULT false,
    bloqueadohasta TIMESTAMP,
    fechacreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimoacceso TIMESTAMP,
    remember_token VARCHAR(100),
    PRIMARY KEY (id),
    UNIQUE (idempleado),
    UNIQUE (usuario),
    UNIQUE (correo)
);

CREATE TABLE detallepedido (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    cantidad INT NOT NULL,
    preciounitario NUMERIC(10,2) NOT NULL,
    subtotal NUMERIC(10,2) NOT NULL,
    descuento NUMERIC(10,2) NOT NULL DEFAULT 0,
    total NUMERIC(10,2) NOT NULL,
    idpedido INT NOT NULL,
    idproductosucursal INT,
    idpromocionsucursal INT,
    PRIMARY KEY (id),
    CHECK ((preciounitario >= (0)::numeric)),
    CHECK ((subtotal >= (0)::numeric)),
    CHECK ((descuento >= (0)::numeric)),
    CHECK ((total >= (0)::numeric)),
    CHECK ((cantidad > 0)),
    CHECK (((idproductosucursal IS NOT NULL) OR (idpromocionsucursal IS NOT NULL)))
);

CREATE TABLE detallereceta (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idproducto INT NOT NULL,
    idinsumo INT NOT NULL,
    cantidad NUMERIC(12,3) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (idproducto, idinsumo),
    CHECK ((cantidad > (0)::numeric))
);

CREATE TABLE empleado (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(15),
    estado BOOLEAN NOT NULL DEFAULT true,
    fecharegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idjefe INT,
    idrol INT NOT NULL,
    idsucursal INT,
    PRIMARY KEY (id)
);

CREATE TABLE historialproducto (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    fecha_registro TIMESTAMP NOT NULL,
    precioanterior NUMERIC(10,2) NOT NULL,
    precionuevo NUMERIC(10,2) NOT NULL,
    idproductosucursal INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE horariosucursal (
    idhorariosucursal INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idsucursal INT NOT NULL,
    horaapertura TIME NOT NULL,
    horacierre TIME NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT true,
    diasemana SMALLINT NOT NULL,
    PRIMARY KEY (idhorariosucursal),
    UNIQUE (idsucursal, diasemana),
    CHECK (((diasemana >= 1) AND (diasemana <= 7)))
);

CREATE TABLE insumo (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    idunidadmedida INT NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id)
);

CREATE TABLE inventariosucursal (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idsucursal INT NOT NULL,
    idinsumo INT NOT NULL,
    cantidad NUMERIC(12,3) NOT NULL DEFAULT 0,
    cantidadminima NUMERIC(12,3),
    fechaactualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (idsucursal, idinsumo),
    CHECK ((cantidad >= (0)::numeric)),
    CHECK (((cantidadminima IS NULL) OR (cantidadminima >= (0)::numeric)))
);

CREATE TABLE mesa (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    numeromesa INT NOT NULL,
    capacidad INT NOT NULL DEFAULT 4,
    estado BOOLEAN NOT NULL DEFAULT true,
    idsucursal INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (idsucursal, numeromesa)
);

CREATE TABLE metodopago (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(50) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT true,
    gateway VARCHAR(50),
    PRIMARY KEY (id),
    UNIQUE (nombre)
);

CREATE TABLE migrations (
    id INT NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE movimientocaja (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    fechamovimiento TIMESTAMP NOT NULL,
    tipomovimiento tipomovimientocaja NOT NULL,
    concepto VARCHAR(150) NOT NULL,
    monto NUMERIC(10,2) NOT NULL,
    observacion VARCHAR(250),
    idaperturacaja INT NOT NULL,
    idempleado INT NOT NULL,
    PRIMARY KEY (id),
    CHECK ((monto > (0)::numeric))
);

CREATE TABLE movimientoinventario (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idinventariosucursal INT NOT NULL,
    tipomovimiento tipomovimientoinventario NOT NULL,
    cantidad NUMERIC(12,3) NOT NULL,
    cantidadanterior NUMERIC(12,3) NOT NULL,
    cantidadnueva NUMERIC(12,3) NOT NULL,
    fechamovimiento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idempleado INT,
    observacion VARCHAR(255),
    PRIMARY KEY (id),
    CHECK ((cantidad > (0)::numeric))
);

CREATE TABLE pago (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idventa INT NOT NULL,
    idmetodopago INT NOT NULL,
    idaperturacaja INT NOT NULL,
    montopago NUMERIC(10,2) NOT NULL,
    estadopago tipoestadopago NOT NULL DEFAULT 'PENDIENTE'::tipoestadopago,
    montorecibido NUMERIC(10,2),
    cambioentregado NUMERIC(10,2) NOT NULL DEFAULT 0,
    referenciaexterna VARCHAR(200),
    gateway VARCHAR(50),
    codigoautorizacion VARCHAR(100),
    idtransaccion VARCHAR(200),
    ultimos4 VARCHAR(4),
    marcatarjeta VARCHAR(30),
    fechapago TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacionpago VARCHAR(200),
    PRIMARY KEY (id)
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    PRIMARY KEY (email)
);

CREATE TABLE pedido (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nropedido INT NOT NULL,
    fecha TIMESTAMP NOT NULL,
    total NUMERIC(10,2) NOT NULL,
    estadopedido tipoestado_pedido NOT NULL DEFAULT 'PENDIENTE'::tipoestado_pedido,
    tipopedido tipo_pedido NOT NULL DEFAULT 'MESA'::tipo_pedido,
    idsucursal INT NOT NULL,
    idempleado INT NOT NULL,
    idmesa INT,
    subtotalpedido NUMERIC(10,2) NOT NULL,
    descuentopedido NUMERIC(10,2) NOT NULL DEFAULT 0,
    horarecogida TIMESTAMP,
    observaciones TEXT,
    idaperturacaja INT,
    PRIMARY KEY (id),
    CHECK ((descuentopedido >= (0)::numeric)),
    CHECK ((total >= (0)::numeric)),
    CHECK ((subtotalpedido >= (0)::numeric))
);

CREATE TABLE permiso (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    codigo VARCHAR(100) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id),
    UNIQUE (codigo)
);

CREATE TABLE producto (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    photo VARCHAR(255),
    estado BOOLEAN NOT NULL DEFAULT true,
    idcategoria INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE promocion (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id)
);

CREATE TABLE promocion_sucursal (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idpromocion INT NOT NULL,
    idsucursal INT NOT NULL,
    tipodescuento tipodescuento NOT NULL,
    valordescuento NUMERIC(10,2) NOT NULL,
    fechainicio TIMESTAMP NOT NULL,
    fechafin TIMESTAMP NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id),
    UNIQUE (idpromocion, idsucursal),
    UNIQUE (id, idsucursal),
    CHECK ((valordescuento >= (0)::numeric)),
    CHECK (((tipodescuento <> 'PORCENTAJE'::tipodescuento) OR (valordescuento <= (100)::numeric))),
    CHECK ((fechafin > fechainicio))
);

CREATE TABLE promocion_sucursal_dia (
    idpromocionsucursal INT NOT NULL,
    diasemana SMALLINT NOT NULL,
    PRIMARY KEY (idpromocionsucursal, diasemana),
    CHECK (((diasemana >= 1) AND (diasemana <= 7)))
);

CREATE TABLE promocion_sucursal_producto (
    idpromocionsucursal INT NOT NULL,
    idsucursalproducto INT NOT NULL,
    idsucursal INT NOT NULL,
    cantidad_producto INT NOT NULL DEFAULT 1,
    PRIMARY KEY (idpromocionsucursal, idsucursalproducto),
    CHECK ((cantidad_producto > 0))
);

CREATE TABLE rol (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    claverol VARCHAR(50) NOT NULL,
    nombrerol VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (claverol)
);

CREATE TABLE rolpermiso (
    idrol INT NOT NULL,
    idpermiso INT NOT NULL,
    PRIMARY KEY (idrol, idpermiso)
);

CREATE TABLE sucursal (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    latitud NUMERIC(10,8) NOT NULL,
    longitud NUMERIC(11,8) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT true,
    idempleadogerente INT,
    PRIMARY KEY (id)
);

CREATE TABLE sucursal_producto (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    idsucursal INT NOT NULL,
    idproducto INT NOT NULL,
    precioproducto NUMERIC(10,2) NOT NULL,
    disponibilidad BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id),
    UNIQUE (idsucursal, idproducto),
    UNIQUE (id, idsucursal),
    CHECK ((precioproducto >= (0)::numeric))
);

CREATE TABLE unidadmedida (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    nombre VARCHAR(50) NOT NULL,
    abreviatura VARCHAR(10) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT true,
    PRIMARY KEY (id),
    UNIQUE (abreviatura)
);

CREATE TABLE venta (
    id INT NOT NULL GENERATED ALWAYS AS IDENTITY,
    fechaventa TIMESTAMP NOT NULL,
    totalventa NUMERIC(10,2) NOT NULL,
    estadoventa tipoestadoventa NOT NULL DEFAULT 'PENDIENTE'::tipoestadoventa,
    idempleado INT NOT NULL,
    idpedido INT,
    PRIMARY KEY (id),
    UNIQUE (idpedido)
);

ALTER TABLE aperturacaja ADD CONSTRAINT aperturacaja_idcaja_fkey FOREIGN KEY (idcaja) REFERENCES caja(id);
ALTER TABLE aperturacaja ADD CONSTRAINT aperturacaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE bitacoraacceso ADD CONSTRAINT fk_bitacora_acceso_empleado FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE bitacoraaccion ADD CONSTRAINT fk_bitacora_accion_empleado FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE bitacoraaccion ADD CONSTRAINT fk_bitacora_accion_permiso FOREIGN KEY (idpermiso) REFERENCES permiso(id);
ALTER TABLE caja ADD CONSTRAINT caja_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE cierrecaja ADD CONSTRAINT cierrecaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES aperturacaja(id);
ALTER TABLE cierrecaja ADD CONSTRAINT cierrecaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE cuentausuario ADD CONSTRAINT cuentausuario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE detallepedido ADD CONSTRAINT detallepedido_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES pedido(id);
ALTER TABLE detallepedido ADD CONSTRAINT detallepedido_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES sucursal_producto(id);
ALTER TABLE detallepedido ADD CONSTRAINT detallepedido_idpromocionsucursal_foreign FOREIGN KEY (idpromocionsucursal) REFERENCES promocion_sucursal(id);
ALTER TABLE detallereceta ADD CONSTRAINT detallereceta_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES insumo(id);
ALTER TABLE detallereceta ADD CONSTRAINT detallereceta_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES producto(id);
ALTER TABLE empleado ADD CONSTRAINT empleado_idjefe_fkey FOREIGN KEY (idjefe) REFERENCES empleado(id);
ALTER TABLE empleado ADD CONSTRAINT empleado_idrol_fkey FOREIGN KEY (idrol) REFERENCES rol(id);
ALTER TABLE empleado ADD CONSTRAINT empleado_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE historialproducto ADD CONSTRAINT historialproducto_idproductosucursal_fkey FOREIGN KEY (idproductosucursal) REFERENCES sucursal_producto(id);
ALTER TABLE horariosucursal ADD CONSTRAINT horariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE insumo ADD CONSTRAINT insumo_idunidadmedida_fkey FOREIGN KEY (idunidadmedida) REFERENCES unidadmedida(id);
ALTER TABLE inventariosucursal ADD CONSTRAINT inventariosucursal_idinsumo_fkey FOREIGN KEY (idinsumo) REFERENCES insumo(id);
ALTER TABLE inventariosucursal ADD CONSTRAINT inventariosucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE mesa ADD CONSTRAINT mesa_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE movimientocaja ADD CONSTRAINT movimientocaja_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES aperturacaja(id);
ALTER TABLE movimientocaja ADD CONSTRAINT movimientocaja_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE movimientoinventario ADD CONSTRAINT movimientoinventario_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE movimientoinventario ADD CONSTRAINT movimientoinventario_idinventariosucursal_fkey FOREIGN KEY (idinventariosucursal) REFERENCES inventariosucursal(id);
ALTER TABLE pago ADD CONSTRAINT pago_idaperturacaja_fkey FOREIGN KEY (idaperturacaja) REFERENCES aperturacaja(id);
ALTER TABLE pago ADD CONSTRAINT pago_idmetodopago_fkey FOREIGN KEY (idmetodopago) REFERENCES metodopago(id);
ALTER TABLE pago ADD CONSTRAINT pago_idventa_fkey FOREIGN KEY (idventa) REFERENCES venta(id);
ALTER TABLE pedido ADD CONSTRAINT pedido_idaperturacaja_foreign FOREIGN KEY (idaperturacaja) REFERENCES aperturacaja(id);
ALTER TABLE pedido ADD CONSTRAINT pedido_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE pedido ADD CONSTRAINT pedido_idmesa_fkey FOREIGN KEY (idmesa) REFERENCES mesa(id);
ALTER TABLE pedido ADD CONSTRAINT pedido_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE producto ADD CONSTRAINT producto_idcategoria_fkey FOREIGN KEY (idcategoria) REFERENCES categoria(id);
ALTER TABLE promocion_sucursal ADD CONSTRAINT promocion_sucursal_idpromocion_fkey FOREIGN KEY (idpromocion) REFERENCES promocion(id) ON DELETE CASCADE;
ALTER TABLE promocion_sucursal ADD CONSTRAINT promocion_sucursal_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id) ON DELETE CASCADE;
ALTER TABLE promocion_sucursal_dia ADD CONSTRAINT promocion_sucursal_dia_idpromocionsucursal_fkey FOREIGN KEY (idpromocionsucursal) REFERENCES promocion_sucursal(id) ON DELETE CASCADE;
ALTER TABLE promocion_sucursal_producto ADD CONSTRAINT promocion_sucursal_producto_idpromocionsucursal_idsucursal_fkey FOREIGN KEY (idpromocionsucursal, idsucursal) REFERENCES promocion_sucursal(id, idsucursal);
ALTER TABLE promocion_sucursal_producto ADD CONSTRAINT promocion_sucursal_producto_idsucursalproducto_idsucursal_fkey FOREIGN KEY (idsucursalproducto, idsucursal) REFERENCES sucursal_producto(id, idsucursal);
ALTER TABLE rolpermiso ADD CONSTRAINT rolpermiso_idpermiso_fkey FOREIGN KEY (idpermiso) REFERENCES permiso(id);
ALTER TABLE rolpermiso ADD CONSTRAINT rolpermiso_idrol_fkey FOREIGN KEY (idrol) REFERENCES rol(id);
ALTER TABLE sucursal ADD CONSTRAINT fk_sucursal_empleado FOREIGN KEY (idempleadogerente) REFERENCES empleado(id);
ALTER TABLE sucursal_producto ADD CONSTRAINT sucursal_producto_idproducto_fkey FOREIGN KEY (idproducto) REFERENCES producto(id);
ALTER TABLE sucursal_producto ADD CONSTRAINT sucursal_producto_idsucursal_fkey FOREIGN KEY (idsucursal) REFERENCES sucursal(id);
ALTER TABLE venta ADD CONSTRAINT venta_idempleado_fkey FOREIGN KEY (idempleado) REFERENCES empleado(id);
ALTER TABLE venta ADD CONSTRAINT venta_idpedido_fkey FOREIGN KEY (idpedido) REFERENCES pedido(id);


CREATE OR REPLACE FUNCTION public.actualizar_caja(p_id integer, p_idsucursal integer, p_nombre character varying, p_activo boolean)
 RETURNS void
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE OR REPLACE PROCEDURE public.actualizar_mesa(IN p_id integer, IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE PROCEDURE public.actualizar_perfil(IN p_id_empleado integer, IN p_nombre character varying, IN p_apellido character varying, IN p_telefono character varying, IN p_usuario character varying, IN p_correo character varying, IN p_password_hash character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE PROCEDURE public.dar_baja_caja(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE PROCEDURE public.dar_baja_mesa(IN p_id integer, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE FUNCTION public.fn_calcular_parametros_cierre()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE OR REPLACE FUNCTION public.fn_crear_cierre_inicial()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE OR REPLACE FUNCTION public.fn_refrescar_cierre_por_movimiento()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN
    IF TG_OP = 'DELETE' THEN
        UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = OLD.idaperturacaja;
        RETURN OLD;
    END IF;
    UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = NEW.idaperturacaja;
    RETURN NEW;
END;
$function$
;

CREATE OR REPLACE FUNCTION public.fn_refrescar_cierre_por_pago()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN
    IF TG_OP = 'DELETE' THEN
        UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = OLD.idaperturacaja;
        RETURN OLD;
    END IF;
    UPDATE cierrecaja SET observacion = observacion WHERE idaperturacaja = NEW.idaperturacaja;
    RETURN NEW;
END;
$function$
;

CREATE OR REPLACE FUNCTION public.fn_validar_apertura_caja()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE OR REPLACE PROCEDURE public.reasignar_subordinados(IN p_jefe_anterior integer, IN p_jefe_nuevo integer, IN p_idsucursal integer)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE PROCEDURE public.registrar_acceso(IN p_id_empleado integer, IN p_tipo_acceso character varying, IN p_equipo character varying, IN p_ip character varying, IN p_resultado character varying, IN p_detalle character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE PROCEDURE public.registrar_accion(IN p_id_empleado integer, IN p_accion character varying, IN p_tabla_afectada character varying, IN p_id_registro_afectado bigint, IN p_codigo_permiso character varying, IN p_resultado character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE FUNCTION public.registrar_caja(p_idsucursal integer, p_nombre character varying, p_activo boolean)
 RETURNS integer
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE OR REPLACE PROCEDURE public.registrar_mesa(IN p_idsucursal integer, IN p_numeromesa integer, IN p_capacidad integer, IN p_estado boolean, IN p_id_empleado integer, IN p_codigo_permiso character varying, IN p_equipo character varying, IN p_ip character varying)
 LANGUAGE plpgsql
AS $procedure$
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
$procedure$
;

CREATE OR REPLACE FUNCTION public.verificar_bloqueo_y_desbloquear(p_id_cuenta integer)
 RETURNS boolean
 LANGUAGE plpgsql
AS $function$
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
$function$
;

CREATE TRIGGER trg_crear_cierre_inicial AFTER INSERT ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION fn_crear_cierre_inicial();

CREATE TRIGGER trg_validar_apertura_caja BEFORE INSERT OR UPDATE ON public.aperturacaja FOR EACH ROW EXECUTE FUNCTION fn_validar_apertura_caja();

CREATE TRIGGER trg_calcular_parametros_cierre BEFORE INSERT OR UPDATE ON public.cierrecaja FOR EACH ROW EXECUTE FUNCTION fn_calcular_parametros_cierre();

CREATE TRIGGER trg_refrescar_cierre_por_movimiento AFTER INSERT OR DELETE OR UPDATE ON public.movimientocaja FOR EACH ROW EXECUTE FUNCTION fn_refrescar_cierre_por_movimiento();

CREATE TRIGGER trg_refrescar_cierre_por_pago AFTER INSERT OR DELETE OR UPDATE ON public.pago FOR EACH ROW EXECUTE FUNCTION fn_refrescar_cierre_por_pago();
