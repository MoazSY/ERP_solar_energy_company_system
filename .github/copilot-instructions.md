# GitHub Copilot Instructions for Solar Company ERP

**Project**: Laravel-based ERP system for solar energy company management  
**Last Updated**: April 7, 2026  
**Architecture**: Service-Repository pattern with multi-guard authentication

---

## Quick Start Commands

```bash
# Setup project (one-time)
composer setup

# Development (watch mode with HMR + logs + queue)
composer dev

# Run tests
composer test

# Individual commands
php artisan migrate              # Run pending migrations
php artisan tinker               # Interactive shell
php artisan queue:listen         # Process background jobs
php artisan pail                 # Stream logs
```

---

## Architecture Overview

### 3-Layer Pattern: Request → Service → Repository → Model

```
Client Request
    ↓
Controller (validation, file handling)
    ↓
Service (business logic orchestration)
    ↓
Repository (data access abstraction)
    ↓
Model (ORM, schema, relationships)
    ↓
Database
```

**Key Principle**: Controllers are thin; business logic lives in Services. Repositories abstract database operations.

### Multi-Guard Authentication

5 user types with separate authentication guards:

- `admin` → System_admin model (platform admins)
- `company_manager` → Solar_company_manager (solar co. executives)
- `agency_manager` → Agency_manager (distribution agencies)
- `employee` → Employee (company staff)
- `customer` → Customer (end-users)

Each guard uses **Laravel Sanctum** for API tokens. Middleware:

- `Check_admin.php`, `Check_company_manager.php`, etc. (guard-specific)
- `Check_auth.php` (generic token validation)

Protected routes in `routes/api.php` use corresponding middleware.

---

## Directory Structure & Conventions

### Controllers (`app/Http/Controllers/`)

- **Naming**: `{UserType}Controller.php` (e.g., `System_admin.php`, `SolarCompanyManager.php`)
- **Responsibility**: Request validation, file uploads, response formatting
- **Pattern**: Inject service in constructor, delegate to service methods

**Example**:

```php
class System_admin extends Controller {
    public function __construct(SystemAdminService $service) {
        $this->service = $service;
    }

    public function register(Request $request) {
        return $this->service->register($request);
    }
}
```

### Services (`app/Services/`)

- **Naming**: `{Entity}Service.php` (e.g., `SystemAdminService.php`)
- **Responsibility**: Orchestrate business logic, call repositories, handle transactions
- **Pattern**: Inject repository interface in constructor; use dependency injection

**Example**:

```php
class SystemAdminService {
    public function __construct(SystemAdminRepositoryInterface $repo) {
        $this->repo = $repo;
    }

    public function register($request) {
        // Complex business logic here
        return $this->repo->create($data);
    }
}
```

### Repositories (`app/Repositories/`)

- **Structure**:
    - `Interfaces/` folder with repository contracts
    - Implementation files (e.g., `SystemAdminRepository.php`)
- **Naming**: `{Entity}RepositoryInterface.php` and `{Entity}Repository.php`
- **Responsibility**: Query builder, Eloquent operations, data transformations
- **Pattern**: Implement interface; return data for service consumption

**Example**:

```php
interface SystemAdminRepositoryInterface {
    public function create($data);
    public function findById($id);
}

class SystemAdminRepository implements SystemAdminRepositoryInterface { }
```

### Models (`app/Models/`)

- **Count**: 50+ models (System_admin, Customer, Product, Invoice, Payment, etc.)
- **Features**:
    - Relationships (hasMany, belongsTo, etc.)
    - Accessors/Mutators (if needed)
    - Hidden attributes for JSON responses
    - Soft deletes (where applicable)

### Routes (`routes/api.php`)

- **Structure**: Grouped by user type with middleware
- **Pattern**:
    ```php
    Route::middleware('Check_admin')->group(function () {
        Route::post('/admin/register', [System_admin::class, 'register']);
    });
    ```

### Migrations (`database/migrations/`)

- **Naming**: `2026_04_07_000000_create_{table}_table.php`
- **Pattern**: Use timestamps, foreign keys, indexes

---

## Database Design Patterns

### Foreign Key Relationships

- Always add `->constrained()` for automatic foreign key naming
- Use `->cascadeOnDelete()` or `->restrictOnDelete()` as appropriate
- Example:
    ```php
    $table->foreignId('company_id')->constrained('solar_companies')->cascadeOnDelete();
    ```

### Common Entity Relationships

- **System_admin** → manages Solar_company, Agency, Employee
- **Solar_company** → has Products, Employees, Agencies
- **Customer** → places Orders, receives Invoices, provides Feedback
- **Product** → has Warranties, Components, Techicians
- **Order** → generates Invoice, Delivery, Payment

---

## Common Development Tasks

### Adding a New API Endpoint

1. **Create/Update Controller** (`app/Http/Controllers/{Entity}.php`):

    ```php
    public function store(Request $request) {
        $validated = $request->validate([...]);
        return $this->service->create($validated);
    }
    ```

2. **Create/Update Service** (`app/Services/{Entity}Service.php`):

    ```php
    public function create($data) {
        // Business logic
        return $this->repo->create($data);
    }
    ```

3. **Create/Update Repository** (`app/Repositories/{Entity}Repository.php`):

    ```php
    public function create($data) {
        return {Model}::create($data);
    }
    ```

4. **Add Route** (`routes/api.php`):

    ```php
    Route::middleware('Check_{guard}')->post('/resource', [{Entity}::class, 'store']);
    ```

5. **Add Tests** (`tests/Feature/{Entity}Test.php`):
    ```php
    public function test_store() {
        $response = $this->actingAs($user)->postJson('/api/resource', [...]);
        $this->assertDatabaseHas('table', [...]);
    }
    ```

### Adding a New Model & Migration

```bash
php artisan make:model {ModelName} -m
```

Edit migration:

```php
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('column_name');
    $table->foreignId('related_id')->constrained();
    $table->timestamps();
    $table->softDeletes(); // if needed
});
```

Edit model:

```php
class Model extends Model {
    protected $fillable = ['column1', 'column2'];

    public function related() {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

### Running Migrations

```bash
php artisan migrate              # Run all pending migrations
php artisan migrate:rollback     # Undo last migration batch
php artisan migrate:refresh      # Drop all tables and migrate fresh
php artisan migrate:reset        # Rollback all migrations
```

---

## Testing Structure

### Test Locations

- **Unit Tests**: `tests/Unit/` — Test individual services/repositories
- **Feature Tests**: `tests/Feature/` — Test API endpoints end-to-end

### PHPUnit Configuration

- See `phpunit.xml` for test database config
- Tests use in-memory SQLite database by default
- `TestCase.php` provides base test utilities

### Running Tests

```bash
composer test           # Run all tests
php artisan test       # Run with detailed output
php artisan test --filter=TestName  # Run specific test
```

### Example Test

```php
class SystemAdminTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->seed();  // Seed test database
    }

    public function test_admin_registration() {
        $response = $this->postJson('/api/admin/register', [
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('system_admins', ['email' => 'admin@test.com']);
    }
}
```

---

## Authentication Flow (Sanctum + Multi-Guard)

1. **User Login**: POST `/api/{user-type}/login`
    - Validates credentials
    - Generates API token via `createToken()`
    - Returns token in response

2. **Token Storage**: Client stores token (usually in localStorage/header)

3. **Protected Requests**: Client sends `Authorization: Bearer {token}` header

4. **Middleware Verification** (`Check_{guard}.php`):

    ```php
    $user = Auth::guard('customer')->user();
    if (!$user) abort(401, 'Unauthorized');
    ```

5. **Token Expiration**: Managed via Sanctum config (see `config/sanctum.php`)

---

## Configuration Files

### Key Config Files

- `config/app.php` — App name, timezone, providers
- `config/auth.php` — Guard definitions (admin, company_manager, etc.)
- `config/database.php` — Database connection config
- `config/sanctum.php` — API token expiration settings
- `config/mail.php` — Email driver (for OTP notifications)
- `.env` — Environment variables (create from `.env.example`)

### Build Configuration

- `vite.config.js` — Frontend bundler config (Vite 7)
- `package.json` — NPM scripts and dependencies
- `composer.json` — PHP dependencies and Composer scripts

---

## Conventions & Best Practices

### Naming

- **Models**: PascalCase (e.g., `System_admin`, `SolarCompany`) ✓
- **Tables**: snake_case_plural (e.g., `system_admins`, `solar_companies`)
- **Methods**: camelCase (e.g., `getUserProfile`, `createOrder`)
- **Routes**: kebab-case (e.g., `/api/admin/register`, `/api/order/list`)

### Response Format

All JSON responses should include:

```json
{
  "status": "success|error",
  "message": "Descriptive message",
  "data": {...} or null,
  "errors": {...} or null
}
```

### Error Handling

- Use HTTP status codes correctly (200, 201, 400, 401, 403, 404, 422, 500)
- Include validation errors in response:
    ```php
    return response()->json(['errors' => $request->errors()], 422);
    ```
- Log exceptions: `Log::error('message', ['exception' => $e])`

### Code Organization

- Keep controllers thin (<50 lines per method)
- Keep services focused (single responsibility)
- Use repositories to abstract database queries
- Use dependency injection throughout
- Add type hints for all method parameters and returns

---

## Debugging & Development

### Interactive Shell (Tinker)

```bash
php artisan tinker
>>> $admin = System_admin::first();
>>> $admin->email;
>>> $admin->fresh();  # Refresh from DB
```

### Real-Time Logging

```bash
php artisan pail   # Stream application logs
php artisan pail --filter=System_admin  # Filter by keyword
```

### Queue Processing

Background jobs (OTP sending, invoice generation, etc.):

```bash
php artisan queue:listen --retry=1              # Process jobs
php artisan queue:failed                         # View failed jobs
php artisan queue:retry {job-id}                # Retry specific job
```

### Database Inspection

```bash
php artisan tinker
>>> DB::table('system_admins')->count();
>>> DB::select("SELECT * FROM customers LIMIT 5");
>>> System_admin::with('related')->get();
```

---

## Project Stats & Metrics

| Metric                    | Count      |
| ------------------------- | ---------- |
| **Total Models**          | 50+        |
| **Migrations**            | 56+        |
| **Controllers**           | 10+        |
| **Services**              | 10+        |
| **Repositories**          | 10+        |
| **Authentication Guards** | 5          |
| **API Endpoints**         | 40+ (est.) |

---

## Important Entry Points for New Features

1. **Add New User Type**:
    - Create Model (e.g., `NewUserType.php`) with Authenticatable trait
    - Add guard in `config/auth.php`
    - Create Middleware (`Check_newusertype.php`)
    - Create Controller, Service, Repository

2. **Add Domain Entity** (non-user):
    - Create Model and Migration
    - Create Repository & Interface
    - Create Service
    - Create Controller with CRUD endpoints
    - Add Routes

3. **Add Background Job**:
    - Create Job class: `php artisan make:job JobName`
    - Dispatch from Service: `JobName::dispatch($data)`
    - Process via `php artisan queue:listen`

4. **Add Notification**:
    - Create Notification: `php artisan make:notification NotificationName`
    - Send from Service: `Notification::send($user, new NotificationName())`

---

## Related Documentation

- [PROJECT_ANALYSIS.md](PROJECT_ANALYSIS.md) — Detailed architecture guide created by AI agent
- [Laravel Framework Docs](https://laravel.com/docs) — Official Laravel documentation
- `.env.example` — Environment variable template
- `config/` directory — Application configuration files

---

## AI Agent Mode

This workspace is optimized for AI-assisted development. When working with AI:

1. **Always reference this guide** when discussing architecture or conventions
2. **Mention the pattern** (Service-Repository-Model) when proposing changes
3. **Test your features** with `composer test` before committing
4. **Follow the naming conventions** to maintain consistency across 50+ models
5. **Use middleware properly** for guard-specific authentication
6. **Leverage existing patterns** — look at SystemAdminService, SystemAdminRepository as exemplars

---

## Next Steps for Team

- [ ] Review multi-guard authentication implementation
- [ ] Establish code review checklist (based on patterns here)
- [ ] Create development workflow documentation
- [ ] Set up CI/CD pipeline
- [ ] Add API documentation (OpenAPI/Swagger)
- [ ] Configure environment-specific deployments
