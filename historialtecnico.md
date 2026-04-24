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

### Decisiones técnicas
- **Cálculo Dinámico vs Persistido**: Se decidió mantener los cálculos como propiedades dinámicas en el `ShiftResource` para esta fase; la persistencia se realizará durante el cierre del ciclo de nómina.
- **Backend como Fuente de Verdad**: El frontend no realiza cálculos matemáticos; solo renderiza los valores proporcionados por el motor de cálculo del backend.
- **Normalización UTC**: Aplicada a todas las acciones de cálculo para garantizar precisión en turnos nocturnos y multidía.
- **Manejo de Extras**: Implementación del reinicio de límite de 8 horas a las 00:00 para cumplimiento de legislación laboral.

### Estado actual
- **Módulo de Empleados**: Estable y funcional.
- **Módulo de Turnos**: Completo en backend y frontend (registro, auditoría y cálculo).
- **Módulo de Nómina**: Motor de cálculo listo; integración con ciclos de nómina pendiente.
- **Legacy**: Tabla `shift_calculations` y relación `calculation` marcadas para eliminación definitiva.

### Próximos pasos
- [ ] Eliminación definitiva de la lógica legacy de cálculos persistidos.
- [ ] Implementación del módulo de Nómina (`PayrollCycle`) para liquidación masiva.
- [ ] Mejora de filtros y búsqueda en el listado de turnos.
- [ ] Desarrollo del Dashboard de métricas operativas.