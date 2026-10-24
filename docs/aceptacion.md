# Aceptación y evidencia de verificación

Verificación local del **24/10/2026**, con PHP **8.5.12**, Laravel **13.38.0**, PHPUnit **12.5.40** y PostgreSQL. Las pruebas se ejecutaron exclusivamente contra **Parcial2_test**, esquema `vida_salud`, mediante las migraciones reales. La base operativa `Parcial2` no se utilizó como destino de pruebas.

## Resultado final

La ejecución consolidada final de toda la suite aprobó **146/146 pruebas y 542 aserciones**, sin fallos, en **38,287 s**. Evidencia: `storage/logs/phpunit-final-all.xml`. Comando: `vendor/bin/phpunit --log-junit storage/logs/phpunit-final-all.xml --stop-on-failure`, ejecutado con el PHP completo indicado en README.

Antes de la ejecución consolidada se verificaron también estos tres lotes, cuyos resultados coinciden con el total:

| Ejecución | Pruebas aprobadas | Aserciones | Duración | Evidencia |
| --- | ---: | ---: | ---: | --- |
| Funcional, excluyendo concurrencia | 129 de 129 | 454 | 28,852 s | `storage/logs/phpunit-final.xml` |
| Concurrencia con procesos reales | 2 de 2 | 16 | 5,663 s | `storage/logs/phpunit-concurrency.xml` |
| Reglas de aceptación complementarias | 15 de 15 | 72 | 5,229 s | `storage/logs/phpunit-acceptance.xml` |

Comandos de esas ejecuciones:

```powershell
& 'C:/Users/USUARIO/.config/vida-salud/php85/php.exe' vendor/bin/phpunit --exclude-group concurrency --log-junit storage/logs/phpunit-final.xml --stop-on-failure
& 'C:/Users/USUARIO/.config/vida-salud/php85/php.exe' vendor/bin/phpunit --group concurrency --log-junit storage/logs/phpunit-concurrency.xml --stop-on-failure
& 'C:/Users/USUARIO/.config/vida-salud/php85/php.exe' vendor/bin/phpunit tests/Feature/AcceptanceRulesTest.php --log-junit storage/logs/phpunit-acceptance.xml --stop-on-failure
```

Además, la ejecución conjunta previa del agente de pruebas terminó con **131 de 131 aprobadas, 470 aserciones, 34,014 s**. Los tiempos de PHPUnit describen la ejecución de pruebas; no miden los objetivos de respuesta de agenda ni de reportes.

## Etapas y alcance comprobado

| Etapa | Estado y evidencia |
| --- | --- |
| Preparación y aislamiento | Guard de configuración y conexión real antes de la limpieza; rechazo de base operativa, entorno incorrecto y driver incorrecto. Migraciones y seeders reales sobre la base test. |
| Acceso, catálogos y agenda | Casos funcionales aprobados de login, cuentas inactivas, permisos, validación, reservas, cancelación, reprogramación y registros ajenos. |
| Atención y laboratorio | Casos aprobados de consulta, historia autorizada, diagnóstico, órdenes, resultados, publicación auditada y versiones de corrección. |
| Portal | Registro público pendiente, aprobación presencial, prevención de toma de cuenta, lectura propia y edición limitada de contacto aprobados. |
| Integración de vistas | `RolePagesTest` renderizó las páginas permitidas de los cinco roles y verificó rechazos. No se sustituyeron las vistas por mocks. |
| Concurrencia | Dos procesos PHP, conexiones y transacciones independientes, datos iniciales confirmados y bloqueo real observado en `pg_stat_activity`. |
| Operación local | Selectores PHP/PostgreSQL y sintaxis PowerShell comprobados. El integrador ejecutó `start.ps1` en el puerto 8001: eligió el runtime completo e inició el servidor. |
| Recuperación | Respaldo y restauración reales en una base nueva; comparación de conteos de las 29 tablas sin diferencias. |
| Rendimiento y navegador | Pendientes de medición específica y validación visual/interactiva. El renderizado HTTP no acredita interacción JavaScript. |

## Matriz CP01–CP24 de la guía original

Se conservan los identificadores originales de `Vida_Salud_Guia_Desarrollo_Web.md`. Los resultados se refieren a los escenarios concretos ejecutados, con datos ficticios en PostgreSQL.

| Caso | Requisito original | Evidencia aprobada |
| --- | --- | --- |
| CP01 | Paciente nuevo persistente | `CatalogTest`: alta por HTTP y lectura posterior de la fila |
| CP02 | Rechazar CI duplicado normalizado | `CatalogTest`: espacios y capitalización, sin registro parcial |
| CP03 | Reserva libre con evento | `AppointmentTest`: cita, duración, autor y auditoría; concurrencia comprueba evento único |
| CP04 | Rechazar superposición | `AppointmentTest` y `AppointmentConcurrencyTest` |
| CP05 | Rechazar fuera de horario | `AppointmentTest`: pasado, franja externa y bloque desalineado |
| CP06 | Cancelar sin borrar y liberar bloque | `AcceptanceRulesTest`: conserva cita/evento y permite reservar el mismo bloque |
| CP07 | Historial conserva cancelaciones y eventos | `AcceptanceRulesTest`: detalle de cita cancelada renderizado con su motivo |
| CP08 | Agenda con filtros y totales | `AcceptanceRulesTest`: fecha y estado producen el total esperado; `RolePagesTest` renderiza por rol |
| CP09 | Acceso sin sesión denegado | `AuthenticationTest` |
| CP10 | Datos inválidos rechazados sin cambios parciales | `AppointmentTest`, `CatalogTest`, `ClinicalAndLaboratoryTest` |
| CP11 | Reserva simultánea: una confirmación y un conflicto | `AppointmentConcurrencyTest`: dos procesos y bloqueo real, dos variantes |
| CP12 | Médico no administra usuarios por URL directa | `AcceptanceRulesTest`: GET y POST devuelven 403; cuentas intactas |
| CP13 | Paciente no opera registros ajenos | `AppointmentTest`: reserva y cancelación ajenas; `AcceptanceRulesTest`: descarga ajena |
| CP14 | Especialidad duplicada normalizada | `CatalogTest` |
| CP15 | Médico inactivo sin nuevas reservas | `AcceptanceRulesTest`; `AppointmentTest` comprueba que tampoco obtiene agenda general |
| CP16 | Paciente desactivado conserva historial y no reserva | `AcceptanceRulesTest`: exige resolver la cita futura, conserva la cancelada y rechaza nueva reserva |
| CP17 | No reducir horario dejando citas fuera | `AcceptanceRulesTest`: actualización rechazada y horario/cita originales intactos |
| CP18 | Reprogramación conflictiva revierte cambios | `AcceptanceRulesTest`: inicio, estado Confirmada y cantidad de eventos originales intactos |
| CP19 | Estados finales y condiciones temporales | `AcceptanceRulesTest`: ausencia prematura y Cancelada → Atendida rechazadas; `ClinicalAndLaboratoryTest`: atención futura rechazada |
| CP20 | Restauración con conteos y relaciones | Respaldo real restaurado en base nueva: 29 tablas sin diferencias y cero restricciones sin validar; alcance de demostración |
| CP21 | Fechas límite y cantidades por estado en reportes | `AcceptanceRulesTest`: incluye el último día, excluye fecha posterior y filtra Cancelada; reporte sin motivo clínico |
| CP22 | CI existente no vincula cuenta automáticamente | `PortalRegistrationTest`: solicitud pendiente/inactiva, aprobación presencial y prevención de toma de cuenta |
| CP23 | Recepción sin datos de salud | `RolePagesTest`, `ClinicalAndLaboratoryTest` y `AcceptanceRulesTest`: acceso clínico denegado y reportes sin contenido clínico |
| CP24 | Bloques contiguos permitidos | `AcceptanceRulesTest`: dos reservas consecutivas persistidas con dos eventos |

También se comprobaron consulta y diagnóstico autorizados, alta válida de tratamiento, órdenes no vacías por la aplicación, procesamiento de resultados, conservación de versiones, retirada de publicación al corregir, publicación médica aun con el autor técnico inactivo, adjuntos privados y revocación del permiso de historia. Estas comprobaciones complementarias no renumeran los casos de la guía.

La revisión visual en Chromium/Firefox y 360 px, la interacción JavaScript y los objetivos de latencia con 10.000 citas/cinco usuarios siguen pendientes. El rendimiento no corresponde al identificador CP24: es un criterio no funcional independiente.

## Detalles de seguridad de la ejecución

`tests/TestCase.php` valida el entorno `testing`, el driver PostgreSQL, el sufijo `_test`, la igualdad entre base configurada y `current_database()` y la ausencia de configuración en caché **antes** de ejecutar los traits de migración. Cada reinicio del esquema repite la validación. `TestDatabaseSafetyTest` comprueba configuraciones peligrosas sin conectarse a `Parcial2`.

Las pruebas habituales usan `RefreshDatabase`. La primera reconstrucción elimina solo el esquema test `vida_salud`, incluidas funciones que `migrate:fresh` conservaría, aplica migraciones y aísla los casos con transacciones. Los fixtures de citas históricas restauran inmediatamente `session_replication_role` antes de cualquier acción HTTP; las acciones bajo prueba mantienen los triggers activos.

La concurrencia usa `DatabaseMigrations` con limpieza adaptada al rollback protegido del snapshot. La primera reserva conserva una transacción abierta mientras el segundo proceso espera un bloqueo observable; después de confirmar la primera, la segunda recibe 422 y queda una sola cita con su evento. Se cubren ambos conflictos: mismo médico/distintos pacientes y mismo paciente/distintos médicos.

## Evidencias operativas y pendientes

El integrador ejecutó `scripts/backup.ps1` y `scripts/restore.ps1` con el archivo `storage/backups/Parcial2_aplicacion_20261024.dump` y destino nuevo `Parcial2_restore_test`. Las 29 tablas coincidieron en `count(*)`; las restricciones sin validar fueron cero. El respaldo contenía datos de demostración y no tenía expedientes clínicos cargados. La evidencia completa y sus límites están en [validacion_restauracion.md](validacion_restauracion.md).

Los scripts `.ps1` se guardan en UTF-8 con BOM para Windows PowerShell 5.1. Se comprobó la selección del PHP completo y de `pg_dump` 18, filtrando directorios ajenos a versiones como `psqlODBC`. El arranque real en puerto 8001 fue confirmado. También se ejecutó `scripts/install.ps1` sobre esta instalación existente: Composer verificó el runtime, las migraciones quedaron al día y los seeders terminaron sin reemplazar credenciales ni permisos configurados. No se registra como ejecutada una instalación completa de `install.ps1` sobre una máquina limpia.

Quedan pendientes la validación visual y de interacción JavaScript en navegador, la medición de rendimiento acordada y las verificaciones de carga y navegador descritas. Estas evidencias no se deducen de las pruebas HTTP ni de los tiempos de PHPUnit. El resultado consolidado final se registra en `storage/logs/phpunit-final-all.xml`.

## Revisión adicional de calidad

Tras reforzar los formularios se ejecutaron **22 pruebas JavaScript**, todas aprobadas, con `npm run test:js`. Cubren campos obligatorios, correo/usuario, contraseñas, Unicode, fechas imposibles y rangos, horarios, archivos, selecciones y campos opcionales/deshabilitados. La evidencia está en `tests/JavaScript/validation.test.mjs`; las pruebas ejecutan las funciones puras que utiliza la interfaz, sin un navegador ni peticiones de red.

También se volvieron a ejecutar **93 pruebas PHP con 334 aserciones**, todas aprobadas, de `RolePagesTest`, `AppointmentTest` y `AuthenticationTest`. Evidencia: `storage/logs/phpunit-quality.xml`. Se verificaron además la sintaxis JavaScript, Laravel Pint, la compilación Blade y la sintaxis PHP de las vistas compiladas. Estos resultados son una comprobación adicional sobre la suite completa documentada anteriormente, no pruebas nuevas que deban sumarse a su total.

La validación DOM y visual con interacción real en navegador sigue pendiente. No cambiaron permisos ni reglas del negocio en esta revisión.
