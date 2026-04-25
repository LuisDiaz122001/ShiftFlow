# Historial Técnico - ShiftFlow

Este archivo se utiliza para rastrear el progreso diario del desarrollo, mantener la continuidad entre sesiones y permitir una comprensión rápida de lo que se realizó anteriormente.

## [2026-04-23] - Implementación de Employee Management

### Duración de la sesión
- 5-6 horas

### Nivel de impacto
- Alto


### Cambios realizados
- Refactorización del modelo **Employee** (se eliminó la generación automática del campo `documento`).
- El campo `activo` es ahora la única fuente de verdad para el estado del empleado.
- El campo legado `estado` ha sido marcado como **depreciado**.
- Se impuso la restricción de `user_id` como **NOT NULL**.
- Se crearon migraciones para asegurar la integridad de la base de datos (`documento` único y requerido).
- Implementación de la API con lógica transaccional (creación atómica de User + Employee).
- Introducción de la capa de **Actions** (Create, Update, Delete) para desacoplar la lógica de negocio.
- Adición de validación mediante **FormRequest**.
- Implementación de **EmployeeResource** para estandarizar las respuestas de la API.
- Creación de la interfaz Web con **Inertia + Vue**.
- Control de acceso basado en roles (admin, supervisor) aplicado a rutas y componentes.
- Actualización de la navegación lateral (sidebar).
- Refactorización de la entidad **Shift** para mejorar la integridad de datos.
- Adición del campo `notas` (nullable) a la tabla `shifts`.
- Restricción de `employee_id` como **NOT NULL** con eliminación en cascada (**cascade on delete**).
- Validación de negocio en el modelo para asegurar que `fecha_fin > fecha_inicio`.
- Implementación de **StoreShiftRequest** y **UpdateShiftRequest** para validación robusta de datos.
- Creación de **CalculateShiftHoursAction** para el cálculo preciso de horas decimales.
- Implementación de **ClassifyShiftHoursAction** para clasificar horas en jornadas diurnas (06:00-21:00) y nocturnas.
- Creación de **ShiftResource** para estandarizar la salida de datos de turnos.
- Integración de cálculos de horas en **ShiftController@store** exponiendo datos dinámicos sin alterar el esquema de DB.
- Integración completa del desglose financiero en la respuesta de la API de creación de turnos.
- Implementación de la interfaz web de gestión de turnos (**Shifts/Index.vue**) con Vue 3 y TailwindCSS.
- Adición de acciones administrativas en la tabla de turnos (Aprobar, Rechazar, Anular) con actualización de estado en tiempo real.
- Creación del componente reutilizable **ShiftBreakdown.vue** para visualizar cálculos dinámicos.
- Actualización integral de **README.md** reflejando la arquitectura, el motor de cálculo y el estado actual del sistema.
- Refactorización de **CalculateShiftPaymentsAction** para soportar recargos nocturnos, horas extras diurnas y nocturnas según la legislación colombiana.
- Creación de **CalculateOvertimeAction** para separar horas ordinarias y extras, con soporte para turnos de múltiples días (reinicio de límite a las 00:00).
- Implementación de **ClassifyOvertimeHoursAction** para el desglose detallado de horas extras en jornadas diurnas y nocturnas.

### Decisiones técnicas
- No se permite la mutación silenciosa de datos; todas las operaciones deben ser explícitas y validadas.
- El `documento` debe ser proporcionado por el usuario (no auto-generado).
- Un Empleado no puede existir sin un Usuario vinculado.
- Uso estricto de **transacciones de DB** para operaciones críticas que involucren múltiples tablas.
- Mantener una arquitectura limpia: **Controller → Action → Model**.
- Evolución segura del modelo Shift manteniendo campos de legado (`status`, `approved_by`, `is_voided`) para asegurar compatibilidad.
- Validación de fechas en la capa de modelo como salvaguarda antes de implementación en DB.
- Verificación estricta de datos nulos antes de aplicar restricciones de integridad en migraciones.

### Problemas encontrados
- Discordancia en las contraseñas debido a un malentendido en el hashing manual vs automático.
- Errores 403 (Prohibido) debido a una asignación incorrecta de roles durante las pruebas.
- Esquema legado que permitía `user_id` nulo, causando inconsistencias de datos.

### Soluciones aplicadas
- Se corrigió el uso del hashing de contraseñas para seguir el estándar de Laravel.
- Se asignaron los roles correctos a los usuarios de prueba mediante seeders y acciones.
- Se reforzaron las restricciones de la DB mediante migraciones de limpieza.
- Se eliminó la creación automática "silenciosa" de empleados para evitar registros huérfanos.

### Estado actual del sistema
- Autenticación funcionando correctamente.
- Dashboard operativo con visualización según roles.
- API de Empleados completamente funcional.
- Interfaz Web de Empleados funcional.
- Todas las pruebas (Tests) están pasando.

### Notas para la siguiente sesión
- [ ] Mejorar el sistema de permisos (permisos granulares más allá de los roles).
- [ ] Continuar con la UI del módulo de Turnos (Shifts).
- [ ] Revisar la eliminación definitiva de la columna legada `estado`.

### Próximo paso técnico claro
- [ ] Implementar módulo de Shifts con UI
- [ ] Integrar Employees con asignación de turnos

## 2026-04-24 - Gestión completa de turnos y acciones administrativas

En esta sesión se consolidó el módulo de Turnos (Shifts) integrando el motor de cálculo avanzado con una interfaz de usuario dinámica y funcional.

### Cambios realizados
- **Interfaz Web de Turnos**: Creación de la página `/shifts` (`Index.vue`) que incluye formulario de registro y listado histórico.
- **Desglose Visual**: Implementación del componente `ShiftBreakdown.vue` para mostrar el impacto financiero y de horas (diurnas, nocturnas, extras) de cada turno en tiempo real.
- **Acciones Administrativas**: Integración de botones para Aprobar, Rechazar y Anular turnos directamente desde la tabla, con validación de estado y confirmación.
- **Motor de Cálculo**: Refactorización y uso de `ClassifyShiftHoursAction`, `ClassifyOvertimeHoursAction` y `CalculateShiftPaymentsAction` para procesar datos dinámicos en la API.
- **UX Mejorada**: Implementación de estados de carga por fila (*loading per row*) y actualizaciones de estado locales (sin recarga de página).
- **Refactorización UI/UX (SaaS Style)**:
    - **Landing Page (`Welcome.vue`)**: Transformación total de la página por defecto de Laravel en una landing page profesional con secciones Hero, Features y Contacto.
    - **Flujo de Autenticación**: Rediseño completo de `Login.vue` y `Register.vue` con gradientes premium, cards de cristal y animaciones fluidas.
    - **Páginas Públicas**: Creación e integración de `ContactPage.vue`, `TermsPage.vue` y `PrivacyPage.vue`.
    - **Rutas Públicas**: Habilitación de rutas informativas en `web.php` sin requerir autenticación.

### Decisiones técnicas
- **Cálculo Dinámico vs Persistido**: Se decidió mantener los cálculos como propiedades dinámicas en el `ShiftResource` para esta fase; la persistencia se realizará durante el cierre del ciclo de nómina.
- **Backend como Fuente de Verdad**: El frontend no realiza cálculos matemáticos; solo renderiza los valores proporcionados por el motor de cálculo del backend.
- **Normalización UTC**: Aplicada a todas las acciones de cálculo para garantizar precisión en turnos nocturnos y multidía.
- **Manejo de Extras**: Implementación del reinicio de límite de 8 horas a las 00:00 para cumplimiento de legislación laboral.
- **Desacoplamiento de Layouts**: Se optó por incluir la lógica de diseño directamente en las páginas de Auth para permitir un control total sobre el fondo de gradiente y el estilo de las cards sin afectar el `GuestLayout` legado.

### Estado actual
- **Módulo de Empleados**: Estable y funcional.
- **Módulo de Turnos**: Completo en backend y frontend (registro, auditoría y cálculo).
- **Interfaz Pública**: Landing page y páginas informativas 100% funcionales con estética premium.
- **Autenticación**: Flujo de Login y Registro modernizado.
- **Módulo de Nómina**: Motor de cálculo listo; integración con ciclos de nómina pendiente.
- **Legacy**: Tabla `shift_calculations` y relación `calculation` marcadas para eliminación definitiva.

### Próximos pasos
- [ ] Eliminación definitiva de la lógica legacy de cálculos persistidos.
- [ ] Implementación del módulo de Nómina (`PayrollCycle`) para liquidación masiva.
- [ ] Mejora de filtros y búsqueda en el listado de turnos.
- [ ] Desarrollo del Dashboard de métricas operativas.

## [2026-04-24] - Versión 1.0 Estable: Nómina e Integridad Contable

Esta sesión marcó la culminación del núcleo operativo de ShiftFlow, transformándolo en un sistema de registro contable inmutable.

### Cambios realizados
- **Source of Truth (Shift)**: Se movieron todos los campos de cálculo (`total_hours`, `diurnas_hours`, `nocturnas_hours`, `total_pago`) directamente a la tabla `shifts`, eliminando la dependencia de tablas externas.
- **Módulo de Nómina (Payroll)**: Implementación de liquidaciones por periodos con lógica de agregación de turnos aprobados.
- **Ledger Contable Inmutable**:
    - Las nóminas se generan con estado **`LOCKED`** y timestamp de cierre (`closed_at`).
    - Protección a nivel de modelo que impide la edición o eliminación de registros liquidados.
    - Bloqueo automático de edición en turnos que ya forman parte de una nómina cerrada.
- **Exportación PDF**: Implementación de `GeneratePayrollPdfAction` y template profesional en Blade para la generación de comprobantes oficiales.
- **Hardening de Integridad**:
    - Uso de `restrictOnDelete` en llaves foráneas para prevenir la pérdida de registros históricos.
    - Unificación del motor de cálculo en API y Web mediante acciones compartidas.
    - Refactorización del Dashboard para utilizar la nueva fuente de verdad.
- **Consolidación de Arquitectura**: Limpieza de código legacy y unificación del flujo **Controller → Action → Model**.

### Decisiones técnicas
- **Snapshot Operativo**: Se decidió que la nómina debe ser un snapshot inmutable de los IDs de los turnos en el momento de la liquidación para garantizar trazabilidad.
- **Transaccionalidad Total**: Todas las operaciones de creación y cálculo de turnos se envolvieron en transacciones de base de datos.
- **Prevención de Doble Liquidación**: Se añadió un filtro en la agregación para excluir turnos que ya pertenecen a una nómina `LOCKED` o `PAID`.

### Estado Final v1.0
- **Producción**: El sistema está listo para su despliegue operativo inicial.
- **Estabilidad**: El motor de cálculo es preciso y persistente.
- **Seguridad**: Los datos financieros están protegidos por salvaguardas de integridad inalterables.