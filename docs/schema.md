# Esquema de Vida Salud

Las migraciones requieren PostgreSQL 18 y utilizan el esquema `vida_salud`. El dump original `database/vida_salud_existing.sql` se conserva intacto. No ejecutar `migrate:fresh`, `db:wipe` ni restauraciones destructivas sobre una base con datos reales.

## Migraciones

1. `bootstrap_vida_salud_schema`: si existe `vida_salud.paciente`, no importa ni reemplaza objetos. En una base vacía instala `btree_gist` e importa el dump, quitando únicamente las directivas de psql `\restrict` y `\unrestrict`. Conserva las opciones de PostgreSQL 18, incluido `transaction_timeout`, y restaura el `search_path` de la conexión. Su reversión lanza una excepción para proteger el esquema original.
2. `extend_vida_salud_for_patient_portal`: agrega los campos y controles descritos abajo. Su reversión elimina solamente sus objetos adicionales y restaura la función original de auditoría. Los valores de las columnas adicionales se pierden al revertir esta migración.
3. `create_vida_salud_framework_tables`: crea `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs` dentro de `vida_salud`. `sessions.user_id` referencia `usuario.id`. No crea `users`. Su reversión exige que todas estas tablas estén vacías.

4. `create_patient_portal_requests`: crea `solicitud_portal`, la cola persistente de solicitudes pendientes de verificación. Las cuentas nuevas quedan inactivas y sin vínculo; recepción verifica identidad y coincidencia de CI/fecha de nacimiento antes de habilitarlas.
5. `correct_laboratory_publication_audit`: separa la validación de correcciones de la publicación, exige al médico solicitante como actor de publicación y conserva su identidad en auditoría.

Configurar `DB_CONNECTION=pgsql` y `DB_SCHEMA=vida_salud,public`. Las tablas de infraestructura se pueden resolver con ese `search_path` o mediante nombres calificados en la configuración. Laravel crea su propia tabla `migrations` en el primer esquema disponible; no pertenece al dump clínico.

## Contrato de columnas adicionales

| Tabla | Columna | Definición |
| --- | --- | --- |
| `usuario` | `paciente_id` | `bigint`, nullable, único, FK a `paciente.id` |
| `usuario` | `paciente_verificado_por` | `bigint`, nullable, FK a `usuario.id` |
| `usuario` | `paciente_verificado_en` | `timestamptz`, nullable |
| `cita` | `codigo` | `varchar(30)`, único, generado siempre y almacenado: `VS-00000001` |
| `resultado_laboratorio` | `publicado_por` | `bigint`, nullable, FK a `usuario.id` |
| `resultado_laboratorio` | `publicado_en` | `timestamptz`, nullable |

Las tres columnas de vínculo de paciente deben estar todas vacías o todas completas. Solo el rol `Paciente` admite ese vínculo; una cuenta vinculada a `medico` debe conservar el rol `Medico` y no puede vincularse a un paciente. El verificador puede ser cualquier usuario válido; el backend debe autorizar quién realiza la verificación. Una cuenta `Paciente` puede permanecer sin verificar, con los tres campos vacíos.

`codigo` se omite en INSERT/UPDATE porque PostgreSQL lo genera. El relleno tiene un mínimo de ocho dígitos, sin truncar identificadores mayores. `usuario.correo` tiene unicidad sobre `lower(btrim(correo))`, excluyendo NULL y cadenas vacías. El backend debe rechazar nombres de usuario con `@` para evitar ambigüedad entre correo y usuario al iniciar sesión.

Los campos de publicación deben estar ambos vacíos o ambos completos. El backend autoriza y publica los resultados. Cambiar `resultado`, `observaciones` o `archivo_referencia` retira automáticamente la publicación mediante un trigger BEFORE UPDATE. Las actualizaciones que solo publican, cambian metadatos o guardan los mismos valores mantienen la publicación y no crean versiones. La función original de versiones se ajusta para registrar únicamente cambios clínicos, conservando el autor anterior y el nuevo autor de la corrección. La reversión restaura desde el dump las definiciones exactas de `auditar_cambio` y `versionar_resultado`.

La exclusión GiST evita intervalos solapados de un mismo paciente para estados `Programada`, `Confirmada` y `Atendida`. Los intervalos son `[inicio, fin)`, por lo que las citas consecutivas son válidas. Se mantiene la exclusión original por médico.

Se rechaza desactivar pacientes, médicos, especialidades, asociaciones médico/especialidad, cuentas de médico o roles de médico con citas futuras `Programada`/`Confirmada`. También se impide cambiar el rol de una cuenta de médico con esas citas. Los triggers de reserva toman bloqueos sobre la disponibilidad implicada para coordinar reservas con desactivaciones concurrentes.

## Auditoría

El backend debe establecer, dentro de su transacción, `set_config('app.usuario_id', id, true)` y `set_config('app.direccion_ip', ip, true)`. Los triggers existentes registran el actor y la IP; si no existe contexto, ambos admiten NULL. Los seeders no desactivan triggers. Las inserciones iniciales de usuarios pueden registrar actor NULL; el directorio demo se registra con el administrador demo.

## Seeders

`DatabaseSeeder` ejecuta `RolePermissionSeeder` y `DemoAccountSeeder`. Mantiene los roles existentes, agrega `Paciente`, conserva roles personalizados y permisos fuera del catálogo administrado. En la primera inicialización asigna los permisos predeterminados que siguen. Las ejecuciones posteriores conservan las asignaciones configuradas por administración y solo inicializan los roles que aún no existen:

| Rol | Permisos predeterminados |
| --- | --- |
| Administrador | Usuarios, roles, pacientes, citas, agenda, médicos, especialidades, horarios, catálogo de análisis, reportes administrativos y bitácora; sin acceso clínico automático |
| Recepcionista | Pacientes, citas, agenda y reportes administrativos |
| Medico | Agenda, atención, historia, solicitud/consulta de laboratorio y reportes clínicos |
| Laboratorista | Consulta/procesamiento de laboratorio y reportes de laboratorio |
| Paciente | `citas.propias`, `perfil.propio` |

Las cuentas son `admin`, `recepcion`, `medico`, `laboratorio` y `paciente`. Su contraseña inicial procede de `DEMO_PASSWORD`; en `local`/`testing` puede omitirse y se utiliza `VidaSalud2026!`. En otros entornos debe configurarse explícitamente. Repetir los seeders no cambia contraseñas, datos personales, estados activos ni vínculos existentes. Un conflicto de identidad o rol genera una excepción en lugar de reemplazar la cuenta.

Se crea Ana Prueba (`TEST-001`), la cuenta paciente verificada por el administrador, Dr Uno (`MED-TEST-01`), Medicina General, horarios de lunes a viernes de 08:00 a 12:00 cuando no existe ya un horario ese día, y tres análisis básicos. No se crean citas, consultas, órdenes ni resultados ficticios. Para cargar únicamente roles y permisos: `php artisan db:seed --class=RolePermissionSeeder --no-interaction`.

`UserFactory` utiliza las columnas reales de `usuario`, ofrece `withRole(...)` y `verifiedPatient(...)`, y no requiere una tabla Laravel `users`.
