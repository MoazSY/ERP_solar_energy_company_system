# Solar Company ERP - Technology Stack

## Core Technologies

| Layer | Technology | Version |
|-------|-----------|---------|
| Language | PHP | ^8.2 |
| Framework | Laravel | ^12.0 |
| API Auth | Laravel Sanctum | ^4.0 |
| Frontend | Livewire | ^4.1 |
| Build Tool | Vite + laravel-vite-plugin | latest |
| CSS Framework | Tailwind CSS 4.0 | @tailwindcss/vite |
| HTTP Client | Axios (via bootstrap.js) | npm |
| Database | MySQL 8.0+ / SQLite | - |
| Queue | Laravel Queue (database driver) | - |
| Cache | File driver (OTP storage) | - |

## PHP Dependencies (composer.json)

### Production
```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.10.1",
  "livewire/livewire": "^4.1"
}
```

### Development
```json
{
  "fakerphp/faker": "^1.23",
  "laravel/pail": "^1.2.2",
  "laravel/pint": "^1.24",
  "laravel/sail": "^1.41",
  "mockery/mockery": "^1.6",
  "nunomaduro/collision": "^8.6",
  "phpunit/phpunit": "^11.5.3"
}
```

## JavaScript Dependencies (package.json)

- `vite` with `laravel-vite-plugin` - asset bundling
- `@tailwindcss/vite` - CSS compilation
- `axios` - HTTP client (configured in bootstrap.js with CSRF token)
- `concurrently` - run multiple processes in dev mode

## Development Commands

### Full Stack Dev (Recommended)
```bash
composer dev
# Starts concurrently:
# - php artisan serve         (Laravel on :8000)
# - php artisan queue:listen  (background jobs)
# - php artisan pail          (real-time log streaming)
# - npm run dev               (Vite HMR on :5173)
```

### Individual Commands
```bash
php artisan serve              # Start Laravel dev server
npm run dev                    # Start Vite dev server
npm run build                  # Production asset build
php artisan queue:listen       # Process queued jobs
php artisan pail               # Stream logs in real-time
```

### Project Setup
```bash
composer setup
# Runs: composer install → .env copy → key:generate → migrate → npm install → npm run build
```

### Database
```bash
php artisan migrate                        # Run pending migrations
php artisan migrate:rollback               # Revert last batch
php artisan migrate:refresh --seed         # Reset + reseed
php artisan make:migration create_X_table  # Create new migration
php artisan tinker                         # Interactive query shell
```

### Testing
```bash
composer test                              # Clear config + run all tests
php artisan test                           # Run all PHPUnit tests
php artisan test tests/Feature/Foo.php     # Run specific test file
php artisan test --filter=testMethodName   # Run specific test method
php artisan test --coverage               # With coverage report
```

### Code Quality
```bash
./vendor/bin/pint                          # Laravel Pint code formatter
php artisan config:clear                   # Clear config cache
php artisan cache:clear                    # Clear app cache
```

## Environment Configuration (.env)

```bash
APP_NAME="Solar Company ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (SQLite default for local dev)
DB_CONNECTION=sqlite
# MySQL for production:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=solar_erp

# Cache & Queue
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Mail (OTP notifications)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@solarcompany.com
```

## Vite Configuration

```javascript
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        tailwindcss(),
    ],
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] }
    },
});
```

## Authentication: Multi-Guard Sanctum

Five separate authentication guards defined in `config/auth.php`:

| Guard | Model | Middleware |
|-------|-------|-----------|
| `admin` | `System_admin` | `check_admin` |
| `company_manager` | `Solar_company_manager` | `check_company_manager` |
| `agency_manager` | `Agency_manager` | `check_Agency_manager` |
| `employee` | `Employee` | `check_employee` |
| `customer` | `Customer` | `check_customer` |

Tokens: Laravel Sanctum personal access tokens + custom refresh token table with 7-day expiry.

## File Storage

Using Laravel's `public` disk (`storage/app/public/`), symlinked to `public/storage/`:

| Directory | Contents |
|-----------|----------|
| `SystemAdmin/` | Admin profile images |
| `CompanyManager/` | Company manager images |
| `AgencyManager/` | Agency manager images |
| `Customer/` | Customer images |
| `Employee/` | Employee images |
| `products/` | Product images |
| `Installation/complete/` | Installation completion photos |
| `SolarSystem/technician_defined/` | Surface images from technicians |
| `ConflictInvoices/` | Conflict invoice documents |

## Testing Framework

- **PHPUnit 11.5.3**: Primary test runner
- **Mockery ^1.6**: Mock objects
- **FakerPHP ^1.23**: Fake data generation
- **Directories**: `tests/Unit/` and `tests/Feature/`
- **Base class**: `tests/TestCase.php`

## External Integrations

- **ShamCash**: Syrian mobile payment gateway (via `ApiSyriaToolsService`)
- **SyriaTel Cash**: Syrian telecom payment (phone number stored per actor)
- **OSRM**: Open Source Routing Machine for delivery distance/fee calculation (`OsrmService`)
- **OTP Notifications**: Via Laravel Notifications (`SendOtpNotification`)
