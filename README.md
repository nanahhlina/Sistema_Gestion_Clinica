# Vida Salud

Aplicación de gestión de un centro médico construida con Laravel 13, PostgreSQL y vistas Blade en español. Incluye acceso por roles, pacientes, catálogos, agenda con bloques de 30 minutos, atención clínica, laboratorio, portal del paciente, reportes y auditoría. Los reportes de resultados requieren autorización clínica o de laboratorio y limitan al médico a sus propias órdenes.

La base operativa se llama **Parcial2** y el esquema clínico es **vida_salud**. Las tablas conservan los nombres españoles en singular del esquema existente. Las pruebas utilizan exclusivamente **Parcial2_test** y reconstruyen sus datos; nunca deben ejecutarse sobre la base operativa.

## Requisitos

- PHP CLI >= 8.3 con `pdo_pgsql` y las extensiones requeridas por Composer. El entorno local disponible usa **PHP 8.5.12**.
- Composer 2 y PostgreSQL. El snapshot fue generado con PostgreSQL **18.6**; se recomienda esa versión para reproducir sus funciones y triggers.
- PowerShell 5.1 o superior para los scripts de Windows.
- Herramientas `pg_dump`, `pg_restore` y `createdb` para respaldo/restauración. El usuario necesita crear bases nuevas; las migraciones iniciales necesitan instalar `btree_gist` si todavía no existe.

Las pantallas usan CSS y JavaScript estáticos en `public/`; la instalación normal no requiere Node, `npm install`, Vite ni compilar recursos.

## Instalación en Windows

Ejecute los comandos desde la raíz del proyecto. El instalador selecciona, en este orden, `VIDA_SALUD_PHP`, el PHP del `PATH` si tiene `pdo_pgsql`, y el runtime del perfil del usuario:

```powershell
$env:VIDA_SALUD_PHP = 'C:/Users/USUARIO/.config/vida-salud/php85/php.exe'
.\scripts\install.ps1
```

El PHP de Herd Lite presente en el `PATH` local carece de `pdo_pgsql`. Los scripts lo descartan y encuentran el runtime completo; no es necesario modificar la instalación de Herd. Para comprobar el runtime elegido manualmente:

```powershell
& $env:VIDA_SALUD_PHP -v
& $env:VIDA_SALUD_PHP -m
```

Antes de instalar, cree **Parcial2** en PostgreSQL si no existe. Si ya contiene el esquema `vida_salud`, conserve sus datos y obtenga un respaldo. No importe el snapshot encima de una base existente: la migración inicial reconoce el esquema ya presente. En una base vacía, la migración carga `database/vida_salud_existing.sql` y aplica las ampliaciones de la aplicación.

Cuando falta `.env`, el instalador copia `.env.example` y solicita nombre de base, usuario y contraseña PostgreSQL. La contraseña se introduce de forma oculta y se escribe únicamente en `.env`, que está ignorado por Git. Si ya existe `.env`, lo conserva; use `-ConfigureDatabase` para volver a solicitar esos tres valores:

```powershell
.\scripts\install.ps1 -ConfigureDatabase
```

El instalador ejecuta `composer.phar` con el mismo PHP seleccionado, instala las versiones fijadas por `composer.lock`, limpia la caché de configuración, genera `APP_KEY` cuando falta y ejecuta **`artisan migrate --seed`**. No ejecuta `migrate:fresh`. Si Composer no está en el `PATH` o no tiene `composer.phar` junto a su lanzador, instale Composer para Windows antes de repetirlo.

La configuración esperada incluye `DB_CONNECTION=pgsql`, `DB_HOST=localhost`, `DB_PORT=5432`, `DB_DATABASE=Parcial2`, `DB_SCHEMA=vida_salud,public` y la contraseña local en `DB_PASSWORD`. La zona horaria funcional es `America/La_Paz`. Los scripts no contienen la contraseña de PostgreSQL.

## Iniciar y usar

```powershell
.\scripts\start.ps1
# Otro puerto, si 8000 está ocupado:
.\scripts\start.ps1 -Port 8001
```

El servidor de desarrollo escucha en `127.0.0.1`, puerto `8000` por defecto, y muestra su dirección al iniciar. Se detiene con `Ctrl+C`. No se recomienda este servidor para publicación en producción.

Las cuentas iniciales son:

| Usuario | Función |
| --- | --- |
| `admin` | Usuarios, roles, pacientes, agenda, médicos, especialidades, horarios, catálogo de análisis, reportes administrativos y auditoría |
| `recepcion` | Pacientes, verificación de solicitudes del portal y agenda; lectura de catálogos operativos |
| `medico` | Agenda propia, historia autorizada, consultas, diagnósticos, tratamientos y órdenes de laboratorio |
| `laboratorio` | Procesamiento, resultados y correcciones de órdenes de laboratorio |
| `paciente` | Portal verificado de Ana Prueba, CI `TEST-001`, y gestión de sus citas |

Las cuentas nuevas de demostración usan `DEMO_PASSWORD`; su valor local predeterminado es **`VidaSalud2026!`**. Configure otra clave antes del primer seeding cuando corresponda. Repetir el seeder no reemplaza automáticamente las contraseñas de cuentas existentes: actualícelas desde la gestión de usuarios. El directorio inicial incluye Medicina General, el médico de demostración, horarios de lunes a viernes y análisis de ejemplo.

Flujo habitual:

1. Administración configura especialidades, perfiles médicos, horarios y usuarios. Recepción registra al paciente y consulta la disponibilidad.
2. Recepción o el paciente verificado reserva un bloque futuro de 30 minutos. Las citas pueden confirmarse, cancelarse con motivo o reprogramarse; los cambios conservan eventos y auditoría.
3. El médico atiende sus propias citas cuando corresponde, registra historia, notas, diagnósticos y tratamientos, y solicita análisis.
4. Laboratorio registra resultados. Una orden pasa de Pendiente a En proceso y a Completada al completarse todos sus análisis. Las correcciones conservan la versión anterior.
5. El médico publica los resultados completos. El paciente consulta sus diagnósticos, tratamientos y resultados publicados; las notas internas permanecen fuera del portal. Una corrección retira la publicación hasta nueva revisión.

## Registro y verificación del portal

La pantalla pública de registro crea una **solicitud pendiente** y una cuenta de rol Paciente **inactiva**, sin vincularla a un paciente existente. Declarar un CI no concede acceso al historial. El solicitante debe acudir a recepción para verificar su identidad.

El personal autorizado revisa la cola de solicitudes, selecciona expresamente un paciente existente y confirma la verificación presencial. El sistema exige coincidencia de fecha de nacimiento y del CI declarado, si existe, y protege el vínculo único entre paciente y cuenta. Una aprobación habilita la cuenta; un rechazo conserva la solicitud y su observación. No cree otro paciente automáticamente para resolver una identidad dudosa.

## Pruebas aisladas

Cree **Parcial2_test** como base independiente y configure `.env.testing`, ignorado por Git. Puede copiar `.env.example`, cambiar `APP_ENV` a `testing`, `DB_DATABASE` a `Parcial2_test`, introducir la contraseña local de PostgreSQL y generar una clave propia:

```powershell
Copy-Item .env.example .env.testing
# Editar .env.testing antes de continuar.
& $env:VIDA_SALUD_PHP artisan key:generate --env=testing --no-interaction
& $env:VIDA_SALUD_PHP artisan config:clear --no-interaction
& $env:VIDA_SALUD_PHP artisan test --compact
```

No copie encima de un `.env.testing` ya configurado. `phpunit.xml` fija los valores no secretos de PostgreSQL local y la base de pruebas; `DB_PASSWORD` se obtiene del entorno o de `.env.testing`. La contraseña no debe añadirse al XML ni a los scripts.

La clase base comprueba **antes de los traits de migración**: entorno `testing`, driver PostgreSQL, nombre configurado terminado en `_test`, nombre real de `current_database()` idéntico y sin configuración en caché. Si falla una condición, detiene la suite antes de modificar el esquema. Cada reconstrucción también vuelve a verificar la conexión. El guard se prueba con configuraciones peligrosas sin abrir la base operativa.

Las pruebas comunes usan `RefreshDatabase`. Antes de la primera migración se reinicia exclusivamente el esquema `vida_salud` de la base test, incluidas las funciones que `migrate:fresh` no elimina. Luego se aplican las migraciones reales y cada caso aísla sus datos mediante transacciones. Los fixtures históricos importan únicamente una cita pasada con `session_replication_role` temporal; todas las acciones HTTP se ejecutan con triggers activos. Por ello, el usuario de pruebas necesita ese permiso; el entorno local usa `postgres`.

La concurrencia usa `DatabaseMigrations`, datos confirmados y dos procesos PHP reales. La primera reserva conserva su transacción mientras la segunda espera un bloqueo comprobado en `pg_stat_activity`; al confirmar la primera, la segunda debe recibir validación y solo debe existir una cita. Se cubren el mismo médico con distintos pacientes y el mismo paciente con distintos médicos. El teardown reconstruye la base test mediante migraciones, sin invocar el rollback protegido del snapshot original. No ejecute dos suites simultáneas sobre la misma base test.

```powershell
# Solo concurrencia:
& $env:VIDA_SALUD_PHP artisan test --compact --group=concurrency
# Solo páginas renderizadas y permisos por rol:
& $env:VIDA_SALUD_PHP artisan test --compact tests/Feature/RolePagesTest.php
# Un archivo concreto:
& $env:VIDA_SALUD_PHP artisan test --compact tests/Feature/AppointmentTest.php
```

El estado de aceptación y las limitaciones verificadas se registran en [docs/aceptacion.md](docs/aceptacion.md). Las pruebas HTTP renderizan Blade y verifican contratos de datos; no prueban interacción JavaScript en un navegador.

## Respaldo y restauración

Use herramientas PostgreSQL de una versión compatible con el servidor. Los scripts buscan primero el `PATH` y luego los directorios de versión en `C:/Program Files/PostgreSQL`. La contraseña se solicita oculta si no se proporcionó mediante `PGPASSWORD` o `PGPASSFILE`; nunca se incluye en los argumentos del proceso.

```powershell
.\scripts\backup.ps1 -OutputFile 'D:/Respaldos/vida-salud-20261024.dump'
.\scripts\restore.ps1 -BackupFile 'D:/Respaldos/vida-salud-20261024.dump' -NewDatabase Parcial2_restaurada_20261024
```

El directorio de respaldo debe existir y el archivo de destino debe ser nuevo. `backup.ps1` produce un archivo custom de toda la base. Si `pg_dump` falla, un archivo parcial no constituye un respaldo válido. Los informes adjuntos privados de `storage/app/private` deben respaldarse aparte, junto con las claves de aplicación guardadas en un medio seguro.

`restore.ps1` acepta exclusivamente un archivo custom válido, **crea una base nueva** y restaura con `--single-transaction --exit-on-error`. Rechaza expresamente `Parcial2` y cualquier nombre que ya exista; no usa `--clean` ni elimina bases. Si falla la restauración, revierte su transacción y conserva la base nueva para diagnóstico. El snapshot SQL del repositorio se utiliza en la migración inicial; no es un archivo custom para este helper. Verifique la copia restaurada y sus adjuntos antes de cambiar la configuración de una instalación.

## Problemas frecuentes

- **`could not find driver`:** el comando usó el PHP de Herd Lite. Ejecute el script o configure `VIDA_SALUD_PHP` con el runtime completo indicado arriba.
- **Conexión rechazada:** revise que PostgreSQL esté iniciado y que host/puerto coincidan. Si falla la autenticación, corrija la contraseña en el archivo de entorno correspondiente.
- **`relation ... does not exist`:** revise `DB_SCHEMA=vida_salud,public` y el estado de migraciones; ejecute `migrate --seed` con el PHP correcto. No use `migrate:fresh` sobre Parcial2.
- **Pruebas bloqueadas:** confirme el sufijo `_test`, `APP_ENV=testing` y limpie la caché de configuración. No desactive el guard para hacer pasar la suite.
- **Cuenta registrada sin acceso:** la cuenta pública necesita verificación presencial antes de activarse.
- **403/404:** revise rol activo, permisos y propiedad del registro. Los registros ajenos pueden responder 404 para no revelar su existencia.
- **Recursos visuales:** esta interfaz carga archivos estáticos; no necesita el servidor Vite. Revise la consola del navegador y que `public/` sea la raíz web.

Consulte [docs/decisiones.md](docs/decisiones.md) para las reglas funcionales adoptadas y [docs/aceptacion.md](docs/aceptacion.md) para evidencias y pendientes. Los objetivos de rendimiento aún requieren una medición específica con el volumen y la concurrencia acordados.
La construcción por etapas está documentada en [docs/etapas.md](docs/etapas.md).

## Calidad de formularios y código

La interfaz conserva el diseño adaptable de la referencia, con componentes de formularios, mensajes y estados reutilizables. JavaScript comprueba campos obligatorios, correo, usuario, longitud de contraseña, confirmación, fechas, franjas de 30 minutos y adjuntos. Los mensajes aparecen junto a cada campo, con resumen accesible y foco en el primer control inválido. Si JavaScript no está disponible, se mantienen las restricciones HTML y las validaciones del servidor.

Laravel repite las validaciones y verifica permisos y propiedad; PostgreSQL conserva restricciones de integridad y concurrencia. Las validaciones del navegador no conceden permisos ni garantizan unicidad o disponibilidad. Los errores de integridad se traducen a mensajes comprensibles; el sistema tiene páginas en español para 403, 404, 419, 429 y 500. Las respuestas asíncronas distinguen sesión vencida, falta de permiso, demasiadas solicitudes y fallos de conexión.

Los comentarios del código explican el orden de bloqueos, el contexto transaccional de auditoría, los intervalos de citas y la conservación de adjuntos/versiones. El código PHP se formatea con Laravel Pint.

Para ejecutar las pruebas de lógica JavaScript se necesita Node.js, sin instalar paquetes adicionales:

```powershell
npm run test:js
```

Estas pruebas no sustituyen una revisión visual e interactiva en navegador.
