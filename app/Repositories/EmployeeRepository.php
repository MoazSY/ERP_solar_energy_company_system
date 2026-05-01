<?php
namespace App\Repositories;

use App\Models\Company_agency_employee;
use App\Models\Deliveries;
use App\Models\Employee;
// use App\Models\Employment_orders;
use App\Models\Input_output_request;
use App\Models\Products;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    private function normalizeInventoryLookupValue(?string $value): string
    {
        $normalizedValue = strtolower(trim((string) $value));
        return preg_replace('/[^a-z0-9]+/', '', $normalizedValue) ?? '';
    }

    private function resolveExistingStockProduct($company, array $data)
    {
        $products = $company->products()->get();
        $targetProductType = $data['product_type'] ?? null;
        $targetModelNumber = $this->normalizeInventoryLookupValue($data['model_number'] ?? null);
        $targetProductName = $this->normalizeInventoryLookupValue($data['product_name'] ?? null);

        if ($targetModelNumber !== '') {
            $matchedProduct = $products->first(function ($product) use ($targetProductType, $targetModelNumber) {
                return $product->product_type === $targetProductType &&
                    $this->normalizeInventoryLookupValue($product->model_number) === $targetModelNumber;
            });

            if ($matchedProduct) {
                return $matchedProduct;
            }
        }

        if ($targetProductName !== '') {
            return $products->first(function ($product) use ($targetProductType, $targetProductName) {
                return $product->product_type === $targetProductType &&
                    $this->normalizeInventoryLookupValue($product->product_name) === $targetProductName;
            });
        }

        return null;
    }

    private function createInventoryTechnicalDetails($product, array $data): void
    {
        if (!($data['with_technical_details'] ?? false)) {
            return;
        }

        if ($product->product_type === 'battery' && !$product->batteries) {
            $product->batteries()->create([
                'battery_type' => $data['battery_type'],
                'capacity_kwh' => $data['capacity_kwh'],
                'voltage_v' => $data['voltage_v'],
                'cycle_life' => $data['cycle_life'],
                'warranty_years' => $data['warranty_years'],
                'weight_kg' => $data['weight_kg'],
                'Amperage_Ah' => $data['Amperage_Ah'],
                'celles_type' => $data['celles_type'],
                'celles_name' => $data['celles_name'] ?? null,
            ]);
        }

        if ($product->product_type === 'inverter' && !$product->inverters) {
            $product->inverters()->create([
                'grid_type' => $data['grid_type'],
                'voltage_v' => $data['voltage_v'],
                'grid_capacity_kw' => $data['grid_capacity_kw'],
                'solar_capacity_kw' => $data['solar_capacity_kw'],
                'inverter_open' => $data['inverter_open'],
                'voltage_open' => $data['voltage_open'],
                'weight_kg' => $data['weight_kg'],
                'warranty_years' => $data['warranty_years'],
            ]);
        }

        if ($product->product_type === 'solar_panel' && !$product->solarPanals) {
            $product->solarPanals()->create([
                'capacity_kw' => $data['capacity_kw'],
                'basbar_number' => $data['basbar_number'],
                'is_half_cell' => $data['is_half_cell'],
                'is_bifacial' => $data['is_bifacial'],
                'warranty_years' => $data['warranty_years'],
                'weight_kg' => $data['weight_kg'],
                'length_m' => $data['length_m'],
                'width_m' => $data['width_m'],
            ]);
        }
    }

    public function employee_profile($employee_id)
    {
        return Employee::findOrFail($employee_id);
    }

    public function create_internal_employee_request($request, $entity, $entityTypeClass, $data)
    {
        $employee = Employee::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'employee_type' => $request->employee_type,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'],
            'account_number' => $request->account_number,
            'syriatel_cash_phone' => $request->syriatel_cash_phone,
            'image' => $request->image,
            'identification_image' => $request->identification_image,
            'about_him' => $request->about_him,
            'is_active' => false,
        ]);
        return [
            'employee' => $employee->fresh(),
        ];
    }

    public function register_employee_company_agency($request, $entity, $entityTypeClass)
    {
        $existing = Company_agency_employee::query()
            ->where('employee_id', $request->employee_id)
            ->where('entity_type_type', $entityTypeClass)
            ->where('entity_type_id', $entity->id)
            ->where('role', $request->role)
            ->first();

        if ($existing) {
            return ['error' => 'Employee is already assigned to this role in this entity'];
        }
        $assignment = Company_agency_employee::create([
            'employee_id' => $request->employee_id,
            'entity_type_type' => $entityTypeClass,
            'entity_type_id' => $entity->id,
            'role' => $request->role,
            'salary_type' => $request->salary_type,
            'currency' => $request->currency,
            'work_type' => $request->work_type,
            'payment_method' => $request->payment_method,
            'payment_frequency' => $request->payment_frequency,
            'salary_rate' => $request->salary_type === 'rate' ? ($request->salary_rate ?? 0) : 0,
            'salary_amount' => $request->salary_type === 'fixed' ? ($request->salary_amount ?? 0) : 0,
        ]);

        $employeeTypeMap = [
            'inventory_manager' => 'inventory_manager',
            'driver' => 'driver',
            'install_technician' => 'technician',
            'metal_base_technician' => 'technician',
            'blacksmith_workshop' => 'technician',
        ];

        $employeeType = $employeeTypeMap[$request->role] ?? null;

        Employee::where('id', $request->employee_id)->update([
            'is_active' => true,
            'employee_type' => $employeeType,
        ]);

        return $assignment->load(['employee', 'entityType']);
    }

    public function search_employees($filter)
    {
        $query = Employee::query();

        if (isset($filter['first_name'])) {
            $query->where('first_name', 'like', '%' . $filter['first_name'] . '%');
        }

        if (isset($filter['last_name'])) {
            $query->where('last_name', 'like', '%' . $filter['last_name'] . '%');
        }

        if (isset($filter['email'])) {
            $query->where('email', 'like', '%' . $filter['email'] . '%');
        }

        if (isset($filter['employee_type'])) {
            $query->where('employee_type', $filter['employee_type']);
        }

        return $query->get();
    }

    public function show_entity_employees($entity, $entityTypeClass)
    {
        $assignments = Company_agency_employee::query()
            ->where('entity_type_type', $entityTypeClass)
            ->where('entity_type_id', $entity->id)
            ->with(['employee'])
            ->latest('id')
            ->get();

        return $assignments->map(function ($assignment) {
            return [
                'assignment' => $assignment,
                'employee' => $assignment->employee,
                'imageUrl' => $assignment->employee?->image ? asset('storage/' . $assignment->employee->image) : null,
                'identification_imageUrl' => $assignment->employee?->identification_image ? asset('storage/' . $assignment->employee->identification_image) : null,
            ];
        });
    }

    public function show_delivery_tasks($employee)
    {
        $deliveries = $employee->driverDeliveries()->with(['deliverable_object', 'entity_type'])->get();
        $delivery_tasks = $deliveries->map(function ($delivery) {
            $targetEntity = $delivery->deliverable_object?->request_entity;

            return [
                'delivery' => $delivery,
                'order_list' => $delivery->deliverable_object,
                'entity_source' => $delivery->entity_type,
                'entity_target' => $targetEntity,
                'address' => $targetEntity?->addresses()->first(),
                'items' => $delivery->deliverable_object->Items()->with('product')->get() ?? null,
                'weight_kg' => $delivery
                    ->deliverable_object
                    ->Items()
                    ->with(['product.inverters', 'product.batteries', 'product.solarPanals'])
                    ->get()
                    ->sum(function ($item) {
                        $unitWeight = $item->product?->inverters?->weight_kg
                            ?? $item->product?->batteries?->weight_kg
                            ?? $item->product?->solarPanals?->weight_kg
                            ?? 0;

                        return $unitWeight * ($item->quantity ?? 1);
                    })
            ];
        });
        return $delivery_tasks;
    }

    public function proccess_delivery_task($request, $employee)
    {
        $delivery = Deliveries::findOrFail($request->delivery_id);

        if ($delivery->driver_id !== $employee->id) {
            return ['error' => 'Unauthorized'];
        }

        if ($delivery->driver_approved_delivery_task !== 'pending') {
            return ['error' => 'This delivery task has already been processed'];
        }

        $delivery->driver_approved_delivery_task = $request->action === 'approve' ? 'approve' : 'reject';
        $delivery->save();
        return $delivery;
    }

    public function show_orderList_for_inventory_manager($employee)
    {
        $input_output_request = Input_output_request::query()
            ->where('inventory_manager_id', $employee->id)
            ->with(['order', 'order.request_entity', 'order.Items', 'order.Items.product', 'order.Items.product.inverters', 'order.Items.product.batteries'])
            ->get();
        return $input_output_request;
    }

    public function insert_product_to_stock($data, $company)
    {
        return DB::transaction(function () use ($data, $company) {
            if(isset($data['product_name_for_validation'])){
                $pruduct=$company->products()->where('product_name',$data['product_name_for_validation'])->first();
                if($pruduct){
                    $existingProduct = $pruduct;
                    $existingQuantity = (int) ($existingProduct->quentity ?? 0);
                    $incomingQuantity = (int) ($data['quentity'] ?? 0);
                    $existingProduct->quentity = $existingQuantity + $incomingQuantity;
                    $existingProduct->save();

                    $product = $existingProduct->load(['batteries', 'inverters', 'solarPanals']);
                    // $this->createInventoryTechnicalDetails($product, $data);

                    return [
                        'product' => $product->fresh(['batteries', 'inverters', 'solarPanals']),
                        'action' => 'updated',
                    ];
                }
            }
            $existingProduct = $this->resolveExistingStockProduct($company, $data);

            if ($existingProduct) {
                $existingQuantity = (int) ($existingProduct->quentity ?? 0);
                $incomingQuantity = (int) ($data['quentity'] ?? 0);
                $existingProduct->quentity = $existingQuantity + $incomingQuantity;
                $existingProduct->save();

                $product = $existingProduct->load(['batteries', 'inverters', 'solarPanals']);
                // $this->createInventoryTechnicalDetails($product, $data);

                return [
                    'product' => $product->fresh(['batteries', 'inverters', 'solarPanals']),
                    'action' => 'updated',
                ];
            }
            $product = $company->products()->create([
                'product_name' => $data['product_name'],
                'product_type' => $data['product_type'],
                'product_brand' => $data['product_brand'] ?? null,
                'model_number' => $data['model_number'] ?? null,
                'quentity' => $data['quentity'] ?? null,
                'price' => $data['price'],
                'disscount_type' => $data['disscount_type'] ?? null,
                'disscount_value' => $data['disscount_value'] ?? null,
                'currency' => $data['currency'],
                'manufacture_date' => $data['manufacture_date'] ?? null,
                'product_image' => $data['product_image'] ?? null,
            ]);

            $this->createInventoryTechnicalDetails($product, $data);

            return [
                'product' => $product->fresh(['batteries', 'inverters', 'solarPanals']),
                'action' => 'created',
            ];
        });
    }

    public function add_inventory_product_battery($request, $product_id)
    {
        $product = Products::findOrFail($product_id->id);
        if ($product->product_type != 'battery') {
            return null;
        }
        $battery = $product->batteries()->create([
            'battery_type' => $request['battery_type'],
            'capacity_kwh' => $request['capacity_kwh'],
            'voltage_v' => $request['voltage_v'],
            'cycle_life' => $request['cycle_life'],
            'warranty_years' => $request['warranty_years'],
            'weight_kg' => $request['weight_kg'],
            'Amperage_Ah' => $request['Amperage_Ah'],
            'celles_type' => $request['celles_type'],
            'celles_name' => $request['celles_name'],
        ]);
        return $battery;
    }

    public function add_inventory_product_inverter($request, $product_id)
    {
        $product = Products::findOrFail($product_id->id);
        if ($product->product_type != 'inverter') {
            return null;
        }
        $inverter = $product->inverters()->create([
            'grid_type' => $request['grid_type'],
            'voltage_v' => $request['voltage_v'],
            'grid_capacity_kw' => $request['grid_capacity_kw'],
            'solar_capacity_kw' => $request['solar_capacity_kw'],
            'inverter_open' => $request['inverter_open'],
            'voltage_open' => $request['voltage_open'],
            'weight_kg' => $request['weight_kg'],
            'warranty_years' => $request['warranty_years'],
        ]);
        return $inverter;
    }

    public function add_inventory_product_solar_panel($request, $product_id)
    {
        $product = Products::findOrFail($product_id->id);
        if ($product->product_type != 'solar_panel') {
            return null;
        }
        $solar_panel = $product->solarPanals()->create([
            'capacity_kw' => $request['capacity_kw'],
            'basbar_number' => $request['basbar_number'],
            'is_half_cell' => $request['is_half_cell'],
            'is_bifacial' => $request['is_bifacial'],
            'warranty_years' => $request['warranty_years'],
            'weight_kg' => $request['weight_kg'],
            'length_m' => $request['length_m'],
            'width_m' => $request['width_m'],
        ]);
        return $solar_panel;
    }

    public function update_inventory_product($request, $data, $product_id)
    {
        $inventory_manager = Auth::guard('employee')->user();
        $inventory_manager = Employee::findOrFail($inventory_manager->id);
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();

        if (!$company) {
            return null;
        }

        $product = $company->products()->find($product_id);

        if (!$product) {
            return null;
        }

        // Handle image upload if provided
        if ($request->hasFile('product_image')) {
            $imagePath = $request->file('product_image')->store('products', 'public');
            $data['product_image'] = $imagePath;
            $product_image_URL = asset('storage/' . $data['product_image']);
        } else {
            $product_image_URL = asset('storage/' . $product->product_image);
        }

        $product->update([
            'product_name' => $data['product_name'] ?? $product->product_name,
            'product_type' => $data['product_type'] ?? $product->product_type,
            'product_brand' => $data['product_brand'] ?? $product->product_brand,
            'model_number' => $data['model_number'] ?? $product->model_number,
            'quentity' => $data['quentity'] ?? $product->quentity,
            'price' => $data['price'] ?? $product->price,
            'disscount_type' => $data['disscount_type'] ?? $product->disscount_type,
            'disscount_value' => $data['disscount_value'] ?? $product->disscount_value,
            'currency' => $data['currency'] ?? $product->currency,
            'manufacture_date' => $data['manufacture_date'] ?? $product->manufacture_date,
            'product_image' => $data['product_image'] ?? $product->product_image,
        ]);

        $product->save();
        $product->refresh();  // Refresh the model to get the latest data

        if ($data['update_technical_details'] == true && $product->product_type == 'battery') {
            $battery = $product->batteries;
            $battery->update([
                'battery_type' => $data['battery_type'] ?? $battery->battery_type,
                'capacity_kwh' => $data['capacity_kwh'] ?? $battery->capacity_kwh,
                'voltage_v' => $data['voltage_v'] ?? $battery->voltage_v,
                'cycle_life' => $data['cycle_life'] ?? $battery->cycle_life,
                'warranty_years' => $data['warranty_years'] ?? $battery->warranty_years,
                'weight_kg' => $data['weight_kg'] ?? $battery->weight_kg,
                'Amperage_Ah' => $data['Amperage_Ah'] ?? $battery->Amperage_Ah,
                'celles_type' => $data['celles_type'] ?? $battery->celles_type,
                'celles_name' => $data['celles_name'] ?? $battery->celles_name,
            ]);
            $battery->save();
            $battery->refresh();
        }
        if ($data['update_technical_details'] == true && $product->product_type == 'inverter') {
            $inverter = $product->inverters;
            $inverter->update([
                'grid_type' => $data['grid_type'] ?? $inverter->grid_type,
                'voltage_v' => $data['voltage_v'] ?? $inverter->voltage_v,
                'grid_capacity_kw' => $data['grid_capacity_kw'] ?? $inverter->grid_capacity_kw,
                'solar_capacity_kw' => $data['solar_capacity_kw'] ?? $inverter->solar_capacity_kw,
                'inverter_open' => $data['inverter_open'] ?? $inverter->inverter_open,
                'voltage_open' => $data['voltage_open'] ?? $inverter->voltage_open,
                'weight_kg' => $data['weight_kg'] ?? $inverter->weight_kg,
                'warranty_years' => $data['warranty_years'] ?? $inverter->warranty_years,
            ]);
            $inverter->save();
            $inverter->refresh();
        }
        if ($data['update_technical_details'] == true && $product->product_type == 'solar_panel') {
            $solar_panel = $product->solarPanals;
            $solar_panel->update([
                'panel_type' => $data['panel_type'] ?? $solar_panel->panel_type,
                'capacity_kw' => $data['capacity_kw'] ?? $solar_panel->capacity_kw,
                'voltage_v' => $data['voltage_v'] ?? $solar_panel->voltage_v,
                'warranty_years' => $data['warranty_years'] ?? $solar_panel->warranty_years,
                'weight_kg' => $data['weight_kg'] ?? $solar_panel->weight_kg,
            ]);
            $solar_panel->save();
            $solar_panel->refresh();
        }

        // if ($data['product_image'] != null) {
        //     $product_image_URL = asset('storage/' . $data['product_image']);
        // }
        return [$product, $product_image_URL];
    }

    public function delete_inventory_product($product_id)
    {
        $inventory_manager = Auth::guard('employee')->user();
        $inventory_manager = Employee::findOrFail($inventory_manager->id);
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();

        if (!$company) {
            return false;
        }

        $product = $company->products()->find($product_id);

        if (!$product) {
            return false;
        }

        if ($product->product_type === 'battery') {
            $product->batteries()->delete();
        } elseif ($product->product_type === 'inverter') {
            $product->inverters()->delete();
        } elseif ($product->product_type === 'solar_panel') {
            $product->solarPanals()->delete();
        }

        $product->delete();
        return true;
    }

    public function delete_inventory_product_details($product_id)
    {
        $inventory_manager = Auth::guard('employee')->user();
        $inventory_manager = Employee::findOrFail($inventory_manager->id);
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();

        if (!$company) {
            return false;
        }

        $product = $company->products()->find($product_id);

        if (!$product) {
            return false;
        }

        if ($product->product_type === 'battery') {
            $product->batteries()->delete();
        } elseif ($product->product_type === 'inverter') {
            $product->inverters()->delete();
        } elseif ($product->product_type === 'solar_panel') {
            $product->solarPanals()->delete();
        }
        return true;
    }

    public function filter_inventory_products($filters)
    {
        $inventory_manager = Auth::guard('employee')->user();
        $inventory_manager = Employee::findOrFail($inventory_manager->id);
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();

        if (!$company) {
            return [];
        }

        $query = $company->products();

        // فلترة البيانات الأساسية للمنتج
        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['product_name'])) {
            $query->where('product_name', 'like', '%' . $filters['product_name'] . '%');
        }

        if (isset($filters['product_brand'])) {
            $query->where('product_brand', 'like', '%' . $filters['product_brand'] . '%');
        }

        if (isset($filters['model_number'])) {
            $query->where('model_number', 'like', '%' . $filters['model_number'] . '%');
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['quentity_min'])) {
            $query->where('quentity', '>=', $filters['quentity_min']);
        }

        if (isset($filters['quentity_max'])) {
            $query->where('quentity', '<=', $filters['quentity_max']);
        }

        // فلترة تفاصيل البطارية
        if (($filters['product_type'] ?? null) === 'battery') {
            if (isset($filters['battery_type']) ||
                    isset($filters['capacity_kwh']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['cycle_life_min']) ||
                    isset($filters['cycle_life_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['Amperage_Ah']) ||
                    isset($filters['celles_type']) ||
                    isset($filters['celles_name'])) {
                $query->whereHas('batteries', function ($batteryQuery) use ($filters) {
                    if (isset($filters['battery_type'])) {
                        $batteryQuery->where('battery_type', $filters['battery_type']);
                    }
                    if (isset($filters['capacity_kwh'])) {
                        $batteryQuery->where('capacity_kwh', $filters['capacity_kwh']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $batteryQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['cycle_life_min'])) {
                        $batteryQuery->where('cycle_life', '>=', $filters['cycle_life_min']);
                    }
                    if (isset($filters['cycle_life_max'])) {
                        $batteryQuery->where('cycle_life', '<=', $filters['cycle_life_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $batteryQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $batteryQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $batteryQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $batteryQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['Amperage_Ah'])) {
                        $batteryQuery->where('Amperage_Ah', $filters['Amperage_Ah']);
                    }
                    if (isset($filters['celles_type'])) {
                        $batteryQuery->where('celles_type', $filters['celles_type']);
                    }
                    if (isset($filters['celles_name'])) {
                        $batteryQuery->where('celles_name', 'like', '%' . $filters['celles_name'] . '%');
                    }
                });
            }
        }

        // فلترة تفاصيل المحول (Inverter)
        if (($filters['product_type'] ?? null) === 'inverter') {
            if (isset($filters['grid_type']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['grid_capacity_kw_min']) ||
                    isset($filters['grid_capacity_kw_max']) ||
                    isset($filters['solar_capacity_kw_min']) ||
                    isset($filters['solar_capacity_kw_max']) ||
                    isset($filters['inverter_open']) ||
                    isset($filters['voltage_open_min']) ||
                    isset($filters['voltage_open_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max'])) {
                $query->whereHas('inverters', function ($inverterQuery) use ($filters) {
                    if (isset($filters['grid_type'])) {
                        $inverterQuery->where('grid_type', $filters['grid_type']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $inverterQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['grid_capacity_kw_min'])) {
                        $inverterQuery->where('grid_capacity_kw', '>=', $filters['grid_capacity_kw_min']);
                    }
                    if (isset($filters['grid_capacity_kw_max'])) {
                        $inverterQuery->where('grid_capacity_kw', '<=', $filters['grid_capacity_kw_max']);
                    }
                    if (isset($filters['solar_capacity_kw_min'])) {
                        $inverterQuery->where('solar_capacity_kw', '>=', $filters['solar_capacity_kw_min']);
                    }
                    if (isset($filters['solar_capacity_kw_max'])) {
                        $inverterQuery->where('solar_capacity_kw', '<=', $filters['solar_capacity_kw_max']);
                    }
                    if (isset($filters['inverter_open'])) {
                        $inverterQuery->where('inverter_open', $filters['inverter_open']);
                    }
                    if (isset($filters['voltage_open_min'])) {
                        $inverterQuery->where('voltage_open', '>=', $filters['voltage_open_min']);
                    }
                    if (isset($filters['voltage_open_max'])) {
                        $inverterQuery->where('voltage_open', '<=', $filters['voltage_open_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $inverterQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $inverterQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $inverterQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $inverterQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                });
            }
        }

        // فلترة تفاصيل الألواح الشمسية
        if (($filters['product_type'] ?? null) === 'solar_panel') {
            if (isset($filters['capacity_kw']) ||
                    isset($filters['basbar_number_min']) ||
                    isset($filters['basbar_number_max']) ||
                    isset($filters['is_half_cell']) ||
                    isset($filters['is_bifacial']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['length_m_min']) ||
                    isset($filters['length_m_max']) ||
                    isset($filters['width_m_min']) ||
                    isset($filters['width_m_max'])) {
                $query->whereHas('solarPanals', function ($panelQuery) use ($filters) {
                    if (isset($filters['capacity_kw'])) {
                        $panelQuery->where('capacity_kw', $filters['capacity_kw']);
                    }
                    if (isset($filters['basbar_number_min'])) {
                        $panelQuery->where('basbar_number', '>=', $filters['basbar_number_min']);
                    }
                    if (isset($filters['basbar_number_max'])) {
                        $panelQuery->where('basbar_number', '<=', $filters['basbar_number_max']);
                    }
                    if (isset($filters['is_half_cell'])) {
                        $panelQuery->where('is_half_cell', $filters['is_half_cell']);
                    }
                    if (isset($filters['is_bifacial'])) {
                        $panelQuery->where('is_bifacial', $filters['is_bifacial']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $panelQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $panelQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $panelQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $panelQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['length_m_min'])) {
                        $panelQuery->where('length_m', '>=', $filters['length_m_min']);
                    }
                    if (isset($filters['length_m_max'])) {
                        $panelQuery->where('length_m', '<=', $filters['length_m_max']);
                    }
                    if (isset($filters['width_m_min'])) {
                        $panelQuery->where('width_m', '>=', $filters['width_m_min']);
                    }
                    if (isset($filters['width_m_max'])) {
                        $panelQuery->where('width_m', '<=', $filters['width_m_max']);
                    }
                });
            }
        }

        return $query->with(['batteries', 'inverters', 'solarPanals'])->get();
    }

    public function show_inventory_products($inventory_manager)
    {
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();
        if (!$company) {
            return null;
        }
        return $company->products()->get();
    }
}
