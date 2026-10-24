# Evidencia de respaldo y restauración

Verificación ejecutada el 24/10/2026 en el equipo del proyecto.

- Origen: `Parcial2`, esquema `vida_salud`.
- Respaldo: `storage/backups/Parcial2_aplicacion_20261024.dump` (excluido de Git).
- Destino nuevo: `Parcial2_restore_test`.
- Comandos utilizados: `scripts/backup.ps1` y `scripts/restore.ps1`.
- Resultado: restauración completa con `pg_restore --single-transaction --exit-on-error`.
- Se comparó `count(*)` de las 29 tablas del esquema en origen y destino: ninguna diferencia.
- Restricciones sin validar en el destino: **0**.

Conteos de referencia: 5 usuarios, 5 roles, 20 permisos, 26 asociaciones de permisos, 1 paciente ficticio, 1 médico, 1 especialidad, 5 horarios, 3 análisis y 5 migraciones. Las tablas de citas, consultas, diagnósticos, tratamientos y resultados estaban vacías en este respaldo.

La prueba acredita la restauración del estado de demostración existente. No acredita una restauración con expedientes clínicos reales ni un volumen alto de datos. La base operativa no se reemplazó ni se borró.
