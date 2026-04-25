# ShiftFlow - Gestión Inteligente de Turnos y Nómina (v1.0 Stable)

ShiftFlow es una plataforma empresarial diseñada para la gestión operativa de turnos de trabajo y la automatización del cálculo de recargos laborales. Construida con una arquitectura de **Ledger Contable Inmutable**, garantiza que cada liquidación sea una fuente de verdad definitiva y auditable.

## 🚀 Características Principales

- **Gestión de Identidad**: Autenticación centralizada con roles diferenciados (Administrador, Supervisor, Empleado).
- **Motor de Cálculos Laborales**: Clasificación automática de horas diurnas, nocturnas y extras según la normativa laboral colombiana.
- **Ledger Contable Inmutable**: Sistema de nómina que congela los datos operativos en snapshots financieros protegidos contra modificaciones.
- **Exportación Oficial**: Generación de comprobantes de nómina en PDF con sello de integridad contable.
- **Interfaz Moderna**: Experiencia de usuario premium tipo SaaS mediante Inertia.js, Vue 3 y TailwindCSS.
- **Integridad de Datos**: Estrictas reglas de negocio (Source of Truth) que bloquean cambios en turnos ya liquidados.

## 📦 Módulos del Sistema

### 🔐 Autenticación y Seguridad
* Control de acceso basado en roles (RBAC).
* Middleware de protección de rutas y Policies para autorizaciones granulares.

### 👥 Gestión de Empleados
* CRUD integral con lógica transaccional.
* Validación estricta de documentos de identidad únicos y restricción de eliminación para preservar integridad contable.

### 🕒 Gestión de Turnos (Shifts)
* **Fuente Única de Verdad**: Los turnos almacenan sus propios cálculos persistidos tras su aprobación.
* Validación cruzada de fechas y bloqueo de edición en periodos liquidados.

### 💰 Sistema de Nómina (Payroll)
* Agregación automática de turnos aprobados por periodo.
* Snapshots inmutables de IDs de turnos para auditoría (Audit Trail).
* Soporte para estados de pago y cierre contable (LOCKED).

## ⚙️ Arquitectura Técnica

El proyecto sigue principios de **Clean Architecture**:

- **Controllers**: Delgados, encargados únicamente de la orquestación.
- **Actions**: Clases de un solo propósito que encapsulan la lógica de negocio pura (Ej: `CalculateShiftAction`, `GeneratePayrollPdfAction`).
- **FormRequests**: Centralización de las reglas de validación.
- **Eloquent Hooks**: Salvaguardas de integridad que impiden la alteración de registros contables.

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS
- **PDF Engine**: DomPDF
- **Iconografía**: Lucide Vue Next
- **Base de Datos**: MySQL / MariaDB

## 📉 Estado del Proyecto: Production Ready v1.0

El sistema se encuentra en su versión estable inicial con todas las capacidades core operativas:
* ✅ Registro, cálculo y auditoría de turnos 100% funcional.
* ✅ Sistema de nómina con snapshots inmutables y trazabilidad.
* ✅ Exportación a PDF con diseño profesional.
* ✅ Hardening de base de datos para prevenir pérdida de integridad.
* ✅ Interfaz SaaS premium completamente responsiva.

---
*ShiftFlow - Optimizando la gestión del capital humano con integridad contable.*