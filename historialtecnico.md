# Historial Técnico - ShiftFlow

Este archivo documenta los problemas técnicos, las causas y las soluciones implementadas durante el desarrollo.

## [2026-05-01] - Corrección de rutas web e Inertia

### Problema
- La raíz `/` mostraba una página de bienvenida en lugar del dashboard.
- Las rutas de empleados usaban llamadas a `api.*` en un flujo basado en sesión.
- El frontend recibía el error: `All Inertia requests must receive a valid Inertia response`.

### Causa
- La ruta `/` devolvía `Inertia::render('Welcome')` cuando no estaba autenticado.
- El componente `Employees/Index.vue` enviaba datos a rutas API token-based en vez de rutas web.
- Los controladores web devolvían respuestas JSON en lugar de redirecciones compatibles con Inertia.

### Solución
- Actualización de `routes/web.php` para redirigir `/` siempre a `route('dashboard')`.
- Reemplazo de `route('api.employees.*')` por `route('employees.*')` en el frontend.
- Agregado `PUT` y `DELETE` a `routes/web.php` para `employees.update` y `employees.destroy`.
- Refactorización de los métodos `store`, `update` y `destroy` en `EmployeeWebController` para retornar `redirect()->route('employees.index')->with(...)`.

---

## [2026-05-01] - Ajuste de Inertia POST/PUT/DELETE en Employees

### Problema
- El componente utilizaba Axios para `post`, `put` y `delete` en operaciones de formulario.
- Se perdía la compatibilidad con las redirecciones de Laravel y la validación de Inertia.

### Causa
- Inertia esperaba un response válido o una redirección, pero el backend devolvía JSON.
- El manejo manual de errores Axios no coincidía con el flujo de `router.post` de Inertia.

### Solución
- Importación de `router` desde `@inertiajs/vue3`.
- Reemplazo de las llamadas `axios.post/axios.put/axios.delete` por `router.post/router.put/router.delete`.
- Uso de callbacks `onSuccess`, `onError` y `onFinish` para limpieza de formulario y asignación de errores.

---

## [2026-04-24] - Problema 419 y configuración de sesiones

### Problema
- Errores 419 en formularios y rutas protegidas por sesión.

### Causa
- Configuración incorrecta de `APP_URL` y token CSRF en el frontend.

### Solución
- Limpieza de cache con `php artisan optimize:clear`.
- Verificación de `axios.defaults.withCredentials = true`.
- Confirmación de que el token CSRF se inyecta desde `resources/views/app.blade.php`.

---

## [2026-04-23] - Decisión técnica: Inertia + Web routes vs API

### Problema
- Confusión entre rutas web autenticadas y endpoints API token-based.

### Causa
- Uso mixto de `api.*` y `web` en un mismo flujo de interfaz de usuario.

### Solución
- Definición clara: el UI web debe usar rutas web con sesión (`web guard`).
- Las rutas API token-based quedan reservadas para clientes externos y móviles.
- Refactor del proyecto para separar `routes/web.php` y `routes/api.php` de forma consistente.
