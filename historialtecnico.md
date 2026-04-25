# Historial Técnico - ShiftFlow

Este archivo se utiliza para rastrear el progreso diario del desarrollo, mantener la continuidad entre sesiones y permitir una comprensión rápida de lo que se realizó anteriormente.

## [2026-04-24] - Versión 1.1 Estable: Unificación Visual y Hardening de Core

Esta sesión consolidó la interfaz de usuario bajo un estándar SaaS Premium y resolvió problemas críticos de autenticación y navegación.

### Cambios realizados
- **Unificación de Interfaz (Premium SaaS UI)**:
    - Migración de todos los módulos (Nómina, Empleados, Dashboard) al nuevo `AppLayout.vue`.
    - Eliminación de dependencias de `AuthenticatedLayout` legado, unificando el diseño de Sidebar, Topbar y Cards.
    - Implementación de **Scrollbars personalizados** globales para una estética más fluida.
- **Navegación SPA (Ziggy Integration)**:
    - Integración total de **Ziggy** en el frontend.
    - Refactorización de `app.js` para usar inyección explícita del objeto de rutas.
    - Corrección de colisión de rutas: prefijo `api.` a rutas de Sanctum.
- **Gestión de Empleados (Fixes Críticos)**:
    - Implementación de **StoreEmployeeRequest** para validación atómica.
    - Refactorización de `EmployeeWebController@data` para devolver respuestas paginadas compatibles.
- **Dashboard v2.0**:
    - Adición de la métrica **"Empleados Registrados"** y corrección de lógica para Administradores y Supervisores.
- **Autenticación**:
    - Alineación de `APP_URL` y configuración de sesiones para evitar errores 419.

---

## [2026-04-24] - Versión 1.0 Estable: Nómina e Integridad Contable

Esta sesión marcó la culminación del núcleo operativo de ShiftFlow, transformándolo en un sistema de registro contable inmutable.

### Cambios realizados
- **Source of Truth (Shift)**: Se movieron todos los campos de cálculo (`total_hours`, `diurnas_hours`, `nocturnas_hours`, `total_pago`) directamente a la tabla `shifts`.
- **Módulo de Nómina (Payroll)**: Implementación de liquidaciones por periodos con lógica de agregación de turnos aprobados.
- **Ledger Contable Inmutable**:
    - Las nóminas se generan con estado **`LOCKED`** y timestamp de cierre.
    - Protección a nivel de modelo que impide la edición o eliminación de registros liquidados.
- **Exportación PDF**: Implementación de `GeneratePayrollPdfAction` y template profesional.

---

## [2026-04-23] - Implementación de Employee Management

### Duración de la sesión
- 5-6 horas

### Nivel de impacto
- Alto

### Cambios realizados
- Refactorización del modelo **Employee** (campo `activo` como fuente de verdad).
- Restricción de `user_id` como **NOT NULL**.
- Implementación de la API con lógica transaccional (creación atómica de User + Employee).
- Introducción de la capa de **Actions** (Create, Update, Delete).
- Refactorización de la entidad **Shift** (campo `notas`, `employee_id` NOT NULL).
- Creación de **CalculateShiftHoursAction** y **ClassifyShiftHoursAction** (jornadas 06:00-21:00).
- Implementación de **CalculateShiftPaymentsAction** con recargos y extras según legislación colombiana.
- Creación de **CalculateOvertimeAction** con reinicio de límite a las 00:00.

### Decisiones técnicas
- No se permite la mutación silenciosa de datos; todas las operaciones deben ser explícitas.
- El `documento` debe ser proporcionado por el usuario.
- Uso estricto de **transacciones de DB** para operaciones críticas.

### Problemas encontrados
- Discordancia en las contraseñas (hashing manual vs automático).
- Errores 403 por asignación incorrecta de roles.
- Esquema legado que permitía `user_id` nulo.

### Soluciones aplicadas
- Se corrigió el hashing para seguir el estándar de Laravel.
- Se reforzaron las restricciones de DB mediante migraciones de limpieza.