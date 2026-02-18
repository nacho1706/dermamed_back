# DermaMED Backend — Instrucciones para Agentes

> Lee `../AGENTS.md` primero para contexto general del proyecto.

---

## Entorno

- **Framework**: Laravel 12 (PHP 8.2+)
- **DB**: PostgreSQL 17 (Docker)
- **Auth**: JWT (tymon/jwt-auth) — stateless
- **Container**: `backend-dermamed`

## Comandos

> **SIEMPRE ejecutar sin `-it`** para compatibilidad con agentes y CI/CD.

```bash
# Setup inicial
docker exec backend-dermamed composer setup

# Migraciones
docker exec backend-dermamed php artisan migrate
docker exec backend-dermamed php artisan migrate:fresh --seed
docker exec backend-dermamed php artisan migrate:rollback
docker exec backend-dermamed php artisan migrate:status

# Tests
docker exec backend-dermamed php artisan test
docker exec backend-dermamed php artisan test tests/Feature/PatientTest.php

# Code format
docker exec backend-dermamed ./vendor/bin/pint

# Generar recursos
docker exec backend-dermamed php artisan make:model ModelName -mfc
docker exec backend-dermamed php artisan make:controller ControllerName
docker exec backend-dermamed php artisan make:request RequestName
docker exec backend-dermamed php artisan make:resource ResourceName
docker exec backend-dermamed php artisan make:seeder SeederName

# Cache
docker exec backend-dermamed php artisan config:clear
docker exec backend-dermamed php artisan cache:clear
docker exec backend-dermamed php artisan route:clear
```

---

## Estructura de Directorios

```
app/
├── Factories/          ← Factory pattern para create/update
├── Http/
│   ├── Controllers/    ← Un controller por recurso
│   └── Requests/       ← Carpetas por controller
│       ├── Patient/
│       │   ├── IndexPatientsRequest.php
│       │   ├── StorePatientRequest.php
│       │   └── UpdatePatientRequest.php
│       └── Appointment/
│           └── ...
├── Models/             ← Eloquent models
└── Providers/
```

---

## Patrones de Código

### Naming

| Concepto        | Convención               | Ejemplo                       |
| --------------- | ------------------------ | ----------------------------- |
| Modelos         | Singular, PascalCase     | `Patient`, `MedicalRecord`    |
| Tablas          | Plural, snake_case       | `patients`, `medical_records` |
| Controllers     | Singular + Controller    | `PatientController`           |
| Form Requests   | Action + Model + Request | `StorePatientRequest`         |
| Factories (App) | Model + Factory          | `PatientFactory`              |
| Migraciones     | Verb + table             | `create_patients_table`       |

### Controller → FormRequest → Factory → Model

```php
// 1. Controller recibe FormRequest validado
public function store(StorePatientRequest $request)
{
    $validated = $request->validated();
    $patient = PatientFactory::fromRequest($validated);
    $patient->save();
    return response()->json([
        'success' => true,
        'message' => 'Patient created successfully',
        'patient' => $patient,
    ], 201);
}

// 2. Factory asigna campos
class PatientFactory
{
    public static function fromRequest($request, ?Patient $patient = null): Patient
    {
        $patient = $patient ?? new Patient();
        $patient->first_name = $request['first_name'] ?? $patient->first_name;
        // ... más campos
        return $patient;
    }
}

// 3. Index con paginación
public function index(IndexPatientsRequest $request)
{
    $validated = $request->validated();
    $cantidad = $validated['cantidad'] ?? 10;
    $pagina = $validated['pagina'] ?? 1;

    $query = Patient::query();
    // ... filtros ...

    $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

    return response()->json([
        'data' => $paginador->items(),
        'current_page' => $paginador->currentPage(),
        'total_pages' => $paginador->lastPage(),
        'total_registros' => $paginador->total(),
    ]);
}
```

### Reglas Generales

- **Validación**: Siempre usar Form Requests, nunca validar en el controller
- **Mass assignment**: Definir `$fillable` en cada modelo
- **Casts**: Usar el método `casts()` (Laravel 12 pattern, no property)
- **Relaciones**: Definir todas en el modelo con return types
- **Responses**: Siempre JSON con `success`, `message`, y el recurso
- **Soft deletes**: Usar en Patient, User, Appointment, Invoice
- **Paginación**: Parámetros en español (`cantidad`, `pagina`)
- **Auth**: JWT con middleware `auth:api` (NO Sanctum)

### Checklist: Crear un Recurso Nuevo

1. Crear migration: `php artisan make:migration create_{tabla}_table`
2. Crear Model con `$fillable`, `casts()`, relaciones, `SoftDeletes` si aplica
3. Crear Factory en `app/Factories/{Model}Factory.php`
4. Crear Form Requests en `app/Http/Requests/{Model}/`
    - `Store{Model}Request.php`
    - `Update{Model}Request.php`
    - `Index{Models}Request.php`
5. Crear Controller con `index`, `store`, `show`, `update`, `destroy`
6. Agregar `apiResource` en `routes/api.php`
7. Ejecutar migration: `docker exec backend-dermamed php artisan migrate`

---

## Learnings

> Si durante una tarea descubrís un error o patrón que no funciona,
> **agregá la solución aquí** para no repetir el problema.

- **Problema**: `docker exec -it` falla con "the input device is not a TTY" → **Solución**: Usar `docker exec` sin `-it`
- **Problema**: `zsh: command not found: php` → **Solución**: Ejecutar dentro del contenedor: `docker exec backend-dermamed php artisan ...`
- **Problema**: Intentar usar `auth:sanctum` en rutas API → **Solución**: Este proyecto usa JWT, el middleware es `auth:api`
