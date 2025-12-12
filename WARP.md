# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (default), supports PostgreSQL
- **API Authentication**: Laravel Sanctum (token-based and SPA authentication)
- **Queue Driver**: Database-backed queues
- **Session Storage**: Database-backed sessions
- **Development Environment**: Docker

## Docker Development

This project uses Docker for development. All commands must be executed inside the Docker container:

```bash
docker exec -it backend-facet-gestion <command>
```

For convenience, you can create an alias:
```bash
alias dexec='docker exec -it backend-facet-gestion'
```

## Development Commands

### Initial Setup
```bash
docker exec -it backend-facet-gestion composer setup
```
This runs the complete setup: installs dependencies, copies .env, generates app key, runs migrations, and builds assets.

### Development Server
```bash
docker exec -it backend-facet-gestion composer dev
```
Starts concurrent processes:
- Laravel development server (port 8000)
- Queue worker (database-backed)
- Laravel Pail for real-time logs
- Vite dev server (for future frontend integration)

Alternatively, run individually:
```bash
docker exec -it backend-facet-gestion php artisan serve
docker exec -it backend-facet-gestion php artisan queue:listen --tries=1
docker exec -it backend-facet-gestion php artisan pail --timeout=0
```

### Testing
```bash
# Run all tests
docker exec -it backend-facet-gestion composer test

# Run specific test suite
docker exec -it backend-facet-gestion php artisan test --testsuite=Unit
docker exec -it backend-facet-gestion php artisan test --testsuite=Feature

# Run specific test file
docker exec -it backend-facet-gestion php artisan test tests/Feature/ExampleTest.php

# Run with coverage
docker exec -it backend-facet-gestion php artisan test --coverage
```

Tests use an in-memory SQLite database and array-based cache/queue drivers for speed.

### Code Quality
```bash
# Format code (Laravel Pint)
docker exec -it backend-facet-gestion ./vendor/bin/pint

# Format specific files/directories
docker exec -it backend-facet-gestion ./vendor/bin/pint app/Models
docker exec -it backend-facet-gestion ./vendor/bin/pint app/Http/Controllers/UserController.php
```

### Database Operations
```bash
# Run migrations
docker exec -it backend-facet-gestion php artisan migrate

# Rollback last migration
docker exec -it backend-facet-gestion php artisan migrate:rollback

# Fresh database with seeders
docker exec -it backend-facet-gestion php artisan migrate:fresh --seed

# Create new migration
docker exec -it backend-facet-gestion php artisan make:migration create_table_name

# Check migration status
docker exec -it backend-facet-gestion php artisan migrate:status
```

### Artisan Commands
```bash
# List all artisan commands
docker exec -it backend-facet-gestion php artisan list

# Interactive REPL (Tinker)
docker exec -it backend-facet-gestion php artisan tinker

# Clear caches
docker exec -it backend-facet-gestion php artisan config:clear
docker exec -it backend-facet-gestion php artisan cache:clear
docker exec -it backend-facet-gestion php artisan route:clear
docker exec -it backend-facet-gestion php artisan view:clear

# Generate resources
docker exec -it backend-facet-gestion php artisan make:model ModelName -mfc
docker exec -it backend-facet-gestion php artisan make:controller ControllerName
docker exec -it backend-facet-gestion php artisan make:request RequestName
docker exec -it backend-facet-gestion php artisan make:resource ResourceName
docker exec -it backend-facet-gestion php artisan make:seeder SeederName
docker exec -it backend-facet-gestion php artisan make:factory FactoryName
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
- **Sanctum**: Used for API token authentication
- Default API route (`/user`) requires `auth:sanctum` middleware
- Stateful domains configured for SPA authentication (localhost, localhost:3000, 127.0.0.1)
- Personal access tokens table for API token management
- Token expiration and prefix configurable via `config/sanctum.php`

### Database Schema
Key tables:
- `users`: Standard Laravel user authentication (name, email, password)
- `personal_access_tokens`: Sanctum API tokens (polymorphic tokenable relationship)
- `sessions`: Database-backed session storage
- `password_reset_tokens`: Password reset functionality
- `cache`, `cache_locks`: Database-backed cache
- `jobs`, `job_batches`, `failed_jobs`: Queue system

### Directory Structure
- `app/Http/Controllers/`: HTTP request handlers (extends base `Controller`)
- `app/Models/`: Eloquent models (uses `HasFactory` trait)
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
- `sanctum.php`: API authentication settings
- `queue.php`: Queue connections and drivers
- `cache.php`: Cache stores (database default)

Environment variables are defined in `.env` (copy from `.env.example`).

## Development Notes

### Model Conventions
- All models should extend `Illuminate\Database\Eloquent\Model` or `Authenticatable`
- Use `HasFactory` trait for factory support
- Define `$fillable` or `$guarded` for mass assignment protection
- Use `casts()` method for attribute casting (Laravel 12 pattern, not property)

### API Development
- API routes automatically prefixed with `/api`
- Use Sanctum middleware (`auth:sanctum`) for protected endpoints
- Return JSON responses using Laravel's response helpers or API Resources
- Consider using Form Requests for validation

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
