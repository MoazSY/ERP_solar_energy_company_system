# Solar Company ERP - Project Structure

## Directory Layout

```
solar_energy_company/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # API endpoint handlers (one per actor type)
│   │   ├── Middleware/        # Auth & authorization guards
│   │   └── Requests/          # Form request validators (minimal, mostly inline)
│   ├── Models/                # 50+ Eloquent models for all domain entities
│   ├── Repositories/          # Data access layer (interface + implementation pairs)
│   ├── Services/              # Business logic layer
│   ├── Notifications/         # Laravel Notification classes (OTP)
│   ├── Providers/             # AppServiceProvider (DI bindings)
│   └── Rules/                 # Custom validation rules
├── database/
│   ├── migrations/            # 56+ timestamp-ordered migration files
│   ├── seeders/               # CoreProjectSeeder and domain seeders
│   └── factories/             # Model factories for testing
├── routes/
│   ├── api.php                # All API routes grouped by middleware/actor
│   ├── web.php                # Minimal web routes
│   └── console.php            # Artisan schedule commands
├── resources/
│   ├── css/app.css            # Tailwind CSS entry point
│   ├── js/                    # app.js + bootstrap.js (Axios, Livewire)
│   └── views/                 # Blade templates (welcome page only)
├── storage/app/public/        # User-uploaded files (images, documents)
│   ├── AgencyManager/
│   ├── CompanyManager/
│   ├── Customer/
│   ├── Employee/
│   ├── Installation/
│   ├── products/
│   ├── SolarSystem/
│   └── SystemAdmin/
├── config/                    # Laravel configuration files
├── tests/
│   ├── Feature/               # API endpoint tests
│   └── Unit/                  # Business logic unit tests
└── .amazonq/rules/memory-bank/ # Project documentation
```

## Core Architecture: Service-Repository Pattern

```
HTTP Request
    ↓
routes/api.php  (route definition + middleware)
    ↓
Controller  (validation, file handling, HTTP response)
    ↓
Service  (business logic, orchestration)
    ↓
Repository  (database queries via Eloquent)
    ↓
Model  (schema, relationships, accessors)
    ↓
Database
```

## Controllers (app/Http/Controllers/)

| File | Actor | Responsibility |
|------|-------|----------------|
| `System_admin.php` | System Admin | Platform admin, geographic data, subscription policies |
| `SolarCompanyManager.php` | Company Manager | Company ops, ordering, invoicing, tasks |
| `AgencyManagerController.php` | Agency Manager | Product catalog, discounts, deliveries |
| `EmployeeController.php` | Employee | Delivery/installation tasks, inventory |
| `CustomerController.php` | Customer | Requests, orders, subscriptions, ratings |
| `OtpController.php` | All | Authentication: OTP, login, logout, token refresh |
| `ApiSyriaToolsController.php` | All | ShamCash payment gateway integration |

## Middleware (app/Http/Middleware/)

| Middleware | Purpose |
|-----------|---------|
| `Check_admin` | Validates admin Sanctum token + guard |
| `Check_company_manager` | Validates company manager token |
| `Check_company_active` | Ensures company is active/approved |
| `Check_company_manager_active` | Ensures manager account is active |
| `Check_company_subscription` | Validates active subscription |
| `Check_Agency_manager` | Validates agency manager token |
| `Check_agency_active` | Ensures agency is active |
| `Check_agency_manager_active` | Ensures agency manager is active |
| `Check_agency_subscription` | Validates agency subscription |
| `Check_employee` | Validates employee token |
| `Check_customer` | Validates customer token |
| `Check_auth` | Generic token validation |

## Services (app/Services/)

| Service | Key Responsibilities |
|---------|---------------------|
| `SystemAdminService` | Admin registration, geographic CRUD, subscription policies |
| `SolarCompanyManagerService` | Company management, invoicing, task assignment, profits |
| `AgencyManagerService` | Agency products, discounts, purchase invoices, delivery |
| `EmployeeService` | Delivery/installation task workflow, inventory management |
| `CustomerService` | Solar requests, subscriptions, orders, maintenance |
| `OtpService` | OTP generation and verification via cache |
| `ApiSyriaToolsService` | ShamCash API calls |
| `ApiSyriaService` | Syria payment gateway abstraction |
| `OsrmService` | Route/delivery calculation (OSRM integration) |

## Repositories (app/Repositories/)

Each major actor has a paired interface + implementation:
- `SystemAdminRepository` / `SystemAdminRepositoryInterface`
- `SolarCompanyManagerRepository` / `SolarCompanyManagerRepositoryInterface`
- `AgencyManagerRepository` / `AgencyManagerRepositoryInterface`
- `EmployeeRepository` / `EmployeeRepositoryInterface`
- `CustomerRepository` / `CustomerRepositoryInterface`
- `TokenRepository` / `TokenRepositoryInterface`

## Key Models (app/Models/)

### Authenticatable User Models
- `System_admin`, `Solar_company_manager`, `Agency_manager`, `Employee`, `Customer`, `User`

### Company & Agency
- `Solar_company`, `Agency`

### Geographic
- `Governorates`, `Areas`, `Neighborhood`, `Address`

### Products & Inventory
- `Products`, `Batteries`, `Solar_panal`, `Inverters`, `Items`, `Consumables`

### Orders & Commerce
- `Order_list`, `Items`, `Input_output_request`, `Purchase_invoice`, `Conflict_invoice`
- `Payment`, `Payment_transactions`, `Deliveries`, `Delivery_rules`

### Solar System & Requests
- `Request_solar_system`, `Technical_inspection_request`, `Metainence_request`
- `Electrical_device`, `Customer_electrical_device_characteristic`

### Project & Tasks
- `Project_task`, `Task_assistants`, `Project_warranties`, `Component_warranties`
- `Company_protofolio`, `Product_techicians`

### Subscriptions & Offers
- `Offers`, `Subscribe_offer`, `Subscribe_polices`, `Custom_subscribe`
- `Promotion_plan`, `Promotion`, `Promotion_parts`, `Promotion_governorates`

### Financial
- `Commision_polices`, `Custom_commision`, `Commision_charges`, `Specific_disscount`
- `Report`, `Proccess_report`

### Auth
- `Refresh_token`, `Employment_orders`, `Company_Agency_rigester`, `Company_agency_subscribe`

## Route Organization (routes/api.php)

Routes are grouped by actor middleware:
```php
// Public routes (no auth)
Route::post('login', ...);
Route::post('customer_register', ...);

// Admin routes
Route::middleware('check_admin')->group(function () { ... });

// Company manager routes (often stacked with active/subscription checks)
Route::middleware('check_company_manager')->group(function () {
    Route::post('...', ...)->middleware(['check_company_manager_active', 'check_company_active']);
});

// Agency manager routes
Route::middleware('check_Agency_manager')->group(function () { ... });

// Employee routes
Route::middleware('check_employee')->group(function () { ... });

// Customer routes
Route::middleware('check_customer')->group(function () { ... });
```

## Authentication Flow

```
1. User sends phone → OTP generated → cached 5-10 min
2. User verifies OTP → cache entry marked 'verified'
3. User registers → Sanctum access token + custom refresh token created
4. Subsequent requests → Bearer token in header → middleware validates guard
5. Token refresh → POST /Refresh_token with refresh token
```

Multi-guard setup: each user type has its own Sanctum guard defined in `config/auth.php`.
