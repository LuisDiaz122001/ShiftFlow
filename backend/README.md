# 🚀 ShiftFlow - Plataforma de Nómina Inteligente

ShiftFlow es una plataforma de gestión de nómina moderna, diseñada con un núcleo de cálculo determinístico y una arquitectura auditable preparada para el mercado colombiano.

## 💎 Características Principales

### ⚙️ Motor de Cálculo de Turnos
*   Cálculo automático de recargos nocturnos, dominicales y festivos.
*   Gestión de horas extras (diurnas/nocturnas).
*   Integración con contratos reales para resolución de salario base.

### 🛡️ Integridad y Auditoría
*   **Snapshot de Auditoría**: Captura inmutable de las reglas laborales y salarios al momento del cálculo.
*   **Control de Estados**: Ciclos de nómina protegidos con máquina de estados (`open` -> `generated` -> `closed`).
*   **Inmutabilidad**: Bloqueo real de edición de turnos y cálculos en periodos cerrados.
*   **Integridad Matemática**: Validación estricta de `Ingresos - Deducciones == Neto` antes del cierre.

### 🌐 API REST v1
*   API profesional y versionada bajo `/api/v1/`.
*   Resources estandarizados para una integración sencilla con el frontend.
*   Eager loading implementado para eliminar problemas de consultas N+1.

## 🛠️ Stack Tecnológico

*   **Backend**: Laravel 11.x (PHP 8.2+)
*   **Base de Datos**: MySQL / SQLite (Auditoría JSON soportada)
*   **Testing**: PHPUnit (Suite de integridad de dominio y API)
*   **Arquitectura**: Capa de Aplicación basada en **Actions** (Business Logic Decoupling)

## 🚀 Instalación y Setup

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/LuisDiaz122001/ShiftFlow.git
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Configurar el entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migraciones y Seeds**
   ```bash
   php artisan migrate --seed
   ```

5. **Ejecutar tests**
   ```bash
   php artisan test
   ```

## 🌐 API REST v1

La plataforma expone una API RESTful documentada bajo el estándar **OpenAPI 3.0**.

### 📄 Documentación Interactiva
Puedes visualizar y probar la API utilizando el archivo [openapi.yaml](file:///c:/xampp/htdocs/ShiftFlow/backend/openapi.yaml) en herramientas como:
*   [Swagger Editor](https://editor.swagger.io/)
*   [Postman](https://www.postman.com/) (Importar YAML)

### 📍 Endpoints Principales

| Recurso | Método | Endpoint | Descripción |
| :--- | :--- | :--- | :--- |
| **Turnos** | POST | `/api/v1/shifts` | Registrar y calcular un turno. |
| **Ciclos** | POST | `/api/v1/payroll-cycles/{id}/process` | Liquidar nómina masiva del ciclo. |
| **Nóminas** | GET | `/api/v1/payrolls?employee_id={id}` | Consultar historial de pagos. |

## 🛡️ Seguridad y Autorización (Gold Standard)

ShiftFlow implementa un modelo de autorización de **tres niveles** basado en el middleware `CheckRole`, protegiendo cada endpoint según la función del usuario.

### 🔐 Roles del Sistema

| Rol | Descripción |
|:---|:---|
| `admin` | Control total del sistema (aprobaciones, nómina, ciclos). |
| `supervisor` | Acceso de lectura a empleados y dashboard. |
| `employee` | Acceso restringido a sus propios turnos y nóminas. |

### 📋 Matriz de Acceso por Endpoint

| Endpoint | employee | supervisor | admin |
|:---|:---:|:---:|:---:|
| `POST /auth/login` | ✅ | ✅ | ✅ |
| `GET /auth/me` | ✅ | ✅ | ✅ |
| `POST /auth/logout` | ✅ | ✅ | ✅ |
| `GET /shifts` (propios) | ✅ | ✅ | ✅ |
| `POST /shifts` (pendiente) | ✅ | ✅ | ✅ |
| `GET /payrolls` (propias) | ✅ | ✅ | ✅ |
| `GET /employees` | ❌ | ✅ | ✅ |
| `POST /shifts/{id}/approve` | ❌ | ❌ | ✅ |
| `POST /shifts/{id}/reject` | ❌ | ❌ | ✅ |
| `POST /shifts/{id}/void` | ❌ | ❌ | ✅ |
| `POST /payroll-cycles` | ❌ | ❌ | ✅ |
| `POST /payroll-cycles/{id}/process` | ❌ | ❌ | ✅ |
| `POST /payroll-cycles/{id}/close` | ❌ | ❌ | ✅ |

### 🔑 Cuentas de Prueba (Seeder)

| Email | Password | Rol |
|:---|:---|:---|
| `admin@test.com` | `password` | `admin` |
| `emp@test.com` | `password` | `employee` |

### 📋 Ciclo de Vida de los Turnos
1.  **Registro**: Los empleados registran turnos en estado `pending`.
2.  **Aprobación**: Un administrador revisa y marca como `approved`.
3.  **Inmutabilidad**: Una vez aprobado o rechazado, el turno **no puede ser editado**.
4.  **Anulación (Voiding)**: Si un turno aprobado es erróneo, se debe **anular** (no eliminar). El sistema permite crear un turno de reemplazo con trazabilidad cruzada.

### 🚫 Políticas de Integridad
*   **Zero-Delete**: La eliminación física de turnos está prohibida a nivel de modelo.
*   **Ownership Check**: El sistema inyecta forzosamente el `employee_id` desde el usuario autenticado, impidiendo la suplantación de identidades.
*   **Blindaje de Nómina**: El motor de liquidación ignora automáticamente turnos anulados o no aprobados.

---
Desarrollado como una solución robusta para la gestión de personal operativo bajo estándares de auditoría estricta.