# Technology Stack

## Core Framework & Language
- **PHP 8.2+** (required: `^8.2`)
- **Laravel 12.x** (`laravel/framework: ^12.0`)
- **Laravel Sanctum 4.x** — API token authentication (`laravel/sanctum: ^4.0`)
- **Livewire 4.x** — included but minimal use in this API-first project (`livewire/livewire: ^4.1`)

## Database
- MySQL (configured via `config/database.php`, `.env` `DB_*` variables)
- 60+ migrations covering full domain schema
- Eloquent ORM with polymorphic relationships throughout

## Authentication
- Laravel Sanctum (token-based for all API guards)
- 6 custom guards: `admin`, `company_manager`, `agency_manager`, `employee`, `customer`, `api`
- Refresh token model (`Refresh_token`) for token rotation
- OTP-based login flow via `OtpService` + `SendOtpNotification`

## External Services
- **OSRM** (Open Source Routing Machine) — HTTP calls via `OsrmService` for delivery distance/routing
- **ApiSyria** — Syrian payment gateway service (`ApiSyriaService`, `ApiSyriaToolsService`) supporting Syriatel Cash and Shamcash

## HTTP Client
- **Guzzle HTTP** (`guzzlehttp/guzzle`) — used in `OsrmService` and `ApiSyriaService` for external API calls

## Dev Dependencies
- `laravel/pint` — PHP code style fixer
- `laravel/sail` — Docker dev environment
- `laravel/pail` — Log viewer
- `fakerphp/faker` — Seeder data generation
- `phpunit/phpunit ^11.5` — Testing
- `mockery/mockery` — Mock objects in tests
- `nunomaduro/collision` — CLI error rendering

## Frontend (minimal)
- Vite (`vite.config.js`) for asset bundling
- `resources/css/app.css` + `resources/js/app.js` — basic stubs (project is API-first)

## Key Development Commands
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Development server (all-in-one: server + queue + logs + vite)
composer run dev

# Run tests
composer run test
# or
php artisan test

# Code style
./vendor/bin/pint

# Artisan REPL
php artisan tinker
```

## File Storage
- `storage/app/public/` with subdirectories per actor:
  - `AgencyManager/`, `CompanyManager/`, `Customer/`, `Employee/`, `SystemAdmin/`
  - `products/`, `Installation/`, `SolarSystem/`, `ConflictInvoices/`
- Accessed via `asset('storage/' . $path)` pattern in services

## Configuration Files of Note
- `config/auth.php` — all guard and provider definitions
- `config/sanctum.php` — token expiry settings
- `config/services.php` — external service credentials
- `.env` — `DB_*`, `OSRM_*`, `APIsyria_*` environment variables expected
