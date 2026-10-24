
# Sistema de Gestión - Clínica Vida Salud

## Descripción del proyecto

Este proyecto fue realizado como parte de un examen de la materia. Consiste en desarrollar un sistema web para la gestión de la Clínica Vida Salud, con el objetivo de facilitar la organización de sus actividades y mejorar el manejo de la información.

El sistema permite registrar y administrar pacientes, gestionar citas médicas, organizar horarios, registrar consultas, diagnósticos y tratamientos, además de gestionar órdenes y resultados de laboratorio. También cuenta con diferentes roles de usuario, un portal para pacientes y reportes.

## Tecnologías utilizadas

- PHP 8.3 o superior
- Laravel 13
- PostgreSQL
- Blade
- HTML, CSS y JavaScript

## Requisitos

Antes de instalar el proyecto, es necesario tener instalado:

- PHP 8.3 o superior, con la extensión `pdo_pgsql`.
- Composer 2.
- PostgreSQL.
- PowerShell 5.1 o superior (Windows).

## Instalación

### 1. Descargar el proyecto

Clonar el repositorio desde la terminal:

```bash
git clone https://github.com/nanahhlina/Sistema-de-Gesti-n-para-la-Cl-nica-Vida-Salud.git
cd Sistema-de-Gesti-n-para-la-Cl-nica-Vida-Salud
```

Si el proyecto ya está descargado, abrir una terminal en su carpeta principal.

### 2. Crear la base de datos

En PostgreSQL, crear una base de datos llamada `Parcial2`.

El sistema utiliza el esquema `vida_salud`. Si la base de datos ya contiene información, se recomienda realizar un respaldo antes de continuar.

### 3. Instalar y configurar

Desde PowerShell, ejecutar:

```powershell
.\scripts\install.ps1
```

El instalador prepara la configuración del proyecto, solicita los datos de conexión a PostgreSQL cuando corresponde, instala las dependencias y ejecuta las migraciones con los datos iniciales.

Si se necesita volver a configurar la conexión con la base de datos:

```powershell
.\scripts\install.ps1 -ConfigureDatabase
```

### 4. Iniciar el sistema

Ejecutar el siguiente comando:

```powershell
.\scripts\start.ps1
```

Abrir en el navegador la dirección local que aparece en la terminal. Por defecto:

http://127.0.0.1:8000

Para detener el servidor, presionar `Ctrl + C` en la terminal.

## Usuarios de prueba

El sistema cuenta con usuarios de demostración para probar las funciones de cada rol.

| Usuario | Función |
|---|---|
| `admin` | Administración general del sistema |
| `recepcion` | Gestión de pacientes y citas |
| `medico` | Consultas, historias clínicas, diagnósticos y tratamientos |
| `laboratorio` | Gestión de órdenes y resultados de laboratorio |
| `paciente` | Acceso al portal del paciente |

**Contraseña predeterminada para las cuentas nuevas de demostración:**

`VidaSalud2026!`

La contraseña efectiva puede variar si las cuentas ya existían o fueron modificadas anteriormente.

## Funcionalidades principales

- Inicio y cierre de sesión.
- Gestión de usuarios, roles y permisos.
- Registro y administración de pacientes.
- Gestión de médicos, especialidades y horarios.
- Reserva, cancelación y reprogramación de citas.
- Registro de consultas médicas, diagnósticos y tratamientos.
- Gestión de órdenes y resultados de laboratorio.
- Portal para pacientes.
- Generación de reportes.
- Registro de auditoría de las acciones realizadas.

## Consideraciones

- El archivo `.env` contiene datos de configuración y no debe subirse al repositorio.
- Las pruebas automatizadas deben ejecutarse sobre la base de datos independiente `Parcial2_test`.
- No ejecutar `migrate:fresh` sobre la base de datos operativa `Parcial2`.
- El servidor incluido está destinado al desarrollo y las pruebas locales.

---

**Proyecto académico — Sistema de Gestión de la Clínica Vida Salud.**

