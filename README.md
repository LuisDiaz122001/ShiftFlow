# ShiftFlow

ShiftFlow es una aplicación empresarial para la gestión de turnos, empleados y nómina. Está diseñada para equipos que necesitan controlar horarios, calcular recargos laborales y mantener un flujo de trabajo ágil con una interfaz moderna.

## Tecnologías

- Laravel 12
- Inertia.js
- Vue 3
- TailwindCSS
- Ziggy
- MySQL / MariaDB

## Características principales

- Gestión de empleados
- Administración de turnos
- Panel de control (dashboard)
- Cálculo y liquidación de nómina
- Autenticación con roles (admin, supervisor, empleado)
- Navegación SPA con Inertia

## Instalación

1. Clonar el repositorio:
   ```bash
   git clone <repository-url>
   cd ShiftFlow/backend
   ```
2. Instalar dependencias PHP:
   ```bash
   composer install
   ```
3. Instalar dependencias JavaScript:
   ```bash
   npm install
   ```
4. Copiar el archivo de entorno y ajustar variables:
   ```bash
   cp .env.example .env
   ```
5. Generar clave de aplicación:
   ```bash
   php artisan key:generate
   ```
6. Configurar la base de datos en `.env`.
7. Ejecutar migraciones y seeders si es necesario:
   ```bash
   php artisan migrate
   ```

## Ejecutar el proyecto

En desarrollo, ejecutar:

```bash
php artisan serve
npm run dev
```

Luego abrir `http://127.0.0.1:8000`.

## Estructura principal

- `routes/web.php` — rutas web y páginas Inertia
- `resources/js/Pages` — componentes Vue de páginas
- `resources/js/Layouts` — layouts globales de Inertia
- `app/Http/Controllers` — controladores Laravel
- `app/Actions` — lógica de negocio separada en acciones
- `app/Http/Requests` — validación de formularios

## Funcionalidades destacadas

- Registro y gestión de empleados
- Gestión de turnos con cálculo automático
- Generación de nómina y estado de pago
- Dashboard administrativo
- Protección de rutas con middleware de roles

## Uso básico

- Iniciar sesión como administrador o supervisor.
- Acceder a la sección de empleados para crear, editar o eliminar registros.
- Administrar turnos y revisar la nómina.
- Navegar entre módulos sin recargas de página gracias a Inertia.

## Notas

Este README es una guía de presentación y puesta en marcha. Para detalles técnicos y registros de debugging, revisa `historialtecnico.md`.
