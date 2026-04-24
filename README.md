# ShiftFlow - Gestión Inteligente de Turnos y Nómina

ShiftFlow es una plataforma empresarial diseñada para la gestión operativa de turnos de trabajo y la automatización del cálculo de recargos laborales. Construida con una arquitectura moderna y robusta, permite a las organizaciones tener un control total sobre las jornadas de sus empleados, desde el registro inicial hasta el desglose financiero detallado.

## 🚀 Características Principales

- **Gestión de Identidad**: Autenticación centralizada con roles diferenciados (Administrador, Supervisor, Empleado).
- **Motor de Cálculos Laborales**: Clasificación automática de horas diurnas, nocturnas y extras según la normativa laboral colombiana.
* **API First**: Backend versionado y desacoplado, listo para integraciones.
* **Interfaz Moderna**: Experiencia de usuario fluida mediante Inertia.js y Vue 3.
* **Integridad de Datos**: Estrictas reglas de negocio a nivel de base de datos y aplicación para evitar inconsistencias.

## 📦 Módulos del Sistema

### 🔐 Autenticación y Seguridad
* Control de acceso basado en roles (RBAC).
* Middleware de protección de rutas y Policies para autorizaciones granulares.

### 👥 Gestión de Empleados
* CRUD integral con lógica transaccional (vínculo obligatorio Usuario-Empleado).
* Validación estricta de documentos de identidad únicos.

### 🕒 Gestión de Turnos (Shifts)
* Ciclo de vida completo del turno: `pendiente`, `aprobado`, `rechazado` y `anulado`.
* Validación cruzada de fechas y restricción de integridad con eliminación en cascada.
* Interfaz administrativa para la auditoría de turnos en tiempo real.

## ⚙️ Arquitectura Técnica

El proyecto sigue principios de **Clean Architecture** para garantizar la mantenibilidad y escalabilidad:

- **Controllers**: Delgados, encargados únicamente de la orquestación del flujo.
- **Actions**: Clases de un solo propósito que encapsulan la lógica de negocio pura.
- **FormRequests**: Centralización de las reglas de validación de entrada.
- **Resources**: Transformación y estandarización de las respuestas de la API.
- **Model Events**: Salvaguardas de integridad a nivel de Eloquent.

## 🧮 Motor de Cálculo Laboral (Acciones Puras)

El núcleo de ShiftFlow es su motor de cálculo, el cual procesa los turnos dinámicamente:

1. **`ClassifyShiftHoursAction`**: Segmenta el turno en jornada diurna (06:00 - 21:00) y nocturna (21:00 - 06:00).
2. **`ClassifyOvertimeHoursAction`**: Identifica el exceso sobre las 8 horas diarias, aplicando un reinicio de límite a la medianoche (soporte multidía).
3. **`CalculateShiftPaymentsAction`**: Aplica los recargos legales vigentes:
    * **Ordinaria Diurna**: 1.00
    * **Ordinaria Nocturna**: 1.35
    * **Extra Diurna**: 1.25
    * **Extra Nocturna**: 1.75
    * *Nota: Los cálculos se basan en un estándar de 240 horas mensuales.*

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js
- **Styling**: TailwindCSS
- **Base de Datos**: MySQL / MariaDB
- **Comunicación**: Axios para peticiones asíncronas
- **Testing**: PHPUnit / Pest

## 📈 Estado Actual del Proyecto

Actualmente, el sistema se encuentra en una fase de **Core Operativo Estable**:
* ✅ Gestión de empleados y autenticación completa.
* ✅ Motor de cálculos laborales verificado y preciso.
* ✅ API v1 funcional para la gestión de turnos.
* ✅ Interfaz web para registro y auditoría de turnos con feedback en tiempo real.
* ⚠️ **Importante**: Los cálculos financieros se realizan **al vuelo (dinámicos)** y se exponen vía API/UI; la persistencia definitiva de estos valores en el módulo de nómina es el siguiente paso.

## 🗺️ Roadmap / Próximos Pasos

1. **Módulo de Nómina (Payroll)**: Integración de los cálculos de turnos en el cierre de ciclos de pago.
2. **Persistencia de Liquidación**: Guardado de desgloses financieros al aprobar turnos o cerrar ciclos.
3. **Reportes y Exportación**: Generación de desgloses en PDF/Excel para contabilidad.
4. **Dashboard de Métricas**: Visualización de costos operativos por empleado y departamento.

---
*ShiftFlow - Optimizando la gestión del capital humano.*