# Historial Técnico - ShiftFlow

Documento de diagnóstico, arquitectura y correcciones del proyecto **ShiftFlow** (Laravel + Inertia + Vue 3).

---

## [2026-05-20] - Corrección de gráficos del dashboard y normalización de series

### Causa raíz

Se detectó una combinación de problemas en el flujo del dashboard:

1. `Dashboard.vue` estaba contaminado con texto ajeno al componente al final del archivo, lo que volvía frágil el render y podía romper la actualización del script en caliente.
2. El render de Chart.js no esperaba al montaje efectivo del `<canvas>` cuando cambiaban los props por Inertia, así que el gráfico podía quedarse sin dibujar en visitas o filtros sucesivos.
3. Las series del backend llegaban solo con los puntos existentes; no se normalizaban los días/meses faltantes, lo que dejaba el gráfico sin contexto visual cuando había pocos datos.

### Solución aplicada

- `DashboardController` ahora construye series completas y consistentes para los dos gráficos.
- Se agregaron logs locales de diagnóstico con `raw_count`, `series_points` y un preview de los datos enviados al frontend.
- `useChart` destruye el gráfico si recibe un dataset inválido, evitando renders fantasma.
- `Dashboard.vue` normaliza números y etiquetas, espera `nextTick()` antes de dibujar y destruye el chart cuando no hay datos.
- Se limpió por completo el archivo del dashboard para eliminar el texto corrupto y dejar solo el componente válido.

### Estructura final de datos del dashboard

`hoursPerDay`:

```json
[
  { "date": "2026-05-01", "total_hours": 8 },
  { "date": "2026-05-02", "total_hours": 0 },
  { "date": "2026-05-03", "total_hours": 6.5 }
]
```

`payrollTrend`:

```json
[
  { "month": "2025-12", "total": 0 },
  { "month": "2026-01", "total": 0 },
  { "month": "2026-02", "total": 0 },
  { "month": "2026-03", "total": 1250000 },
  { "month": "2026-04", "total": 980000 },
  { "month": "2026-05", "total": 1430000 }
]
```

### Verificación de datos demo

- Existen `88` turnos, `43` asistencias y `8` nóminas en la base demo actual.
- Los turnos demo tienen horas calculables y las nóminas cubren varios meses del ciclo demo.
- Si se requiere resembrar: `php artisan app:demo-data --fresh`.

---

## [2026-05-20] - Sistema demo vivo + dashboard operativo + procesar periodos

### Resumen

Fase orientada a dejar el sistema **usable en desarrollo** con datos coherentes, métricas reales en dashboard y flujo completo de procesamiento de periodos desde la UI.

### Comando de desarrollo

```bash
cd backend
php artisan app:demo-data --fresh      # migrate:fresh + DemoDataSeeder
php artisan app:demo-data --seed-only  # solo re-sembrar
php artisan migrate:fresh --seed       # equivalente vía DatabaseSeeder
```

Documentación rápida: `backend/README-DEMO.md`

### Seeders y factories

| Archivo | Rol |
|---------|-----|
| `DemoDataSeeder` | Orquestador principal de datos demo |
| `DatabaseSeeder` | Llama solo a `DemoDataSeeder` |
| `LaborRuleFactory` / `HolidayFactory` | Factories para pruebas y extensión |
| `UserSeeder` | Legacy; credenciales incluidas en `DemoDataSeeder` |

**Volúmenes típicos tras `app:demo-data --fresh`:** ~11 usuarios, 9 empleados, 6 periodos, decenas de turnos y asistencias, nóminas distribuidas en varios meses, 5 festivos.

### Dashboard funcional

`DashboardController` ahora expone:

- Horas hoy / semana / mes
- Empleados activos y totales
- Asistencias del día y check-in abiertos
- Nómina acumulada en rango + tendencia 6 meses
- Gráfico horas por día + gráfico nómina mensual
- Distribución de turnos y top empleados

`Dashboard.vue`: overlay de carga Inertia, breadcrumbs, estados vacíos con hint de demo.

### Procesar periodo (UI)

- Ruta: `POST payrolls/periods/{cycle}/process` → `payrolls.periods.process`
- Controlador: `PayrollCycleController@process` + `ProcessPayrollCycleAction`
- UI: botón **Procesar / Regenerar** en `Payrolls/Cycles.vue` con confirmación y flag `force` si ya está `generated`
- Policy: solo admin, estados `open` o `generated`

### UX añadida

- `Breadcrumbs.vue`, `EmptyState.vue`
- Loader global en dashboard al filtrar
- Breadcrumbs en Periodos y mensajes flash

### Cómo agregar más datos demo

1. Extender `DemoDataSeeder::seedEmployees()` o usar factories en un método nuevo.
2. Ajustar `$sampleDays` en `seedShifts()` para más/menos turnos.
3. Ejecutar `php artisan app:demo-data --seed-only` tras cambios.

### Pendiente

- Sembrar periodo **cerrado** explícito para probar inmutabilidad en UI.
- Comando `app:demo-data` en CI (opcional).
- Skeletons animados en tablas (mejora cosmética).

---

## [2026-05-20] - Módulos administrativos completos + unificación UX

### Resumen ejecutivo

Se implementaron los CRUD web faltantes para **Contratos**, **Reglas laborales**, **Festivos** y **creación de periodos de nómina**. Se unificó la experiencia visual con componentes compartidos, se migró **Empleados** a props Inertia (sin `axios` en listado) y se reforzó seguridad por rol y policies.

### Arquitectura actual

```
┌─────────────────────────────────────────────────────────────┐
│  Browser (Vue 3 + Inertia)                                  │
│  AppLayout · AdminPageHeader · FormPanel · FlashAlerts      │
└───────────────────────────┬─────────────────────────────────┘
                            │ sesión web + CSRF
┌───────────────────────────▼─────────────────────────────────┐
│  routes/web.php                                             │
│  middleware: auth · role:admin|supervisor · verified        │
└───────────────────────────┬─────────────────────────────────┘
                            │
     ┌──────────────────────┼──────────────────────┐
     ▼                      ▼                      ▼
 *WebController      FormRequest            Policy
 (Inertia render)    (validación)          (authorize)
     │                      │                      │
     └──────────────────────┼──────────────────────┘
                            ▼
                      Eloquent Models
```

**Separación API vs Web**

| Capa | Autenticación | Uso |
|------|---------------|-----|
| `routes/web.php` | Sesión (`web` guard) | UI Inertia |
| `routes/api.php` | Sanctum token | Clientes externos / móvil |

### Matriz de módulos (estado actual)

| Módulo | Listado | Crear | Editar | Eliminar | Rol UI | Rutas web |
|--------|---------|-------|--------|----------|--------|-----------|
| Empleados | ✅ Inertia | ✅ | ✅ | ✅ | admin, supervisor | `employees.*` |
| Turnos | ✅ Inertia | ✅ | — | — (void) | auth (+ mod admin) | `shifts.*` |
| Asistencia | ✅ Inertia | check-in/out | — | — | employee | `attendance.*` |
| Nómina | ✅ | ✅ | status | — | admin, supervisor | `payrolls.*` |
| Periodos nómina | ✅ | ✅ | — | cierre | admin | `payrolls.periods*` |
| Contratos | ✅ | ✅ | ✅ | ✅ | admin | `contracts.*` |
| Reglas laborales | ✅ | ✅ | ✅ | ✅ | admin | `labor-rules.*` |
| Festivos | ✅ | ✅ | ✅ | ✅ | admin | `holidays.*` |
| Auditoría nómina | ✅ | — | — | — | admin | `payrolls.audit` |

### Funcionalidades completadas en esta iteración

1. **Contratos** — `ContractWebController`, policies, FormRequests, `Contracts/Index.vue` con filtros por empleado/estado.
2. **Reglas laborales** — `LaborRuleWebController`, validación de `vigente_desde` único, normalización de horas `H:i` → `H:i:s`.
3. **Festivos** — `HolidayWebController`, fecha única en BD, filtros por año y nombre.
4. **Periodos de nómina** — `POST payrolls.periods.store`, validación anti-duplicado y anti-solapamiento (`StorePayrollCycleRequest`), formulario en `Payrolls/Cycles.vue`.
5. **Empleados** — listado paginado vía props Inertia; eliminada ruta `employees.data` y uso de `axios` en la página.
6. **Componentes compartidos** — `AdminPageHeader`, `FlashAlerts`, `FormPanel`, composable `useFlashMessages`.
7. **Sidebar** — sección **Configuración** (solo admin): Contratos, Reglas, Festivos.
8. **Tests** — `AdminConfigModulesTest` (acceso por rol, CRUD festivo, periodos, props empleados).
9. **Ziggy** — rutas regeneradas (`php artisan ziggy:generate`).

### Seguridad implementada

| Control | Implementación |
|---------|----------------|
| Middleware por rol | `role:admin` en configuración; `role:admin,supervisor` en empleados/nómina |
| Policies | `ContractPolicy`, `LaborRulePolicy`, `HolidayPolicy`, `PayrollCyclePolicy` |
| FormRequests | `authorize()` exige `isAdmin()` en módulos de configuración |
| Mass assignment | Solo campos en `$fillable` de cada modelo |
| IDOR empleados | Turnos/asistencia derivan `employee_id` del servidor, no del request |
| Periodos duplicados | Unique DB + validación de solapamiento en `StorePayrollCycleRequest |
| Supervisor | Sin acceso a Contratos/Reglas/Festivos (403) |

### Archivos principales creados/modificados

**Backend**

- `app/Http/Controllers/ContractWebController.php`
- `app/Http/Controllers/LaborRuleWebController.php`
- `app/Http/Controllers/HolidayWebController.php`
- `app/Http/Controllers/EmployeeWebController.php` (props paginados)
- `app/Http/Controllers/PayrollCycleController.php` (`store`)
- `app/Policies/ContractPolicy.php`, `LaborRulePolicy.php`, `HolidayPolicy.php`
- `app/Http/Requests/Store*Request.php`, `Update*Request.php`, `StorePayrollCycleRequest.php`
- `routes/web.php`
- `tests/Feature/AdminConfigModulesTest.php`

**Frontend**

- `resources/js/Pages/Contracts/Index.vue`
- `resources/js/Pages/LaborRules/Index.vue`
- `resources/js/Pages/Holidays/Index.vue`
- `resources/js/Pages/Employees/Index.vue` (refactor Inertia)
- `resources/js/Pages/Payrolls/Cycles.vue` (formulario crear)
- `resources/js/Components/Admin/*`
- `resources/js/Composables/useFlashMessages.js`
- `resources/js/Components/Layout/Sidebar.vue`
- `resources/js/ziggy.js` (regenerado)

### Pendientes reales (siguiente fase)

1. **Procesar ciclo desde UI** — la API expone `payroll-cycles/{id}/process`; falta botón web en periodos o dashboard.
2. **Edición/eliminación de periodos** — solo creación y cierre; ciclos `generated/closed` son inmutables por diseño.
3. **Breadcrumbs** — no implementados (opcional UX).
4. **Tests CRUD** — ampliar cobertura para contratos y reglas laborales.
5. **Build frontend** — ejecutar `npm run build` en entorno con Node en PATH.

### Recomendaciones futuras

- Extraer un composable `useCrudIndex` si se añaden más módulos similares (reducir duplicación entre Index.vue).
- Semilla inicial de `LaborRule` y festivos nacionales en `DatabaseSeeder`.
- Auditoría UI para acciones `PayrollLog` ya existentes en backend.
- Considerar `verified` middleware coherente en todas las rutas admin si se exige email verificado.

---

## [2026-05-20] - Integración Inertia turnos y asistencia

### Problemas corregidos

- `Shifts/Index.vue` consumía `/api/v1` con Sanctum → migrado a rutas web `shifts.*`.
- `AttendanceController` devolvía JSON con formularios Inertia → `redirect()->back()` + flash.
- `markAsPaid` en nómina no enviaba `estado` en el body del formulario.
- Empleados redirigidos a dashboard sin permiso → `/` envía a `shifts.index`.
- Flash global en `HandleInertiaRequests`.

---

## [2026-05-01] - Corrección de rutas web e Inertia (empleados)

- Rutas web `employees.*` con redirect Inertia.
- `router.post/put/delete` en lugar de axios para mutaciones.

---

## [2026-04-24] - Errores 419 / sesiones

- `APP_URL`, CSRF en `app.blade.php`, `axios` con `withCredentials`.

---

## [2026-04-23] - Decisión: Inertia + Web vs API

- UI → `web` + sesión.
- API → Sanctum para integraciones externas.
