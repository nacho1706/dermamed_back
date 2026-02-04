# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (default), supports PostgreSQL
- **API Authentication**: JWT (tymon/jwt-auth) - stateless token-based authentication
- **Queue Driver**: Database-backed queues
- **Session Storage**: Database-backed sessions
- **Development Environment**: Docker

## Docker Development

This project uses Docker for development. All commands must be executed inside the Docker container:

```bash
docker exec -it backend-dermamed <command>
```

For convenience, you can create an alias:
```bash
alias dexec='docker exec -it backend-dermamed'
```

## Development Commands

### Initial Setup
```bash
docker exec -it backend-dermamed composer setup
```
This runs the complete setup: installs dependencies, copies .env, generates app key, runs migrations, and builds assets.

### Development Server
```bash
docker exec -it backend-dermamed composer dev
```
Starts concurrent processes:
- Laravel development server (port 8000)
- Queue worker (database-backed)
- Laravel Pail for real-time logs
- Vite dev server (for future frontend integration)

Alternatively, run individually:
```bash
docker exec -it backend-dermamed php artisan serve
docker exec -it backend-dermamed php artisan queue:listen --tries=1
docker exec -it backend-dermamed php artisan pail --timeout=0
```

### Testing
```bash
# Run all tests
docker exec -it backend-dermamed composer test

# Run specific test suite
docker exec -it backend-dermamed php artisan test --testsuite=Unit
docker exec -it backend-dermamed php artisan test --testsuite=Feature

# Run specific test file
docker exec -it backend-dermamed php artisan test tests/Feature/ExampleTest.php

# Run with coverage
docker exec -it backend-dermamed php artisan test --coverage
```

Tests use an in-memory SQLite database and array-based cache/queue drivers for speed.

### Code Quality
```bash
# Format code (Laravel Pint)
docker exec -it backend-dermamed ./vendor/bin/pint

# Format specific files/directories
docker exec -it backend-dermamed ./vendor/bin/pint app/Models
docker exec -it backend-dermamed ./vendor/bin/pint app/Http/Controllers/UserController.php
```

### Database Operations
```bash
# Run migrations
docker exec -it backend-dermamed php artisan migrate

# Rollback last migration
docker exec -it backend-dermamed php artisan migrate:rollback

# Fresh database with seeders
docker exec -it backend-dermamed php artisan migrate:fresh --seed

# Create new migration
docker exec -it backend-dermamed php artisan make:migration create_table_name

# Check migration status
docker exec -it backend-dermamed php artisan migrate:status
```

### Artisan Commands
```bash
# List all artisan commands
docker exec -it backend-dermamed php artisan list

# Interactive REPL (Tinker)
docker exec -it backend-dermamed php artisan tinker

# Clear caches
docker exec -it backend-dermamed php artisan config:clear
docker exec -it backend-dermamed php artisan cache:clear
docker exec -it backend-dermamed php artisan route:clear
docker exec -it backend-dermamed php artisan view:clear

# Generate resources
docker exec -it backend-dermamed php artisan make:model ModelName -mfc
docker exec -it backend-dermamed php artisan make:controller ControllerName
docker exec -it backend-dermamed php artisan make:request RequestName
docker exec -it backend-dermamed php artisan make:resource ResourceName
docker exec -it backend-dermamed php artisan make:seeder SeederName
docker exec -it backend-dermamed php artisan make:factory FactoryName
```

### Docker Deployment
A Dockerfile is provided with PHP 8.4-Apache, includes:
- PostgreSQL support (pdo_pgsql)
- Image processing (GD with JPEG/FreeType)
- Composer 2
- Apache with mod_rewrite enabled
- Document root configured to `/public`

## Architecture Overview

### Application Bootstrap
- Entry point: `bootstrap/app.php` configures routing, middleware, and exception handling
- Routes are registered for web, API, console, and health check (`/up`)
- Service providers are loaded via `app/Providers/AppServiceProvider.php`

### Authentication & API
- **JWT (tymon/jwt-auth)**: Used for stateless API token authentication
- Tokens are self-contained and don't require database lookups for validation
- User model implements `JWTSubject` interface with `getJWTIdentifier()` and `getJWTCustomClaims()` methods
- Authentication uses `JWTAuth::attempt()` for login and `JWTAuth::fromUser()` for registration
- Token refresh available via `JWTAuth::refresh()`
- Token invalidation (logout) via `JWTAuth::invalidate()`
- Token expiration and other settings configurable via `config/jwt.php`

### Database Schema
Key tables:
- `users`: Standard Laravel user authentication (name, email, password, email_verified_at, remember_token)
- `sessions`: Database-backed session storage
- `password_reset_tokens`: Password reset functionality
- `cache`, `cache_locks`: Database-backed cache
- `jobs`, `job_batches`, `failed_jobs`: Queue system

### Directory Structure
- `app/Http/Controllers/`: HTTP request handlers (extends base `Controller`)
- `app/Http/Requests/`: Form Request classes for validation
- `app/Models/`: Eloquent models (uses `HasFactory` trait)
- `app/Factories/`: Domain factories for creating/updating models from requests
- `routes/api.php`: API routes (prefix: `/api`)
- `routes/web.php`: Web routes
- `routes/console.php`: Artisan commands
- `database/migrations/`: Database schema migrations
- `database/factories/`: Model factories for testing
- `database/seeders/`: Database seeders
- `tests/Unit/`: Unit tests
- `tests/Feature/`: Feature/integration tests

### Configuration
All configuration files are in `config/`:
- `auth.php`: Authentication guards and providers
- `database.php`: Database connections (SQLite default)
- `jwt.php`: JWT authentication settings (token TTL, refresh TTL, algorithm, etc.)
- `queue.php`: Queue connections and drivers
- `cache.php`: Cache stores (database default)

Environment variables are defined in `.env` (copy from `.env.example`).

## Development Notes

### Model Conventions
- All models should extend `Illuminate\Database\Eloquent\Model` or `Authenticatable`
- Use `HasFactory` trait for factory support
- Define `$fillable` or `$guarded` for mass assignment protection
- Use `casts()` method for attribute casting (Laravel 12 pattern, not property)

### Request Validation Pattern
- **All request validation must use dedicated Form Request classes in `app/Http/Requests/`**
- Form Requests encapsulate validation rules separate from controller logic
- **Form Requests must be organized in folders by controller**: Each controller should have its own folder containing all its Form Requests
  - Example: `app/Http/Requests/Cheque/StoreChequeRequest.php`, `app/Http/Requests/Cheque/UpdateChequeRequest.php`
  - Example: `app/Http/Requests/User/StoreUserRequest.php`, `app/Http/Requests/User/UpdateUserRequest.php`
- Request naming convention: `{Action}{ModelName}Request` (e.g., `StoreChequeRequest`, `UpdateChequeRequest`)
- Controllers must type-hint the Form Request class in method signatures
- Use `$validated = $request->validated()` at the beginning of controller methods
- Access validated data using `$validated['field_name']` instead of `$request->field_name`

**Form Request Example:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    public function rules(): array
    {
        return [
            'titulo' => 'string|nullable',
            'numero' => 'numeric|nullable',
            'id_entidad_bancaria' => 'numeric|nullable',
            'fecha_cobro' => 'date|required',
            'id_persona_tomador' => 'required|exists:personas,id',
            'id_persona_librador' => 'required|exists:personas,id',
            'monto_nominal' => 'required|numeric',
            'cantidad_dias_clearing_bancario' => 'nullable|numeric',
            'fecha_operativa' => 'nullable|date',
            'forma_pago' => 'nullable|in:efectivo,transferencia,cheque',
        ];
    }
}
```

**Controller Usage:**
```php
public function store(StoreChequeRequest $request)
{
    $validated = $request->validated();
    
    $cheque = ChequeFactory::fromRequest($validated);
    $cheque->save();
    
    return response()->json(['success' => true, 'cheque' => $cheque], 201);
}

public function update(UpdateChequeRequest $request, $id)
{
    $validated = $request->validated();
    
    $cheque = Cheque::findOrFail($id);
    $cheque = ChequeFactory::fromRequest($validated, $cheque);
    $cheque->save();
    
    return response()->json(['success' => true, 'cheque' => $cheque]);
}
```

### Index Method Pagination Pattern
- **All `index()` methods must use Form Requests with pagination parameters**
- Index requests must accept `cantidad` (items per page) and `pagina` (page number) as optional parameters
- Both parameters should be validated as `sometimes|integer|min:1`
- Default values: `cantidad` = 10, `pagina` = 1
- Use Laravel's `paginate()` method for database queries
- Apply filters from validated request data before pagination
- Response must include: `data`, `current_page`, `total_pages`, `total_registros`

**Index Request Example:**
```php
<?php

namespace App\Http\Requests\Actividad;

use Illuminate\Foundation\Http\FormRequest;

class IndexActividadesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'id_modelo_decision' => 'sometimes|integer|exists:modelos_decision,id',
            'nombre' => 'sometimes|string',
        ];
    }
}
```

**Controller Index Example:**
```php
public function index(IndexActividadesRequest $request)
{
    $validated = $request->validated();
    $cantidad = $validated['cantidad'] ?? 10;
    $pagina = $validated['pagina'] ?? 1;

    $query = Actividad::query()->with('modeloDecision');

    if (isset($validated['id_modelo_decision'])) {
        $query->where('id_modelo_decision', $validated['id_modelo_decision']);
    }

    if (isset($validated['nombre'])) {
        $query->where('nombre', $validated['nombre']);
    }

    $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

    return response()->json([
        'data' => $paginador->items(),
        'current_page' => $paginador->currentPage(),
        'total_pages' => $paginador->lastPage(),
        'total_registros' => $paginador->total(),
    ]);
}
```

### Factory Pattern for Create/Update Operations
- **All create and update operations must use dedicated factory classes in `app/Factories/`**
- Factories encapsulate the logic for building/updating models from request data
- Factory naming convention: `{ModelName}Factory` (e.g., `CodigosDescuentosFactory`)
- Factory method signature: `fromRequest($request, ?Model $model = null): Model`
- The method accepts validated request data (array) and an optional model instance (for updates)
- Use null coalescing to preserve existing values when not provided in request
- Controllers should call the factory method with `$validated` data and then explicitly call `save()`

**Factory Example:**
```php
<?php

namespace App\Factories;

use App\Models\CodigoDescuento;

class CodigosDescuentosFactory
{
    public static function fromRequest($request, ?CodigoDescuento $codigoDescuento = null): CodigoDescuento
    {
        $codigoDescuento = $codigoDescuento ?? new CodigoDescuento();
        $codigoDescuento->codigo = isset($request['codigo']) ? $request['codigo'] : $codigoDescuento->codigo;
        $codigoDescuento->nombre = isset($request['nombre']) ? $request['nombre'] : $codigoDescuento->nombre;
        
        return $codigoDescuento;
    }
}
```

**Controller Usage:**
```php
// Create
$validated = $request->validated();
$model = CodigosDescuentosFactory::fromRequest($validated);
$model->save();

// Update
$validated = $request->validated();
$model = CodigosDescuentosFactory::fromRequest($validated, $existingModel);
$model->save();
```

### API Development
- API routes automatically prefixed with `/api`
- Use Sanctum middleware (`auth:sanctum`) for protected endpoints
- Return JSON responses using Laravel's response helpers or API Resources
- Always use Form Requests for validation (see Request Validation Pattern above)

### Queue Workers
- Queue connection uses database driver by default
- Process jobs with `php artisan queue:work` or `queue:listen`
- Failed jobs are logged to `failed_jobs` table
- Use `--tries=1` flag to avoid retries during development

### Database
- Default connection is SQLite (`database/database.sqlite`)
- PostgreSQL support available via Docker configuration
- Migrations use anonymous class syntax (Laravel 12 standard)
- Use `Schema::create()` and Blueprint for table definitions
