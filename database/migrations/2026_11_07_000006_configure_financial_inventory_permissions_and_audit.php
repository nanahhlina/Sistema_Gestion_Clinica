<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pagos.registrar', 'ventas.gestionar', 'productos.gestionar', 'proveedores.gestionar', 'compras.gestionar', 'inventario.consultar', 'inventario.gestionar', 'lotes.gestionar', 'finanzas.configurar', 'pagos.reembolsar'] as $code) {
            DB::table('vida_salud.permiso')->insertOrIgnore(['codigo' => $code, 'descripcion' => $code]);
            $permission = DB::table('vida_salud.permiso')->where('codigo', $code)->value('id');
            $roles = ['Administrador'];
            if (in_array($code, ['pagos.registrar', 'ventas.gestionar'], true)) {
                $roles[] = 'Recepcionista';
            }
            foreach (DB::table('vida_salud.rol')->whereIn('nombre', $roles)->pluck('id') as $role) {
                DB::table('vida_salud.rol_permiso')->insertOrIgnore(['rol_id' => $role, 'permiso_id' => $permission]);
            }
        }
        foreach (['producto', 'proveedor', 'compra', 'detalle_compra', 'lote', 'movimiento_inventario', 'venta', 'detalle_venta', 'detalle_venta_lote', 'pago', 'transaccion_paypal'] as $table) {
            DB::statement("CREATE TRIGGER auditar AFTER INSERT OR UPDATE OR DELETE ON vida_salud.{$table} FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_cambio()");
        }
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION vida_salud.auditar_tipo_cambio() RETURNS trigger LANGUAGE plpgsql SET search_path TO vida_salud,public AS $$
            DECLARE actor bigint;
            BEGIN
                actor:=nullif(current_setting('app.usuario_id',true),'')::bigint;
                IF TG_OP='DELETE' THEN RAISE EXCEPTION 'La configuración financiera se conserva'; END IF;
                PERFORM exigir_permiso(actor,'finanzas.configurar');
                IF NEW.actualizada_por IS DISTINCT FROM actor OR (TG_OP='UPDATE' AND NEW.version<>OLD.version+1)
                    OR (TG_OP='INSERT' AND NEW.version<>1) THEN RAISE EXCEPTION 'Cambio de configuración inválido'; END IF;
                NEW.actualizada_en:=clock_timestamp();
                INSERT INTO bitacora(usuario_id,accion,entidad,registro_id,descripcion,direccion_ip)
                    VALUES(actor,'TIPO_CAMBIO','configuracion_financiera',NEW.id,
                        jsonb_build_object('anterior',CASE WHEN TG_OP='UPDATE' THEN OLD.bob_por_usd::text ELSE NULL END,
                            'nueva',NEW.bob_por_usd::text,'version',NEW.version,'motivo',NEW.motivo)::text,
                        nullif(current_setting('app.direccion_ip',true),'')::inet);
                RETURN NEW;
            END; $$;
            CREATE TRIGGER auditar_tipo_cambio BEFORE INSERT OR UPDATE OR DELETE ON vida_salud.configuracion_financiera
                FOR EACH ROW EXECUTE FUNCTION vida_salud.auditar_tipo_cambio();
            SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('No se eliminan permisos ratificados ni auditoría financiera automáticamente.');
    }
};
