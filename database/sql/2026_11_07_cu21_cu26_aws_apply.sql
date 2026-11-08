-- Propuesta exacta AWS: requiere confirmacion del usuario antes de ejecutar.
BEGIN;
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '60s';
DO $$
BEGIN
    IF current_database() <> 'Parcial2' THEN
        RAISE EXCEPTION 'La base esperada es Parcial2';
    END IF;
END $$;
LOCK TABLE vida_salud.migrations IN SHARE ROW EXCLUSIVE MODE;
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM vida_salud.migrations
        WHERE migration='2026_11_07_000003_create_payment_purchase_inventory_tables') THEN
        RAISE EXCEPTION 'La migracion ya figura registrada; revisar antes de ejecutar';
    END IF;
END $$;

-- CU21-CU26. DDL compartido por la migracion y la propuesta de aplicacion MCP.
-- No ejecutar sobre AWS sin confirmacion. No contiene datos ni credenciales.
CREATE TABLE vida_salud.producto (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre varchar(150) NOT NULL CHECK (btrim(nombre) <> ''),
    descripcion text,
    tipo varchar(30) NOT NULL CHECK (tipo IN ('Medicamento', 'Insumo')),
    unidad_medida varchar(40) NOT NULL CHECK (btrim(unidad_medida) <> ''),
    precio_venta numeric(12,2) CHECK (precio_venta >= 0 AND precio_venta <> 'NaN'::numeric),
    activo boolean NOT NULL DEFAULT true
);

CREATE TABLE vida_salud.proveedor (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre varchar(150) NOT NULL CHECK (btrim(nombre) <> ''),
    nit varchar(30) CHECK (nit IS NULL OR btrim(nit) <> ''),
    telefono varchar(40),
    correo varchar(150),
    direccion text,
    activo boolean NOT NULL DEFAULT true
);

CREATE TABLE vida_salud.compra (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    proveedor_id bigint NOT NULL REFERENCES vida_salud.proveedor(id) ON DELETE RESTRICT,
    registrada_por bigint NOT NULL REFERENCES vida_salud.usuario(id) ON DELETE RESTRICT,
    fecha_compra timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado varchar(30) NOT NULL DEFAULT 'Registrada'
        CHECK (estado IN ('Registrada', 'Parcial', 'Recibida', 'Cancelada')),
    total numeric(12,2) NOT NULL CHECK (total >= 0 AND total <> 'NaN'::numeric)
);

CREATE TABLE vida_salud.detalle_compra (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    compra_id bigint NOT NULL REFERENCES vida_salud.compra(id) ON DELETE RESTRICT,
    producto_id bigint NOT NULL REFERENCES vida_salud.producto(id) ON DELETE RESTRICT,
    cantidad integer NOT NULL CHECK (cantidad > 0),
    precio_unitario numeric(12,2) NOT NULL CHECK (precio_unitario >= 0 AND precio_unitario <> 'NaN'::numeric),
    subtotal numeric(12,2) GENERATED ALWAYS AS (cantidad::numeric * precio_unitario) STORED,
    CONSTRAINT detalle_compra_id_producto_unique UNIQUE (id, producto_id)
);

CREATE TABLE vida_salud.lote (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    producto_id bigint NOT NULL REFERENCES vida_salud.producto(id) ON DELETE RESTRICT,
    numero_lote varchar(80) NOT NULL CHECK (btrim(numero_lote) <> ''),
    fecha_vencimiento date,
    estado varchar(30) NOT NULL DEFAULT 'Disponible' CHECK (estado IN ('Disponible', 'Bloqueado')),
    CONSTRAINT lote_id_producto_unique UNIQUE (id, producto_id)
);
CREATE UNIQUE INDEX lote_producto_numero_unique
    ON vida_salud.lote (producto_id, upper(btrim(numero_lote)));

CREATE TABLE vida_salud.venta (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    paciente_id bigint NOT NULL REFERENCES vida_salud.paciente(id) ON DELETE RESTRICT,
    registrada_por bigint NOT NULL REFERENCES vida_salud.usuario(id) ON DELETE RESTRICT,
    fecha timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total numeric(12,2) NOT NULL CHECK (total >= 0 AND total <> 'NaN'::numeric),
    estado varchar(30) NOT NULL DEFAULT 'Pendiente'
        CHECK (estado IN ('Pendiente', 'Completada', 'PendienteResolucion', 'Cancelada'))
);

CREATE TABLE vida_salud.detalle_venta (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    venta_id bigint NOT NULL REFERENCES vida_salud.venta(id) ON DELETE RESTRICT,
    producto_id bigint NOT NULL REFERENCES vida_salud.producto(id) ON DELETE RESTRICT,
    cantidad integer NOT NULL CHECK (cantidad > 0),
    precio_unitario numeric(12,2) NOT NULL CHECK (precio_unitario >= 0 AND precio_unitario <> 'NaN'::numeric),
    subtotal numeric(12,2) GENERATED ALWAYS AS (cantidad::numeric * precio_unitario) STORED,
    CONSTRAINT detalle_venta_id_producto_unique UNIQUE (id, producto_id)
);

CREATE TABLE vida_salud.detalle_venta_lote (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    detalle_venta_id bigint NOT NULL,
    lote_id bigint NOT NULL,
    producto_id bigint NOT NULL,
    cantidad integer NOT NULL CHECK (cantidad > 0),
    CONSTRAINT detalle_venta_lote_detalle_fk FOREIGN KEY (detalle_venta_id, producto_id)
        REFERENCES vida_salud.detalle_venta(id, producto_id) ON DELETE RESTRICT,
    CONSTRAINT detalle_venta_lote_lote_fk FOREIGN KEY (lote_id, producto_id)
        REFERENCES vida_salud.lote(id, producto_id) ON DELETE RESTRICT,
    CONSTRAINT detalle_venta_lote_asignacion_unique UNIQUE (detalle_venta_id, lote_id),
    CONSTRAINT detalle_venta_lote_origen_unique UNIQUE (id, lote_id, producto_id)
);

CREATE TABLE vida_salud.movimiento_inventario (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    lote_id bigint NOT NULL,
    producto_id bigint NOT NULL,
    usuario_id bigint NOT NULL REFERENCES vida_salud.usuario(id) ON DELETE RESTRICT,
    detalle_compra_id bigint,
    detalle_venta_lote_id bigint,
    tipo varchar(30) NOT NULL CHECK (tipo IN ('Entrada', 'Salida', 'AjusteEntrada', 'AjusteSalida')),
    cantidad integer NOT NULL CHECK (cantidad > 0),
    motivo text,
    fecha timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT movimiento_lote_producto_fk FOREIGN KEY (lote_id, producto_id)
        REFERENCES vida_salud.lote(id, producto_id) ON DELETE RESTRICT,
    CONSTRAINT movimiento_compra_producto_fk FOREIGN KEY (detalle_compra_id, producto_id)
        REFERENCES vida_salud.detalle_compra(id, producto_id) ON DELETE RESTRICT,
    CONSTRAINT movimiento_venta_lote_producto_fk FOREIGN KEY (detalle_venta_lote_id, lote_id, producto_id)
        REFERENCES vida_salud.detalle_venta_lote(id, lote_id, producto_id) ON DELETE RESTRICT,
    CONSTRAINT movimiento_origen_check CHECK (
        (detalle_compra_id IS NULL OR (tipo = 'Entrada' AND detalle_venta_lote_id IS NULL))
        AND (detalle_venta_lote_id IS NULL OR (tipo = 'Salida' AND detalle_compra_id IS NULL))
    ),
    CONSTRAINT movimiento_motivo_check CHECK (
        (detalle_compra_id IS NOT NULL OR detalle_venta_lote_id IS NOT NULL)
        OR NULLIF(btrim(motivo), '') IS NOT NULL
    ),
    CONSTRAINT movimiento_venta_lote_unique UNIQUE (detalle_venta_lote_id)
);

CREATE TABLE vida_salud.pago (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cita_id bigint REFERENCES vida_salud.cita(id) ON DELETE RESTRICT,
    venta_id bigint REFERENCES vida_salud.venta(id) ON DELETE RESTRICT,
    registrado_por bigint NOT NULL REFERENCES vida_salud.usuario(id) ON DELETE RESTRICT,
    monto numeric(12,2) NOT NULL CHECK (monto >= 0 AND monto <> 'NaN'::numeric),
    moneda varchar(3) NOT NULL CHECK (moneda ~ '^[A-Z]{3}$'),
    metodo varchar(30) NOT NULL CHECK (metodo IN ('Efectivo', 'PayPal')),
    estado varchar(30) NOT NULL DEFAULT 'Pendiente'
        CHECK (estado IN ('Pendiente', 'Completado', 'Fallido', 'Cancelado', 'Reembolsado')),
    fecha_creacion timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_confirmacion timestamptz,
    CONSTRAINT pago_destino_exclusivo_check CHECK (num_nonnulls(cita_id, venta_id) = 1),
    CONSTRAINT pago_confirmacion_check CHECK (
        (estado NOT IN ('Completado', 'Reembolsado') OR fecha_confirmacion IS NOT NULL)
        AND (fecha_confirmacion IS NULL OR fecha_confirmacion >= fecha_creacion)
    )
);

CREATE TABLE vida_salud.transaccion_paypal (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pago_id bigint NOT NULL REFERENCES vida_salud.pago(id) ON DELETE RESTRICT,
    paypal_order_id varchar(100) NOT NULL UNIQUE CHECK (btrim(paypal_order_id) <> ''),
    paypal_capture_id varchar(100) UNIQUE CHECK (paypal_capture_id IS NULL OR btrim(paypal_capture_id) <> ''),
    estado varchar(30) NOT NULL CHECK (btrim(estado) <> ''),
    fecha_creacion timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transaccion_paypal_fechas_check CHECK (fecha_actualizacion >= fecha_creacion)
);

CREATE INDEX compra_proveedor_idx ON vida_salud.compra(proveedor_id);
CREATE INDEX compra_usuario_idx ON vida_salud.compra(registrada_por);
CREATE INDEX detalle_compra_compra_idx ON vida_salud.detalle_compra(compra_id);
CREATE INDEX detalle_compra_producto_idx ON vida_salud.detalle_compra(producto_id);
CREATE INDEX venta_paciente_idx ON vida_salud.venta(paciente_id);
CREATE INDEX venta_usuario_idx ON vida_salud.venta(registrada_por);
CREATE INDEX detalle_venta_venta_idx ON vida_salud.detalle_venta(venta_id);
CREATE INDEX detalle_venta_producto_idx ON vida_salud.detalle_venta(producto_id);
CREATE INDEX detalle_venta_lote_lote_idx ON vida_salud.detalle_venta_lote(lote_id, producto_id);
CREATE INDEX movimiento_lote_fecha_idx ON vida_salud.movimiento_inventario(lote_id, fecha, id);
CREATE INDEX movimiento_compra_idx ON vida_salud.movimiento_inventario(detalle_compra_id, producto_id);
CREATE INDEX movimiento_usuario_idx ON vida_salud.movimiento_inventario(usuario_id);
CREATE INDEX pago_cita_idx ON vida_salud.pago(cita_id);
CREATE INDEX pago_venta_idx ON vida_salud.pago(venta_id);
CREATE INDEX pago_usuario_idx ON vida_salud.pago(registrado_por);
CREATE INDEX transaccion_paypal_pago_idx ON vida_salud.transaccion_paypal(pago_id);

-- Registrar solamente esta migracion, despues de completar su DDL en la misma transaccion.
INSERT INTO vida_salud.migrations (migration, batch)
SELECT '2026_11_07_000003_create_payment_purchase_inventory_tables', COALESCE(MAX(batch),0)+1
FROM vida_salud.migrations;
COMMIT;