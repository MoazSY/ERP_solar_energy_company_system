# Solar Company ERP - Project Architecture & Development Guide

_Last Updated: April 7, 2026_

---

## 1. BUILD & TEST COMMANDS

### NPM Scripts (package.json)

```bash
npm run dev          # Start Vite dev server (watch mode)
npm run build        # Production build with Vite
```

### Composer/Artisan Scripts

```bash
composer setup       # Complete project setup:
                     # - composer install
                     # - Generate .env file
                     # - Generate APP_KEY
                     # - Run migrations
                     # - npm install & npm run build

composer dev         # Start development environment (concurrently):
                     # - php artisan serve (Laravel server on port 8000)
                     # - php artisan queue:listen (Background jobs, retry 1x, no timeout)
                     # - php artisan pail (Real-time log streaming)
                     # - npm run dev (Vite hot reload)
                     # Runs 4 processes with color-coded output

composer test        # Run test suite:
                     # - Clear config cache
                     # - Execute PHPUnit tests
```

### Individual Artisan Commands (Commonly Used)

```bash
php artisan serve                          # Start development server (port 8000)
php artisan migrate                        # Run pending database migrations
php artisan tinker                         # Interactive shell for testing code
php artisan queue:listen                   # Process queued jobs
php artisan pail                           # Stream application logs in real-time
php artisan config:clear                   # Clear cached configuration
php artisan cache:clear                    # Clear application cache
php artisan key:generate                   # Generate APP_KEY for encryption
php artisan make:migration create_X_table  # Create new migration
php artisan make:model ModelName           # Create new model with migration
```

### Testing Framework

- **PHPUnit 11.5.3**: Unit & Feature tests
- **Mockery**: Mocking/stubbing
- **Test Directories**: `tests/Unit/` and `tests/Feature/`
- **Run Tests**: `composer test`

---

## 2. ARCHITECTURE PATTERNS & DIRECTORY ORGANIZATION

### Design Patterns in Use

#### **Service-Repository Pattern**

This project implements a 3-layer architecture:

```
Request → Controller → Service → Repository → Model → Database
```

**Example Flow (Admin Registration):**

1. **Controller** (`System_admin.php`): Validates incoming request, handles file uploads
2. **Service** (`SystemAdminService.php`): Orchestrates business logic (token generation, OTP verification)
3. **Repository** (`SystemAdminRepository.php`): Executes database operations
4. **Model** (`System_admin.php`): Defines schema, relationships, accessors/mutators

#### **Repository Pattern with Interfaces**

All repositories implement interfaces for loose coupling:

```php
// Interface defines contract
interface SystemAdminRepositoryInterface {
    public function Create($request, $imagepath, $data);
    public function Admin_profile($admin_id);
}

// Implementation
class SystemAdminRepository implements SystemAdminRepositoryInterface { }

// Dependency Injection in Service
public function __construct(SystemAdminRepositoryInterface $repo) { }
```

#### **Multi-Guard Authentication**

Custom authentication guards for different user types:

- `admin` → System_admin model (platform administrators)
- `company_manager` → Solar_company_manager model (solar company executives)
- `agency_manager` → Agency_manager model (distribution agencies)
- `employee` → Employee model (company employees)
- `customer` → Customer model (end customers)

Each guard has corresponding middleware: `Check_admin`, `Check_company_manager`, etc.

### Directory Structure & Conventions

```
app/
├── Console/              [No custom Artisan commands yet - ready for expansion]
├── Http/
│   ├── Controllers/      [API endpoint handlers - naming: User type + Controller]
│   │   ├── System_admin.php
│   │   ├── SolarCompanyManager.php
│   │   └── ...
│   ├── Middleware/       [Authentication & authorization]
│   │   ├── Check_admin.php              [Validate admin token & guard]
│   │   ├── Check_company_manager.php
│   │   ├── Check_auth.php               [Generic token validation]
│   │   └── ...
│   └── Requests/         [Form request validation - currently minimal]
│       └── StoreUserRequest.php
├── Models/               [50+ models for domain entities]
│   ├── System_admin.php       [Authenticatable]
│   ├── Customer.php           [Authenticatable]
│   ├── Solar_company.php      [Non-authenticatable domain model]
│   ├── Agency.php
│   └── ... [Product, Invoice, Order, Payment, Report, etc.]
├── Repositories/         [Data access layer - all have interfaces]
│   ├── Interfaces/       [Abstract contracts]
│   │   ├── SystemAdminRepositoryInterface.php
│   │   ├── CustomerRepositoryInterface.php
│   │   └── ...
│   ├── SystemAdminRepository.php
│   ├── CustomerRepository.php
│   └── ... [One repository per major entity]
├── Services/             [Business logic layer]
│   ├── SystemAdminService.php
│   ├── CustomerService.php
│   ├── OtpService.php           [OTP sendable & verification]
│   └── ...
├── Notifications/        [Laravel Notification classes]
├── Providers/            [Service provider registrations]
│   └── AppServiceProvider.php
└── Rules/                [Custom validation rules]

database/
├── migrations/           [56+ migration files]
│   ├── create_system_admins_table.php
│   ├── create_solar_companies_table.php
│   └── ... [Timestamp-ordered]
├── seeders/              [Database seeding]
├── factories/            [Model factories for testing]

routes/
├── api.php              [All API routes - no HTTP verb grouping yet]
├── web.php              [Web routes (minimal)]
└── console.php          [Artisan commands]

resources/
├── css/                 [Tailwind CSS entry point]
├── js/                  [JavaScript/Livewire components]
└── views/               [Blade templates - mostly unused in API]

tests/
├── Unit/                [Business logic tests]
├── Feature/             [API endpoint tests]
└── TestCase.php         [Base test class]

public/
└── index.php            [Laravel entry point for web requests]
```

### Key Conventions

| Aspect              | Convention                                 | Example                                                       |
| ------------------- | ------------------------------------------ | ------------------------------------------------------------- |
| **Models**          | PascalCase, Authenticatable only for users | `System_admin`, `Customer`, `Product`                         |
| **Controllers**     | Named after entity type                    | `System_admin.php`, `SolarCompanyManager.php`                 |
| **Repositories**    | Entity + "Repository", with Interface pair | `CustomerRepository` implements `CustomerRepositoryInterface` |
| **Services**        | Entity + "Service"                         | `CustomerService`, `OtpService`                               |
| **Middleware**      | `Check_` prefix with entity type           | `Check_admin`, `Check_company_manager`                        |
| **Tables**          | Snake_case, plural for collections         | `system_admins`, `solar_companies`                            |
| **Migration Names** | Year_Month_Day_HHmmss_description          | `2026_02_25_070948_create_system_admins_table.php`            |

---

## 3. KEY SERVICES, REPOSITORIES & MODELS (DESIGN PATTERN EXEMPLARS)

### Exemplar 1: System Admin Authentication Flow

**Model** ([System_admin.php](app/Models/System_admin.php#L1))

```php
// Authenticatable with API tokens and polymorphic relationships
class System_admin extends Authenticatable {
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = ['first_name', 'last_name', 'email', 'password', ...];

    // Polymorphic refresh tokens relationship
    public function refreshTokens() {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }

    // Domain relationships
    public function reports(): HasMany { ... }
    public function commisionPolices(): HasMany { ... }
}
```

**Repository Interface** ([SystemAdminRepositoryInterface.php](app/Repositories/SystemAdminRepositoryInterface.php#L1))

```php
interface SystemAdminRepositoryInterface {
    public function Create($request, $imagepath, $data);
    public function Admin_profile($admin_id);
    public function add_governorates($request);
    // ... defines all data operations
}
```

**Repository Implementation** ([SystemAdminRepository.php](app/Repositories/SystemAdminRepository.php#L1))

```php
class SystemAdminRepository implements SystemAdminRepositoryInterface {
    public function Create($request, $imagepath, $data) {
        return System_admin::create([
            'first_name' => $request->first_name,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'image' => $imagepath,
            // ...
        ]);
    }

    public function add_governorates($request) {
        return Governorates::create(['name' => $request->name]);
    }
}
```

**Service** ([SystemAdminService.php](app/Services/SystemAdminService.php#L1))

```php
class SystemAdminService {
    protected $SystemAdminRepositoryInterface;
    protected $tokenRepositoryInterface;

    public function register($request, $data) {
        // File upload handling
        if($request->hasFile('image')) {
            $imagepath = $request->file('image')->storeAs('SystemAdmin/images', ...);
        }

        // Delegate to repository
        $admin = $this->SystemAdminRepositoryInterface->Create($request, $imagepath, $data);

        // Generate tokens via Sanctum
        $token = $admin->createToken('authToken')->plainTextToken;

        // Track token expiration in custom table
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);

        return ['admin' => $admin, 'token' => $token, 'refresh_token' => $refresh_token];
    }
}
```

**Controller** ([System_admin.php Controller](app/Http/Controllers/System_admin.php#L1))

```php
class System_admin extends Controller {
    protected $SystemAdminService;

    public function Register(Request $request) {
        // Validation with custom rules
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|alpha_num|min:8',
            'phoneNumber' => 'required|regex:/^09\d{8}$/',
        ]);

        // OTP verification check (via cache)
        $cached_phone = Cache::get('otp_' . $internalPhone);
        if (!$cached_phone || $cached_phone['status'] !== 'verified') {
            return response()->json(['message' => 'OTP not verified'], 400);
        }

        // Unique constraint validation
        $uniqueValidator = Validator::make($request->all(), [
            'email' => 'required|unique:system_admins',
            'phoneNumber' => 'required|unique:system_admins',
        ]);

        // Delegate to service
        $result = $this->SystemAdminService->register($request, $data);

        return response()->json(['admin' => $result['admin'], 'token' => $result['token']]);
    }
}
```

### Exemplar 2: Multi-Entity Relationship (Agency Model)

**Model** ([Agency.php](app/Models/Agency.php#L1))

```php
class Agency extends Model {
    protected $fillable = [
        'agency_manager_id',
        'agency_name',
        'agency_status',
        'verified_at',
        // ... domain fields
    ];

    // Belongs to relationship
    public function agencyManager(): BelongsTo {
        return $this->belongsTo(Agency_manager::class, 'agency_manager_id');
    }

    // Has many relationships
    public function conflictInvoices(): HasMany {
        return $this->hasMany(Conflict_invoice::class, 'agency_id');
    }
}
```

This exemplifies:

- Clear relationship definition with proper key naming
- Semantic methods (agencyManager, conflictInvoices)
- Type-hinted return types

### Exemplar 3: Token Management (Custom Extension)

**Model** ([Refresh_token.php](app/Models/Refresh_token.php))

```php
class Refresh_token extends Model {
    protected $fillable = ['user_table_id', 'user_table_type', 'token', 'expires_at'];

    // Polymorphic relationship - works with any user type
    public function user_table() {
        return $this->morphTo();
    }
}
```

**Repository** ([TokenRepository.php](app/Repositories/TokenRepository.php))

```php
class TokenRepository implements TokenRepositoryInterface {
    public function Add_refresh_token($token) {
        $user = Auth::user();
        return $user->refreshTokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
```

This pattern:

- Extends Laravel Sanctum with custom token tracking
- Uses polymorphic relationships (same table for all user types)
- Enables token expiration and refresh rotation

### Service Inventory

| Service                        | Purpose                                 | Key Methods                                           |
| ------------------------------ | --------------------------------------- | ----------------------------------------------------- |
| **SystemAdminService**         | Admin account management                | `register()`, `Admin_profile()`, `add_governorates()` |
| **CustomerService**            | Customer account & order management     | Similar pattern                                       |
| **EmployeeService**            | Employee lifecycle management           | Similar pattern                                       |
| **SolarCompanyManagerService** | Company registration & profile          | Similar pattern                                       |
| **AgencyManagerService**       | Agency management                       | Similar pattern                                       |
| **OtpService**                 | OTP generation & verification via cache | `sendOtp()`, `verifyOtp()`                            |

### Repository Inventory (All Follow Same Pattern)

Each major entity has:

1. **Interface** - Contract definition
2. **Repository** - Concrete implementation
3. **Service** - Business logic orchestration

Entities: `SystemAdmin`, `Customer`, `Employee`, `SolarCompanyManager`, `AgencyManager`, `Token`

---

## 4. DEVELOPMENT ENVIRONMENT SETUP REQUIREMENTS

### Prerequisites

- **PHP**: 8.2 or higher
- **Node.js**: 18+ (for Vite & npm)
- **Composer**: 2.0+ (PHP dependency manager)
- **Database**: MySQL 8.0+ OR SQLite (default)
- **Git**: For version control

### Initial Setup (Quick Start)

```bash
# 1. Clone repository (assumed already done)
cd solar_energy_company

# 2. Run composer setup script (handles everything below)
composer setup

# That's it! This runs:
# - composer install
# - Copies .env.example → .env
# - Generates APP_KEY
# - Runs migrations
# - npm install & npm run build
```

### Manual Setup (If Not Using Script)

```bash
# Install PHP dependencies
composer install

# Setup environment
cp .env.example .env

# Generate encryption key
php artisan key:generate

# Setup database (creates SQLite if using default)
php artisan migrate

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### Environment Configuration (.env)

```bash
APP_NAME="Solar Company ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (SQLite default, change to mysql for production)
DB_CONNECTION=sqlite
# For MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=solar_erp
# DB_USERNAME=root
# DB_PASSWORD=

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Mail (for OTP notifications - configure to your provider)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@solarcompany.com

# Sanctum
SANCTUM_ENCRYPTION_KEY=your-key-here
```

### Development Workflow

**Start Full Stack (Recommended)**

```bash
# Runs all services concurrently with hot reload
composer dev

# Opens these automatically:
# - Backend: http://localhost:8000
# - Vite: http://localhost:5173 (for HMR)
# - Real-time logs in terminal
# - Queue listener for background jobs
```

**Or, Run Services Individually**

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (from separate terminal)
npm run dev

# Terminal 3: Queue listener (for background jobs)
php artisan queue:listen

# Terminal 4: Log streaming
php artisan pail
```

### Database Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration batch
php artisan migrate:rollback

# Reset all migrations (caution: deletes data)
php artisan migrate:reset

# Refresh schema and reseed
php artisan migrate:refresh --seed
```

### Testing Environment Setup

```bash
# Run all tests
composer test

# Run specific test
php artisan test tests/Feature/AuthenticationTest.php

# Run with coverage
php artisan test --coverage
```

---

## 5. BUILD CONFIGURATION FILES

### Vite Configuration ([vite.config.js](vite.config.js))

```javascript
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        // Laravel integration - watches for manifest changes
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true, // Reload on Blade changes
        }),
        // Tailwind CSS compilation
        tailwindcss(),
    ],
    server: {
        // Don't watch compiled Blade views (prevents recompile loops)
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
```

**What This Does:**

- Processes CSS/JS entry points from `resources/`
- Hot Module Replacement (HMR) for instant browser updates
- Tailwind CSS compilation from utility classes
- Manifest generation for Laravel asset versioning

### Tailwind CSS Configuration (Implicit)

Uses Tailwind CSS 4.0 with `@tailwindcss/vite` plugin:

```css
/* resources/css/app.css */
@import "tailwindcss"; /* Auto-imports all Tailwind utilities */
```

### Laravel Configuration Files

**app.php** - Application metadata & service providers
**database.php** - Connection definitions (SQLite default, MySQL available)
**auth.php** - Guard & provider configurations
**sanctum.php** - Token-based auth settings

---

## 6. OUTPUT & ASSET PIPELINE

### Build Outputs

```
public/
├── build/                  # Production-compiled assets
│   ├── app.css
│   └── app.js
├── storage/               # User uploads
│   ├── SystemAdmin/images/
│   ├── admin/images/
│   └── ...
├── index.php             # Entry point
└── robots.txt
```

### Asset Compilation

**Development**

```bash
npm run dev
# Triggers Vite dev server at http://localhost:5173
# HMR enabled - changes reflect instantly in browser
```

**Production**

```bash
npm run build
# Outputs minified, versioned assets to public/build/
# Generates manifest.json for Laravel's asset() helper
```

---

## 7. API AUTHENTICATION & AUTHORIZATION FLOW

### Multi-Guard System

```
Request (Header: Authorization: Bearer TOKEN)
    ↓
Middleware Check_admin / Check_company_manager / etc.
    ↓
Validate Token Expiration
    ↓
Check Guard (e.g., Auth::guard('admin')->check())
    ↓
Authenticate User
    ↓
Allow Request Access
```

### Token Lifecycle

1. **Generation**: `$admin->createToken('authToken')->plainTextToken` (Sanctum)
2. **Tracking**: Stored in custom `refresh_tokens` table with expiration
3. **Validation**: Middleware checks token exists and not expired
4. **Refresh**: Custom `refresh_token` generated for token rotation
5. **Expiration**: 7-day default (configurable per user type)

### Route Protection Example

```php
// api.php
Route::post('admin_register', [System_admin::class, 'Register']);  // Public

Route::middleware('check_admin')->group(function () {
    Route::get('Admin_profile', [System_admin::class, 'Admin_profile']);
    Route::post('update_profile', [System_admin::class, 'update_profile']);
    // ... protected routes
});
```

---

## 8. KEY ENTRY POINTS FOR AI AGENT PRODUCTIVITY

### Request Handling Chain

1. **Route Entry**: `routes/api.php` - Find endpoint definition
2. **Controller**: Look for class matching route (e.g., `System_admin.php`)
3. **Service**: Find corresponding service class (e.g., `SystemAdminService.php`)
4. **Repository**: Find data access logic (e.g., `SystemAdminRepository.php`)
5. **Model**: Understand domain entity structure

### Modification Patterns

- **New Feature**: Create Controller → Service → Repository Interface → Repository Implementation
- **New Entity**: Model → Migration → Repository pair → Service → Controller
- **API Route**: Add to `routes/api.php`, optionally protect with middleware
- **Validation**: Use Laravel Form Requests or inline Validators in controller

### Database Interaction

- All queries flow through Repository pattern
- Models defined in `app/Models/` with relationships
- Migrations in `database/migrations/` (timestamp-ordered)
- Seeders in `database/seeders/` for test data

### Authentication

- Use `Auth::guard('admin')` (or other guard) to get current user
- Protect routes with `middleware('check_admin')` pattern
- Generate tokens: `$user->createToken('name')->plainTextToken`

---

## 9. COMMON DEVELOPMENT TASKS

### Add New API Endpoint

1. Create/update method in controller
2. Add route in `routes/api.php`
3. Create service method for business logic
4. Create or update repository method
5. Set authentication guard in middleware if needed

### Add New Model/Entity

```bash
php artisan make:model ModelName -m  # Creates model + migration
```

Then:

1. Define relationships in model
2. Add repository interface & implementation
3. Create service class
4. Create controller class
5. Add routes in `routes/api.php`

### Work with Database

```bash
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Revert
php artisan tinker              # Interactive shell for testing queries
```

### Debug Issues

```bash
php artisan pail               # Real-time logs
php artisan tinker             # Test code interactively
dd($variable)                  # Dump & die (prints then stops)
Log::info('message')           # Log to file/console
```

---

## 10. TESTING STRATEGY

### Test Structure

- **Unit Tests**: `tests/Unit/` - Logic tests without DB
- **Feature Tests**: `tests/Feature/` - API endpoint tests with DB

### Running Tests

```bash
composer test                                    # All tests
php artisan test tests/Feature/LoginTest.php    # Specific file
php artisan test --filter=testMethodName         # Specific test method
php artisan test --coverage                     # With coverage report
```

### Test Example

```php
// tests/Feature/AdminRegistrationTest.php
public function test_admin_can_register_with_otp() {
    // Setup: Seed OTP in cache
    Cache::put('otp_email@example.com', ['status' => 'verified']);

    // Action: Send registration request
    $response = $this->postJson('/api/admin_register', [
        'first_name' => 'John',
        'email' => 'email@example.com',
        // ...
    ]);

    // Assert: Check response
    $response->assertStatus(200);
    $this->assertDatabaseHas('system_admins', [...]);
}
```

---

## 11. PROJECT STATS

| Metric                    | Value                                                          |
| ------------------------- | -------------------------------------------------------------- |
| **Models**                | 50+ domain entities                                            |
| **Migrations**            | 56+ database migrations                                        |
| **Services**              | 6 main services                                                |
| **Repositories**          | 6 repositories (with interfaces)                               |
| **API Routes**            | 40+ endpoints (RESTful via Sanctum)                            |
| **Authentication Guards** | 5 (admin, company_manager, agency_manager, employee, customer) |
| **Middleware**            | 6 custom auth middleware                                       |
| **PHP Version**           | 8.2+ (type hints, attributes)                                  |
| **Laravel Version**       | 12.0 (latest LTS)                                              |

---

## 12. NEXT STEPS FOR AGENT DEVELOPMENT

1. **Run Setup**: `composer setup` to get fully functional environment
2. **Explore API**: Start with `routes/api.php` to understand endpoints
3. **Trace Flow**: Pick any endpoint and trace Controller → Service → Repository → Model
4. **Check Tests**: Review existing tests as examples of expected behavior
5. **Start Development**: Use patterns exemplified by existing code
6. **Use composer dev**: Keep this running - provides logs + HMR + queue processing

---

**Notes for Maintenance:**

- Always create migrations for DB changes (never modify schema directly)
- Test new endpoints before pushing to production
- Keep OTP tokens in cache with 5-10 minute expiration
- Monitor queue listener for job failures
- Use `php artisan pail` to track errors in real-time
