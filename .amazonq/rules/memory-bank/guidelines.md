# Solar Company ERP - Development Guidelines

## Architecture Patterns

### Service-Repository Pattern (Mandatory)
Every new feature must follow this 4-layer chain:
```
Controller → Service → Repository → Model
```
- Controllers: validate input, handle HTTP response only
- Services: all business logic, orchestration, file uploads
- Repositories: all Eloquent queries
- Models: schema, relationships, accessors

### Dependency Injection via AppServiceProvider
All repository interfaces are bound in `AppServiceProvider::register()`:
```php
$this->app->bind(SystemAdminRepositoryInterface::class, SystemAdminRepository::class);
```
Services receive their repository via constructor injection (auto-resolved by Laravel):
```php
public function __construct(SystemAdminRepositoryInterface $repo)
{
    $this->systemAdminRepository = $repo;
}
```

### Repository Interface Pattern
Every repository MUST have a paired interface:
```php
// Interface (contract)
interface CustomerRepositoryInterface {
    public function create($data): Customer;
    public function findById(int $id): ?Customer;
}

// Implementation
class CustomerRepository implements CustomerRepositoryInterface {
    public function create($data): Customer {
        return Customer::create($data);
    }
}
```

---

## Controller Conventions

### Standard Method Structure
Every controller method follows this exact pattern:
```php
public function methodName(Request $request)
{
    // 1. Validate
    $validate = Validator::make($request->all(), [
        'field' => 'required|string',
    ]);

    if ($validate->fails()) {
        return response()->json(['message' => $validate->errors()], 422);
    }

    // 2. (Optional) OTP check for sensitive operations
    $cached_phone = Cache::get('otp_' . $internalPhone);
    if (!$cached_phone || $cached_phone['status'] !== 'verified') {
        return response()->json(['message' => 'OTP not verified'], 400);
    }

    // 3. Call service
    $result = $this->service->methodName($request);

    // 4. Check for errors
    if (isset($result['error'])) {
        return response()->json(['message' => $result['error']], 400);
    }

    // 5. Return success response
    return response()->json([
        'message' => 'Operation completed successfully',
        'data' => $result,
    ], 200); // or 201 for creation
}
```

### Error Response Pattern
Services return `['error' => 'message']` arrays on failure. Controllers check with `isset($result['error'])`:
```php
$result = $this->service->doSomething($request);
if (isset($result['error'])) {
    return response()->json(['message' => $result['error']], 400);
}
```

### Validation Response HTTP Codes
| Scenario | HTTP Code |
|---------|-----------|
| Validation fails | 422 |
| Business logic error | 400 |
| Not found | 404 |
| Server error | 500 |
| Created | 201 |
| Retrieved/Updated/Deleted | 200 |

### Route Parameter Validation
When route parameters need validation, merge them into the validator:
```php
public function show_agency_products(Request $request, $agency_id)
{
    $validate = Validator::make(['agency_id' => $agency_id], [
        'agency_id' => 'required|integer|exists:agencies,id'
    ]);
    // ...
}
```

Or with request body:
```php
$validate = Validator::make(array_merge($request->all(), ['agency_id' => $agency_id]), [
    'agency_id' => 'required|integer|exists:agencies,id',
    'products' => 'required|array',
]);
```

---

## Validation Rules Patterns

### Syrian Phone Number
```php
'phoneNumber' => 'required|regex:/^09\d{8}$/'
// Internal format (for OTP cache key): '963' . substr($phone, 1)
```

### Payment Method Conditional Fields
```php
'payment_method' => 'required|in:syriatel_cash,shamcash',
'gsm'            => 'required_if:payment_method,syriatel_cash|regex:/^09\d{8}$/',
'pin_code'       => 'required_if:payment_method,syriatel_cash|string',
'account_address'=> 'required_if:payment_method,shamcash|string',
```

### Image Uploads
```php
'image'  => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',   // profile (2MB)
'images' => 'sometimes|array',
'images.*' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',           // installation (4MB)
```

### Date Range Filters
```php
'date_from' => 'sometimes|date',
'date_to'   => 'sometimes|date|after_or_equal:date_from',
```

### Enum Fields (Common Values)
```php
'system_type'    => 'sometimes|string|in:on_grid,off_grid,hybrid',
'battery_type'   => 'sometimes|string|in:lithium_ion,lead_acid,nickel_cadmium',
'inverter_voltage_v' => 'sometimes|string|in:12V,24V,48V',
'currency'       => 'sometimes|string|in:USD,SY',
'task_type'      => 'sometimes|string|in:installation,metal_base,blacksmith_workshop,maintenance,technical_inspection',
'payment_status' => 'sometimes|string|in:pending,partially_paid,paid',
'expected_budget'=> 'sometimes|string|in:low,medium,high',
```

---

## File Upload Handling

Always handled in the Controller, path passed to Service:
```php
// In controller
$data = $validate->validated();
if ($request->hasFile('surface_image')) {
    $imagePath = $request->file('surface_image')->getClientOriginalName();
    $data['surface_image'] = $request->file('surface_image')
        ->storeAs('SolarSystem/technician_defined', $imagePath, 'public');
}
$result = $this->service->methodName($data);

// For multiple images
$storedImages = [];
if ($request->hasFile('images')) {
    foreach ($request->file('images') as $file) {
        $original = $file->getClientOriginalName();
        $path = $file->storeAs('Installation/complete', time() . '_' . $original, 'public');
        $storedImages[] = $path;
    }
}
if (!empty($storedImages)) {
    $data['images'] = $storedImages;
}
```

Storage directories (all under `public` disk):
- `SystemAdmin/` - admin images
- `CompanyManager/` - company manager images
- `AgencyManager/` - agency manager images
- `Customer/` - customer images
- `Employee/` - employee images
- `products/` - product images
- `Installation/complete/` - task completion photos
- `SolarSystem/technician_defined/` - technician surface images

---

## Authentication & OTP Pattern

### OTP Verification (Required for phone/email changes and registration)
```php
// Phone: convert Syrian format to international
$internalPhone = '963' . substr($request['phoneNumber'], 1);
$cached_phone = Cache::get('otp_' . $internalPhone);

if (!$cached_phone || $cached_phone['status'] !== 'verified') {
    return response()->json(['message' => 'OTP not verified'], 400);
}
```

### Uniqueness Validation (StoreUserRequest Pattern)
For email/phone uniqueness that supports both create and update:
```php
$uniqueRequest = app(StoreUserRequest::class);
$uniqueRequest->ignoreId = Auth::guard('company_manager')->user()->id; // null for create
$uniqueRequest->ignoreTable = 'solar_company_managers';               // null for create
$uniqueRequest->merge(['email' => $request->email]);
$uniqueRequest->prepareForValidation();
$uniqueValidator = Validator::make($uniqueRequest->all(), $uniqueRequest->rules())->validate();
$data = array_merge($uniqueValidator, $validate->validated());
```

### Getting Authenticated User
```php
$user = Auth::guard('company_manager')->user(); // specific guard
$user = Auth::user();                            // current guard
```

---

## Route Organization

Routes in `routes/api.php` are grouped by actor middleware:
```php
Route::middleware('check_company_manager')->group(function () {
    // Routes that only need manager auth:
    Route::get('profile', ...);

    // Routes that also need active status + subscription:
    Route::post('action', ...)->middleware(['check_company_manager_active', 'check_company_active', 'check_company_subscription']);
});
```

Middleware stacking order: `check_X` → `check_X_active` → `check_X_subscription`

---

## Model Conventions

### Authenticatable User Models
```php
class Employee extends Authenticatable {
    use HasApiTokens, Notifiable, HasFactory;
    protected $fillable = [...];

    // Polymorphic refresh tokens
    public function refreshTokens() {
        return $this->morphMany(Refresh_token::class, 'user_table');
    }
}
```

### Relationship Naming
- `belongsTo` → camelCase singular: `agencyManager()`, `solarCompany()`
- `hasMany` → camelCase plural: `conflictInvoices()`, `projectTasks()`
- `morphMany` → named after purpose: `refreshTokens()`

### Naming Conventions
- Model files: PascalCase with underscores for compound names: `Solar_company`, `Agency_manager`, `Project_task`
- Table names: snake_case plural: `solar_companies`, `agency_managers`, `project_tasks`
- Foreign keys: `entity_id` pattern: `agency_manager_id`, `solar_company_id`

---

## Naming Conventions Summary

| Artifact | Convention | Example |
|----------|-----------|---------|
| Controllers | PascalCase, actor-named | `SolarCompanyManager`, `EmployeeController` |
| Controller methods | snake_case verbs | `filter_installation_tasks`, `proccess_delivery_task` |
| Services | Entity + Service | `SolarCompanyManagerService` |
| Repositories | Entity + Repository | `CustomerRepository` |
| Repository Interfaces | Entity + RepositoryInterface | `CustomerRepositoryInterface` |
| Middleware | `Check_` + entity | `Check_company_manager`, `Check_employee` |
| Models | PascalCase (underscores allowed) | `Solar_company`, `Request_solar_system` |
| Migration files | `YYYY_MM_DD_HHmmss_description` | `2026_02_25_070948_create_system_admins_table.php` |
| Route names | snake_case verbs | `filter_installation_tasks`, `assign_delivery_task` |

---

## Filter/Search Endpoints Pattern

Filter endpoints consistently accept optional date ranges, status enums, and entity IDs:
```php
$validate = Validator::make($request->all(), [
    'date_from'   => 'sometimes|date',
    'date_to'     => 'sometimes|date|after_or_equal:date_from',
    'status'      => 'sometimes|string|in:value1,value2',
    'entity_id'   => 'sometimes|integer|exists:table,id',
    'min_amount'  => 'sometimes|numeric|min:0',
    'max_amount'  => 'sometimes|numeric|min:0',
]);
$filters = $validate->validated();
$result = $this->service->filter_X($filters);
return response()->json([
    'message' => 'X filtered successfully',
    'count' => count($result),
    'data' => $result,
]);
```

---

## Adding New Features Checklist

1. **Model**: Define in `app/Models/` with `$fillable`, relationships
2. **Migration**: `php artisan make:migration create_X_table`
3. **Repository Interface**: Define method contracts in `app/Repositories/XRepositoryInterface.php`
4. **Repository**: Implement in `app/Repositories/XRepository.php`
5. **Register DI**: Add `$this->app->bind(XRepositoryInterface::class, XRepository::class)` in `AppServiceProvider`
6. **Service**: Business logic in `app/Services/XService.php`, inject interface via constructor
7. **Controller**: Validate → call service → check error → return JSON in `app/Http/Controllers/`
8. **Routes**: Add to appropriate middleware group in `routes/api.php`
9. **Middleware**: If new actor type, create `Check_X.php` middleware

---

## Code Quality Notes

- Use `$validate->validated()` (not `$request->all()`) when passing data to services to ensure only validated fields are passed
- Use `isset($result['error'])` pattern consistently — never throw exceptions from services to controllers
- Commented-out route/method stubs are acceptable for planned features (use Arabic comments for business context)
- Arabic comments in code are common and intentional (business domain context for Syrian market)
- Avoid returning raw model instances; services should return arrays or collections for flexible JSON shaping
- Use `response()->json(...)` exclusively — no Blade responses in API context
