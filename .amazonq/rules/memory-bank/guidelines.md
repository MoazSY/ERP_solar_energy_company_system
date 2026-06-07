# Development Guidelines

## Architecture Patterns

### Controller → Service → Repository → Model
Every new feature must follow this 4-layer chain. Controllers are thin — no business logic allowed there.

```php
// Controller: only delegates
public function filter_installation_tasks(Request $request)
{
    $result = $this->employeeService->filter_installation_tasks($request->all());
    return response()->json($result);
}

// Service: owns business logic + authorization
public function filter_installation_tasks($filters)
{
    $employee_id = Auth::guard('employee')->user()->id;
    $employee = Employee::findOrFail($employee_id);

    if (!in_array($employee->employee_type, ['install_technician', 'metal_base_technician'], true)) {
        return ['error' => 'Unauthorized'];
    }

    return $this->employeeRepositoryInterface->filter_installation_tasks($employee, $filters);
}

// Repository: owns data access
public function filter_installation_tasks($employee, array $filters)
{
    $query = $employee->projectTasks();
    // apply filters...
    return $query->latest('id')->get();
}
```

### Repository Interface Pair
Every repository must have a matching interface. Register bindings in `AppServiceProvider`.

```php
// Interface: app/Repositories/EmployeeRepositoryInterface.php
interface EmployeeRepositoryInterface {
    public function filter_installation_tasks($employee, array $filters);
}

// Implementation: app/Repositories/EmployeeRepository.php
class EmployeeRepository implements EmployeeRepositoryInterface { ... }
```

---

## Error Response Convention
All service methods return an associative array. Errors use `['error' => 'message']`. Never throw exceptions to the controller unless wrapping in `DB::transaction`.

```php
// Standard error return
if (!$company) {
    return ['error' => 'company not found for the current manager'];
}

// Standard success return
return ['payment' => $payment->fresh(), 'transaction' => $transaction];
```

---

## Authentication Pattern
Always resolve the current actor from the appropriate Sanctum guard then re-fetch from DB with `findOrFail`:

```php
$company_manager_id = Auth::guard('company_manager')->user()->id;
$company_manager = Solar_company_manager::findOrFail($company_manager_id);
$company = $company_manager->solarCompanies()->first();

if (!$company) {
    return ['error' => 'company not found for the current manager'];
}
```

Available guards: `admin`, `company_manager`, `agency_manager`, `employee`, `customer`.

For employee role checks:
```php
if (!in_array($employee->employee_type, ['install_technician', 'metal_base_technician'], true)) {
    return ['error' => 'Unauthorized'];
}
```

---

## Database Transaction Pattern
Wrap any operation that writes to multiple tables in `DB::transaction`. Return errors from within the closure (do not throw for business logic errors):

```php
return DB::transaction(function () use ($employee, $task_id, $data) {
    $task = Project_task::find($task_id);
    if (!$task) {
        return ['error' => 'Installation task not found'];
    }
    // ... mutations ...
    $task->save();
    return $task->fresh();
});
```

For complex operations that may re-throw, wrap the outer call in try/catch:
```php
try {
    return DB::transaction(function () use (...) { ... });
} catch (\Throwable $e) {
    return ['error' => 'operation failed: ' . $e->getMessage()];
}
```

---

## File Upload Pattern
Use `getClientOriginalName()` as the filename. Always store to `'public'` disk using `storeAs()`. Generate URLs with `asset('storage/' . $path)`:

```php
if ($request->hasFile('company_logo')) {
    $company_logo = $request->file('company_logo')->getClientOriginalName();
    $company_logo_path = $request->file('company_logo')->storeAs('CompanyManager/company_logo', $company_logo, 'public');
    $company_logo_URL = asset('storage/' . $company_logo_path);
} else {
    $company_logo_URL = null;
}
```

Storage subdirectory convention by actor:
- `CompanyManager/` — company manager files
- `AgencyManager/` — agency manager files
- `Employee/` — employee files
- `Customer/` — customer files
- `SystemAdmin/` — admin files
- `products/` — product images
- `Installation/` — installation photos
- `SolarSystem/` — system images

---

## Currency Conversion Convention
Two Syrian Pound regimes exist in the codebase. Always convert at payment/display time:

```php
// USD to new SYP
$amount = (float) $price * 1.35;

// Old SYP to new SYP
$amount = (float) $price / 100;

// Currency check pattern
if ($product->currency === 'USD') {
    $unitPrice *= 1.35;
} else {
    $unitPrice /= 100;
}
```

---

## Polymorphic Morph Conventions
Several core models use Laravel morphs. Use the full class string (not a short alias) as the morph type value:

```php
// Correct — use full class string
$payment = Payment::where('payment_object_table_type', Project_task::class)
    ->where('target_table_type', Company_agency_employee::class)
    ->first();

// Delivery rules lookup pattern
$agency->deliveryRules()
    ->where('is_active', true)
    ->where('governorate_id', $companyAddress->governorate_id)
    ->latest('id')
    ->first();
```

---

## Product Type Dispatch Pattern
Products have 3 types: `battery`, `solar_panel`, `inverter`. Always use this pattern to load type-specific details:

```php
$details = null;
if ($item->product_type === 'battery') {
    $details = $item->batteries;
} elseif ($item->product_type === 'solar_panel') {
    $details = $item->solarPanals;
} elseif ($item->product_type === 'inverter') {
    $details = $item->inverters;
}
return ['product' => $item, 'product_image' => $product_image_URL, 'details' => $details];
```

Or with `switch` / `match` for brevity:
```php
$detailed_info = match ($item->product_type) {
    'inverter' => $item->inverters,
    'battery' => $item->batteries,
    'solar_panel' => $item->solarPanals,
    default => null,
};
```

Note: the relationship name for solar panels is `solarPanals` (not `solarPanels`).

---

## Payment Gateway Pattern
Payment methods supported: `syriatel_cash`, `shamcash`, `cash`. Validate method before calling the gateway service:

```php
if ($request->payment_method !== 'syriatel_cash' && $request->payment_method !== 'shamcash') {
    return ['error' => 'Unsupported payment method'];
}

if ($request->payment_method === 'syriatel_cash') {
    $paymentResponse = $this->apiSyriaService->transferCash($request->gsm, $toGsm, $amount, $request->pin_code);
} else {
    $verificationResult = $this->apiSyriaService->verifyShamcashPaymentFromLogs($toAccountAddress, $amount, $request->account_address);
    if (!$verificationResult['success']) {
        return ['error' => $verificationResult['message']];
    }
    $paymentResponse = ['success' => true, 'message' => 'ShamCash payment verified from logs', 'data' => $verificationResult['matched_log'] ?? null];
}

if (!$paymentResponse['success']) {
    return ['error' => $paymentResponse['message']];
}
```

`ApiSyriaService` always returns `['success' => bool, 'message' => string, 'data' => mixed]`.

---

## External HTTP Pattern (OsrmService / ApiSyriaService)
Wrap all external HTTP calls in try/catch. Return `['error' => '...']` on failure. Use `Http::timeout(10)` for OSRM and `Http::withHeaders(['X-Api-Key' => $this->apiKey])` for ApiSyria:

```php
try {
    $response = Http::timeout(10)->get($url, [...]);
    if (!$response->successful()) {
        throw new Exception('Request failed');
    }
    return $response->json();
} catch (\Throwable $e) {
    return null; // or ['error' => 'message']
}
```

---

## Filter Method Pattern
All filter methods accept an array of optional filters and build an Eloquent query dynamically. Guard each condition with `isset` or `!empty`:

```php
public function filter_installation_tasks($employee, array $filters)
{
    $query = $employee->projectTasks();

    if (!empty($filters['task_status'])) {
        $query->where('task_status', $filters['task_status']);
    }
    if (!empty($filters['date_from'])) {
        $query->whereDate('sheduled_at', '>=', $filters['date_from']);
    }
    // ...

    return $query->latest('id')->get();
}
```

Use `whereHas` for filtering across relationships:
```php
$query->whereHas('company', function ($q) use ($name) {
    $q->where('company_name', 'like', '%' . $name . '%');
});
```

---

## Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Methods | `snake_case` | `filter_installation_tasks` |
| Models | `PascalCase` with underscores for multi-word | `Solar_company`, `Project_task` |
| Table names | `snake_case` plural | `solar_companies`, `project_tasks` |
| Route names | `snake_case` verbs | `proccess_delivery_task` |
| Guards | `snake_case` | `company_manager`, `agency_manager` |
| Storage paths | `PascalCase/subfolder` | `CompanyManager/images` |

Note: `employee_type` values are lowercase strings: `driver`, `install_technician`, `metal_base_technician`, `inventory_manager`, `blacksmith_workshop`.

---

## Commented-Out Code
The codebase contains many intentionally commented-out routes, validations, and logic blocks. Do not remove them — they represent alternative implementations and planned features under review.

---

## Arabic Comments
Inline Arabic comments are used throughout the codebase for business logic explanations. Preserve them in context. Do not replace with English unless asked.

---

## Delivery Fee Calculation Pattern
Delivery fee = `base_fee + (distance_km * price_per_km) + (extra_weight_kg * price_per_extra_kg)`. Always resolve the delivery rule by governorate + optional area, preferring area-specific rules:

```php
$ruleQuery = $agency->deliveryRules()
    ->where('is_active', true)
    ->where('governorate_id', $companyAddress->governorate_id)
    ->where(function ($query) use ($companyAddress) {
        if ($companyAddress->area_id) {
            $query->where('area_id', $companyAddress->area_id)->orWhereNull('area_id');
        } else {
            $query->whereNull('area_id');
        }
    });

if ($companyAddress->area_id) {
    $ruleQuery->orderByRaw('CASE WHEN area_id = ? THEN 0 ELSE 1 END', [$companyAddress->area_id]);
}

$rule = $ruleQuery->latest('id')->first();
```

Product weight is derived from the related technical model:
```php
$unitWeight = $product->inverters?->weight_kg
    ?? $product->batteries?->weight_kg
    ?? $product->solarPanals?->weight_kg
    ?? 0;
```
