<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::selectOne("SELECT to_regclass('vida_salud.operacion_web') AS name")->name) {
            throw new RuntimeException('Se requiere complete_clinical_workflows y operacion_web antes de finanzas.');
        }
        DB::unprepared(<<<'SQL'
            CREATE TABLE vida_salud.configuracion_financiera (
                id smallint PRIMARY KEY CHECK (id=1),
                bob_por_usd numeric(18,8) NOT NULL CHECK (bob_por_usd>0 AND bob_por_usd<>'NaN'::numeric),
                version bigint NOT NULL CHECK (version>0),
                actualizada_por bigint NOT NULL REFERENCES vida_salud.usuario(id),
                actualizada_en timestamptz NOT NULL DEFAULT current_timestamp,
                motivo text NOT NULL CHECK (length(btrim(motivo)) BETWEEN 1 AND 1000)
            );
            ALTER TABLE vida_salud.pago
                ADD COLUMN monto_pasarela numeric(12,2),
                ADD COLUMN moneda_pasarela varchar(3),
                ADD COLUMN bob_por_usd numeric(18,8),
                ADD COLUMN cotizada_en timestamptz,
                ADD COLUMN operacion_actual varchar(20) CHECK (operacion_actual IN ('Crear','Capturar','Reembolsar','Conciliar')),
                ADD COLUMN propietario uuid,
                ADD COLUMN version_operacion bigint NOT NULL DEFAULT 0 CHECK (version_operacion>=0),
                ADD COLUMN bloqueo_hasta timestamptz,
                ADD COLUMN resultado_incierto boolean NOT NULL DEFAULT false,
                ADD COLUMN reembolsado_por bigint REFERENCES vida_salud.usuario(id),
                ADD COLUMN reembolsado_en timestamptz,
                ADD COLUMN motivo_reembolso text,
                ADD CONSTRAINT pago_cotizacion_check CHECK (
                    (monto_pasarela IS NULL AND moneda_pasarela IS NULL AND bob_por_usd IS NULL AND cotizada_en IS NULL)
                    OR (metodo='PayPal' AND moneda='BOB' AND monto_pasarela IS NOT NULL AND monto_pasarela>0
                        AND monto_pasarela<>'NaN'::numeric AND moneda_pasarela IS NOT NULL AND moneda_pasarela='USD'
                        AND bob_por_usd IS NOT NULL AND bob_por_usd>0 AND bob_por_usd<>'NaN'::numeric AND cotizada_en IS NOT NULL
                        AND monto_pasarela=round(monto/bob_por_usd,2))),
                ADD CONSTRAINT pago_reembolso_check CHECK (
                    (reembolsado_por IS NULL AND reembolsado_en IS NULL AND motivo_reembolso IS NULL AND estado<>'Reembolsado')
                    OR (reembolsado_por IS NOT NULL AND reembolsado_en IS NOT NULL AND reembolsado_en>=fecha_confirmacion
                        AND motivo_reembolso IS NOT NULL AND length(btrim(motivo_reembolso)) BETWEEN 1 AND 1000 AND estado='Reembolsado'));
            CREATE UNIQUE INDEX pago_cita_vigente_unique ON vida_salud.pago(cita_id) WHERE estado IN ('Pendiente','Completado');
            CREATE UNIQUE INDEX pago_venta_vigente_unique ON vida_salud.pago(venta_id) WHERE estado IN ('Pendiente','Completado');
            CREATE INDEX pago_conciliacion_idx ON vida_salud.pago(estado,bloqueo_hasta);
            ALTER TABLE vida_salud.transaccion_paypal
                ADD CONSTRAINT transaccion_paypal_pago_unique UNIQUE(pago_id),
                ADD COLUMN paypal_refund_id varchar(100) UNIQUE,
                ADD COLUMN estado_reembolso varchar(30),
                ADD COLUMN reembolso_solicitado_en timestamptz,
                ADD COLUMN reembolso_confirmado_en timestamptz;
            CREATE UNIQUE INDEX proveedor_nit_normalizado_unique ON vida_salud.proveedor(upper(btrim(nit))) WHERE nit IS NOT NULL;
            CREATE FUNCTION vida_salud.congelar_cotizacion_pago() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF ROW(NEW.cita_id,NEW.venta_id,NEW.registrado_por,NEW.monto,NEW.moneda,NEW.metodo,NEW.fecha_creacion,
                    NEW.monto_pasarela,NEW.moneda_pasarela,NEW.bob_por_usd,NEW.cotizada_en)
                    IS DISTINCT FROM ROW(OLD.cita_id,OLD.venta_id,OLD.registrado_por,OLD.monto,OLD.moneda,OLD.metodo,OLD.fecha_creacion,
                    OLD.monto_pasarela,OLD.moneda_pasarela,OLD.bob_por_usd,OLD.cotizada_en) THEN
                    RAISE EXCEPTION 'El importe, destino y cotización del intento son inmutables';
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER congelar_cotizacion BEFORE UPDATE ON vida_salud.pago FOR EACH ROW EXECUTE FUNCTION vida_salud.congelar_cotizacion_pago();
            SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('El soporte financiero conserva su trazabilidad; no admite retirada automática.');
    }
};
