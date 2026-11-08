<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE vida_salud.pago
                ADD COLUMN recibido_efectivo numeric(12,2),
                ADD COLUMN cambio_efectivo numeric(12,2),
                ADD COLUMN clave_pasarela uuid NOT NULL DEFAULT gen_random_uuid() UNIQUE,
                ADD COLUMN creacion_paypal_iniciada_en timestamptz,
                ADD COLUMN destinatario_paypal varchar(100),
                ADD CONSTRAINT pago_efectivo_check CHECK (
                    (recibido_efectivo IS NULL AND cambio_efectivo IS NULL)
                    OR (metodo='Efectivo' AND moneda='BOB' AND recibido_efectivo IS NOT NULL AND cambio_efectivo IS NOT NULL
                        AND recibido_efectivo>=monto AND cambio_efectivo=recibido_efectivo-monto
                        AND recibido_efectivo<>'NaN'::numeric AND cambio_efectivo>=0));
            ALTER TABLE vida_salud.transaccion_paypal
                ADD COLUMN approval_url text,
                ADD COLUMN captura_solicitada_en timestamptz,
                ADD COLUMN reembolso_solicitado_por bigint REFERENCES vida_salud.usuario(id),
                ADD COLUMN motivo_reembolso text;
            CREATE FUNCTION vida_salud.proteger_pago_original() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP='DELETE' THEN RAISE EXCEPTION 'El pago original se conserva para trazabilidad'; END IF;
                IF ROW(NEW.recibido_efectivo,NEW.cambio_efectivo,NEW.clave_pasarela,NEW.destinatario_paypal)
                    IS DISTINCT FROM ROW(OLD.recibido_efectivo,OLD.cambio_efectivo,OLD.clave_pasarela,OLD.destinatario_paypal) THEN
                    RAISE EXCEPTION 'El comprobante y destinatario originales son inmutables';
                END IF;
                RETURN NEW;
            END; $$;
            CREATE TRIGGER proteger_pago_original BEFORE UPDATE OR DELETE ON vida_salud.pago
                FOR EACH ROW EXECUTE FUNCTION vida_salud.proteger_pago_original();
            SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Los comprobantes e identificadores de pago se conservan; reversión solo tras revisión expresa.');
    }
};
