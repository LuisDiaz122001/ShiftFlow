# ShiftFlow - Gestión Inteligente de Turnos y Nómina (v1.1 Stable)

ShiftFlow es una plataforma empresarial de alto nivel diseñada para la gestión operativa de turnos de trabajo y la automatización del cálculo de recargos laborales. Construida con una arquitectura de **Ledger Contable Inmutable**, garantiza que cada liquidación sea una fuente de verdad definitiva, auditable y visualmente impactante.

## 🚀 Características Principales

- **Gestión de Identidad**: Autenticación centralizada con roles diferenciados (Administrador, Supervisor, Empleado).
- **Motor de Cálculos Laborales**: Clasificación automática de horas diurnas, nocturnas y extras según la normativa laboral colombiana.
- **Ledger Contable Inmutable**: Sistema de nómina que congela los datos operativos en snapshots financieros protegidos contra modificaciones.
- **Exportación Oficial**: Generación de comprobantes de nómina en PDF con sello de integridad contable.
- **Interfaz Premium SaaS**: Experiencia de usuario de vanguardia con **Inertia.js**, **Vue 3** y **TailwindCSS**, unificada bajo un sistema de diseño cohesivo.
- **Navegación SPA**: Integración total de **Ziggy** para una navegación instantánea sin recargas de página.
- **Integridad de Datos**: Estrictas reglas de negocio (Source of Truth) que bloquean cambios en turnos ya liquidados.

## 📦 Módulos del Sistema

### 🔐 Autenticación y Seguridad
* Control de acceso basado en roles (RBAC).
* Middleware de protección de rutas y Policies para autorizaciones granulares.
* Protección contra ataques CSRF y gestión segura de sesiones.

### 👥 Gestión de Empleados
* CRUD integral con lógica transaccional y validación robusta vía **StoreEmployeeRequest**.
* Sincronización atómica entre perfiles de usuario y datos laborales.
* Visualización paginada de alto rendimiento.

### 🕒 Gestión de Turnos (Shifts)
* **Fuente Única de Verdad**: Los turnos almacenan sus propios cálculos persistidos tras su aprobación.
* Desglose financiero detallado por turno (Recargos, Extras, Ordinarias).
* Validación cruzada de fechas y bloqueo de edición en periodos liquidados.

### 💰 Sistema de Nómina (Payroll)
* Agregación automática de turnos aprobados por periodo.
* Snapshots inmutables de IDs de turnos para auditoría (Audit Trail).
* Soporte para estados de pago y cierre contable (LOCKED).

## ⚙️ Arquitectura Técnica

El proyecto sigue principios de **Clean Architecture** y **SOLID**:

- **Controllers**: Orquestadores delgados que delegan la lógica a acciones.
- **Actions**: Clases de un solo propósito (Single Responsibility) como `CalculateShiftAction` o `GeneratePayrollPdfAction`.
- **FormRequests**: Centralización de reglas de negocio y validación.
- **Inertia + Ziggy**: Puentes de comunicación reactiva entre Laravel y Vue 3.

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS
- **Routing**: Ziggy Vue Next
- **PDF Engine**: DomPDF
- **Iconografía**: Lucide Vue Next
- **Base de Datos**: MySQL / MariaDB

## 📉 Estado del Proyecto: Production Ready v1.1

El sistema se encuentra en su versión estable consolidada:
* ✅ Registro, cálculo y auditoría de turnos 100% funcional.
* ✅ Sistema de nómina con snapshots inmutables y trazabilidad.
* ✅ Gestión de empleados con validación avanzada y atómica.
* ✅ Interfaz SaaS premium unificada en todos los módulos (Dashboard, Empleados, Nómina).
* ✅ Navegación SPA ultrarrápida y reactiva.
* ✅ Hardening de base de datos para prevenir pérdida de integridad.

---
*ShiftFlow - Optimizando la gestión del capital humano con integridad contable.*