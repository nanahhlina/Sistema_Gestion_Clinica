# Etapas de construcción

La implementación se organizó en módulos dentro de `app/Modules`, conservando la estructura de entrada, configuración y almacenamiento de Laravel.

| Etapa | Entrega | Verificación realizada |
| --- | --- | --- |
| 1. Entorno y esquema | PHP con pdo_pgsql, Laravel Boost, respaldo original, migraciones compatibles con esquema existente y base vacía | Migraciones reales en PostgreSQL; base operativa y base test separadas |
| 2. Acceso | Sesiones, roles activos, permisos por operación y cuentas demo | Pruebas de acceso, denegación y páginas por rol |
| 3. Directorio | Pacientes, médicos, especialidades, asociaciones, horarios y análisis | Validación de duplicados, datos inválidos y estados |
| 4. Agenda | Bloques de 30 minutos, reservas, reprogramación, cancelación, estados y eventos | Restricciones PostgreSQL y pruebas de conflictos, bloques contiguos y rollback |
| 5. Atención | Antecedentes, consultas, diagnósticos, tratamientos e historial autorizado | Aislamiento, autoría y rechazo de atención prematura |
| 6. Laboratorio | Órdenes, resultados privados, archivos, versiones y publicación médica | Pruebas de permisos, orden completa, correcciones y visibilidad en portal |
| 7. Portal | Registro pendiente, verificación presencial, perfil y citas propias | Pruebas contra asociación automática o toma de cuenta ajena |
| 8. Supervisión | Reportes administrativos separados de datos clínicos y bitácora | Filtros, páginas autorizadas y exclusión de información privada |
| 9. Integración | Helpers de instalación, inicio, respaldo y restauración | Instalación local, arranque, restauración en base nueva y comparación de las 29 tablas |

Las evidencias automatizadas, sus resultados y los límites de aceptación se registran en `aceptacion.md`. La revisión visual en navegador y la medición de carga requieren verificaciones adicionales; no se presentan como completadas.
