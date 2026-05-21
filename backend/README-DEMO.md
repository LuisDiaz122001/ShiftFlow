# ShiftFlow — Datos demo

## Reinicio rápido

Desde la carpeta `backend/`:

```bash
php artisan app:demo-data --fresh
```

Esto ejecuta `migrate:fresh` y siembra datos demo coherentes.

## Comandos disponibles

| Comando | Descripción |
|---------|-------------|
| `php artisan app:demo-data --fresh` | Borra tablas, migra y siembra demo |
| `php artisan app:demo-data --seed-only` | Solo ejecuta `DemoDataSeeder` |
| `php artisan migrate:fresh --seed` | Equivalente vía `DatabaseSeeder` |

## Usuarios demo

| Rol | Email | Password |
|-----|-------|----------|
| Admin | admin@test.com | password |
| Supervisor | supervisor@test.com | password |
| Empleado | emp@test.com | password |
| Empleados | empleado1@test.com … empleado8@test.com | password |

## Qué genera el seeder

- Regla laboral base (vigente desde enero)
- 5 festivos nacionales del año actual
- 9 empleados con contratos activos
- 3 periodos de nómina (últimos 3 meses)
- ~88 turnos (aprobados, pendientes y rechazados)
- Asistencias de los últimos días + check-in abiertos hoy
- Nóminas procesadas en un periodo (algunas marcadas como pagadas)
- Un periodo abierto para probar **Procesar** desde la UI

## Flujo operativo de prueba

1. Login como `admin@test.com`
2. Dashboard → ver métricas y gráficos
3. Nómina → Periodos → **Procesar** el periodo abierto
4. Nómina Global → marcar pagos / ver detalle
