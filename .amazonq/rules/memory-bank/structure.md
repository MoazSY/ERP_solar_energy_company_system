# Project Structure

## Directory Layout

```
solar_energy_company/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Thin controllers — delegate to Services
│   │   ├── Middleware/         # Guard-specific auth + activity checks
│   │   └── Requests/           # Form request validation classes
│   ├── Models/                 # Eloquent models (50+ domain models)
│   ├── Notifications/          # OTP notification (SendOtpNotification)
│   ├── Providers/              # AppServiceProvider
│   ├── Repositories/           # Data access layer (Repository + Interface pairs)
│   ├── Rules/                  # Custom validation rules (UniqueAcrossTables)
│   └── Services/               # Business logic layer
├── bootstrap/                  # App bootstrap + cached service manifest
├── config/                     # Laravel config (auth, database, sanctum, etc.)
├── database/
│   ├── migrations/             # 60+ timestamped migration files
│   ├── seeders/                # Core, electrical device, maintenance seeders
│   └── factories/              # UserFactory
├── routes/
│   └── api.php                 # All API routes grouped by middleware guard
├── storage/app/public/         # Uploaded files (logos, product images, etc.)
└── .amazonq/rules/memory-bank/ # Project memory bank documentation
```

## Core Layers & Relationships

### Controller → Service → Repository → Model
Every actor has a matching stack:
- `SolarCompanyManager` controller → `SolarCompanyManagerService` → `SolarCompanyManagerRepository` (implements `SolarCompanyManagerRepositoryInterface`)
- `AgencyManagerController` → `AgencyManagerService` → `AgencyManagerRepository`
- `EmployeeController` → `EmployeeService` → `EmployeeRepository`
- `CustomerController` → `CustomerService` → `CustomerRepository`
- `System_admin` controller → `SystemAdminService` → `SystemAdminRepository`

### Shared Services
- `OtpService` — OTP generation/verification and token management (used across all actors)
- `OsrmService` — HTTP calls to OSRM for routing/distance
- `ApiSyriaService` / `ApiSyriaToolsService` — Syrian payment gateway integration

### Authentication Architecture
Six Sanctum guards defined in `config/auth.php`:
- `admin`, `company_manager`, `agency_manager`, `employee`, `customer`, `api`

Each guard backed by its own Eloquent model provider. Middleware enforces per-guard access:
- `check_admin`, `check_company_manager`, `check_Agency_manager`, `check_employee`, `check_customer`
- Activity/subscription checks layered on top: `check_company_active`, `check_company_manager_active`, `check_agency_active`, `check_agency_manager_active`, `check_agency_subscription`, `check_company_subscription`

### Key Domain Models

| Model | Role |
|---|---|
| `Solar_company` | Core company entity |
| `Agency` | Product distribution agency |
| `Employee` | Field worker with `employee_type` enum |
| `Customer` | End user |
| `Products` / `Batteries` / `Solar_panal` / `Inverters` | Product catalog with type-specific details |
| `Order_list` / `Items` | Customer product orders |
| `Offers` / `Subscribe_offer` | Subscription-based solar system packages |
| `Project_task` | Installation/maintenance task assigned to technician |
| `Deliveries` | Delivery task assigned to driver |
| `Payment` / `Payment_transactions` | Payment records with gateway transactions |
| `Input_output_request` | Inventory stock movement requests |
| `Metainence_request` | Customer maintenance requests |
| `Technical_inspection_request` | Pre-installation site inspection |
| `Request_solar_system` | Customer's raw solar system request |
| `Company_Agency_rigester` | Registration approval record (polymorphic) |
| `Company_agency_subscribe` | Subscription policy binding (polymorphic) |

### Polymorphic Patterns
Models heavily use Laravel morphs for multi-entity associations:
- `Address` → morphs to any entity type
- `Payment` → `payable` (who pays) + `target_table` (who receives) morphs
- `Products` → morphs to company or agency as `entity_type`
- `Deliveries` → morphs to company or agency as `entity_type`
- `Company_agency_subscribe` → morphs to company or agency as `subscribable`

### Route Organization (`routes/api.php`)
Routes grouped by middleware guard:
```
Public routes (no auth)
└── check_admin group
└── check_company_manager group
    └── + check_company_manager_active + check_company_active (sensitive ops)
└── check_Agency_manager group
    └── + check_agency_manager_active + check_agency_active
└── check_employee group
└── check_customer group
└── auth:sanctum group (shared authenticated routes)
```
