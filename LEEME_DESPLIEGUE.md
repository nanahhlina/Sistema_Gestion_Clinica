# Despliegue Vida Salud

Paquete preparado 2026-11-07_17-14-00_Bolivia. Archivos de aplicación en la raíz para Elastic Beanstalk; document_root /public. No se ejecutó despliegue, migración ni carga de datos al generar este ZIP.

Dependencias bloqueadas requieren PHP >=8.4.1 (aunque el rango raíz sea ^8.3); comprobar la plataforma de Elastic Beanstalk. Verificación local con PHP8.5.12, no certifica Linux. composer.json/composer.lock, instalación Linux --no-dev --prefer-dist --optimize-autoloader por Elastic Beanstalk. vendor no se distribuye desde Windows. El layout usa public/assets; no requiere un build Vite para sus páginas actuales.

Configurar variables en el entorno AWS, sin subir .env: APP_ENV=production, APP_DEBUG=false, APP_KEY existente, APP_URL HTTPS, DB_CONNECTION=pgsql, DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD/DB_SCHEMA/DB_SSLMODE. Conservar la clave APP_KEY existente del entorno. Plantillas .env.example/.env.aws.example sin credenciales, solo referencia.

PayPal exclusivamente Sandbox: PAYPAL_SANDBOX_ENABLED, PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET y PAYPAL_MERCHANT_ID mediante configuración segura del entorno. No se incluyen valores locales ni se habilita Live.

Corrección de despliegue por memoria: se retiró de .ebextensions la instalación redundante por yum (conservada como referencia en docs/deployment/history, fuera del ZIP). El hook 00-check-php-extensions.sh verifica PHP/extensiones sin instalar paquetes ni refrescar repositorios. Los registros del intento previo mostraron OOM durante yum makecache; esta corrección no garantiza que otras etapas posteriores no requieran más memoria.

Se mantienen hooks de HTTPS que dependen del certificado externo y permisos IAM/S3 existentes; no se incluyen claves privadas ni se verificó ese recurso remoto en esta tarea. Persistencia privada de informes y scheduler deben verificarse en el entorno de destino.

Sin migraciones ni seeders automáticos. AWS ya tenía16 migraciones registradas según verificación07/11/2026; volver a inspeccionar antes de una actualización distinta. Se excluyen los seeders y factories de cuentas de demostración (incluido DatabaseSeeder que los invoca), tests y datos locales; RolePermissionSeeder permanece disponible para una operación expresamente autorizada. No usar composer setup en un entorno operativo.

Límite conocido: CU19 funcional, RNF16 aún pendiente de optimización con AWS (citas5.99s,órdenes7.70s en revisión anterior). No se declara despliegue verificado ni cumplimiento completo de rendimiento. La base AWS contiene datos DEMO por solicitud previa; no forman parte del archivo ZIP.
