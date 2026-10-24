# Decisiones funcionales y técnicas

Estas decisiones concretan la delegación «haz todo» y las precisiones de integración del proyecto. Son reglas de aceptación; su implementación se comprueba con las evidencias de [aceptacion.md](aceptacion.md).

## Datos existentes e instalación

La aplicación trabaja con PostgreSQL, base operativa `Parcial2`, esquema `vida_salud`, tablas españolas en singular y zona horaria `America/La_Paz`. Se conservan el snapshot original, sus claves, restricciones de exclusión, funciones y triggers de auditoría. Las migraciones amplían el esquema y reconocen su presencia en instalaciones existentes.

La instalación usa `migrate --seed`, sin vaciar datos. El rollback del esquema clínico original está protegido. Las cuentas de demostración se crean cuando faltan; una cuenta existente conserva su contraseña. Las asignaciones iniciales de permisos se establecen al crear el sistema o un rol nuevo; repetir los seeders conserva los permisos que administración haya configurado después.

La interfaz usa Blade, CSS y JavaScript estáticos. El runtime local es PHP 8.5.12 con `pdo_pgsql`; los helpers admiten PHP >= 8.3 y ejecutan Composer y Artisan con el mismo binario. No se requiere npm para esta interfaz.

## Roles y alcance

| Rol inicial | Alcance autorizado |
| --- | --- |
| Administrador | Cuentas, roles, catálogos, pacientes y citas como gestión administrativa, verificación del portal, reportes administrativos y bitácora. No obtiene acceso clínico por ser administrador. |
| Recepcionista | Pacientes, solicitudes verificadas del portal y citas; lectura de médicos, especialidades y horarios. No accede a historia, notas, diagnósticos o resultados internos. |
| Médico | Agenda propia y atención de sus citas; historia de pacientes con relación asistencial autorizada, diagnósticos, tratamientos y órdenes propias. Puede publicar resultados de sus órdenes completas. |
| Laboratorista | Consulta y procesamiento de órdenes, resultados, adjuntos y correcciones. No solicita análisis ni registra atención clínica. |
| Paciente verificado | Sus citas, sus diagnósticos y tratamientos, resultados propios publicados y edición de sus datos de contacto. No accede a notas clínicas internas. |

La desactivación de una cuenta o de su rol bloquea el acceso. Un médico con perfil inactivo conserva su clasificación de rol: se rechaza su acceso al alcance médico y nunca se lo convierte en lector de la agenda general. Un identificador ajeno puede producir 404 para no confirmar la existencia del registro.

## Identidad del portal

El registro público crea una cuenta Paciente inactiva y una solicitud durable en `solicitud_portal`, inicialmente Pendiente. No vincula registros por CI ni crea otro paciente automáticamente. Los campos enviados por el solicitante no pueden conceder permisos, activar la cuenta o fijar `paciente_id`.

Administración o recepción verifica presencialmente la identidad, selecciona el paciente existente y confirma expresamente esa verificación. Deben coincidir la fecha de nacimiento y el CI cuando fue declarado. Un paciente solo puede tener una cuenta vinculada: una solicitud posterior no puede tomar control de una cuenta ya verificada. La aprobación activa y vincula; el rechazo conserva su motivo y la solicitud. Ambas resoluciones quedan auditadas.

La edición del perfil público permite teléfono, correo, dirección y contacto de emergencia. No cambia CI, nombre, rol ni vínculo con el paciente. Las notas internas y los borradores de laboratorio no se publican en el portal.

## Agenda y atención

Cada reserva ocupa 30 minutos completos dentro de un horario activo, alineados con el inicio de la franja. Debe ser futura y corresponder a un paciente activo y una combinación activa de médico y especialidad. Los nombres de especialidad y los CI duplicados se comparan normalizados; el CI se almacena recortado y en mayúsculas.

Se impide solapamiento tanto del médico como del paciente, incluso entre médicos diferentes. La protección usa transacciones, bloqueos y restricciones PostgreSQL. Las pruebas de concurrencia comprueban dos procesos reales, un bloqueo observable y una sola reserva confirmada.

Las citas usan Programada, Confirmada, Atendida, Cancelada e Inasistencia. Cancelar exige motivo; reprogramar conserva el registro y sus eventos y vuelve a Programada. El paciente solo gestiona sus citas antes de su inicio. La atención no se registra antes de comenzar y la inasistencia se registra al finalizar el bloque. Los estados finales conservan el historial.

Cambiar médico, paciente o especialidad requiere cancelar y crear otra cita: el trigger existente protege esas relaciones. El médico modifica la atención de sus propias citas y conserva la autoría de consultas. Leer historia autorizada de un paciente no concede permiso para editar consultas de otro médico.

## Laboratorio y publicación

Solo el médico solicita análisis para una consulta autorizada. La orden contiene al menos un análisis activo. Su flujo es Pendiente, En proceso y Completada; la incorporación del último resultado completa la orden. Solo se cancela sin resultados, con motivo. El laboratorista registra o corrige valores y adjuntos privados.

Completar una orden no publica automáticamente sus resultados. El médico solicitante publica cuando está completa y la auditoría identifica a ese médico como actor. Que el laboratorista que registró el valor esté inactivo no invalida la publicación del médico. Publicar no genera una versión de corrección ni cambia al autor técnico original.

Una corrección de contenido conserva la versión anterior, su autor y quién la corrigió, y retira la publicación. El paciente vuelve a verla únicamente después de nueva publicación autorizada. Los adjuntos se sirven mediante rutas con autorización, fuera del directorio público.

## Verificación, respaldo y rendimiento

Las pruebas ejecutan las migraciones reales exclusivamente en una base cuyo nombre termina en `_test`. Una barrera verifica configuración y conexión real antes de cualquier limpieza. Los fixtures históricos preparan una cita pasada temporalmente sin triggers; cada operación bajo prueba se ejecuta con los triggers activos. El teardown de concurrencia usa reconstrucción por migraciones y evita el rollback protegido del snapshot.

Los respaldos incluyen toda la base en formato custom. La restauración crea una base nueva, rechaza `Parcial2` y nombres existentes y usa una transacción única. Los archivos adjuntos y la clave de aplicación tienen un respaldo independiente. La restauración local real y sus conteos están descritos en [validacion_restauracion.md](validacion_restauracion.md).

Los objetivos son agenda en 2 segundos con 10.000 citas y cinco usuarios concurrentes, y reportes en 5 segundos. No se declara cumplimiento a partir de la duración de PHPUnit ni de una consulta vacía: falta una medición con volumen, condiciones y concurrencia documentados. La validación visual y de JavaScript en navegador también requiere evidencia distinta del renderizado HTTP de Blade.

El permiso `laboratorio.consultar` permite lectura y descarga autorizada; retirarle `laboratorio.procesar` a un laboratorista no elimina su lectura. Los médicos con perfil profesional inactivo no acceden a las órdenes. Los reportes de resultados requieren autorización separada y conservan el alcance de las órdenes propias del médico.
