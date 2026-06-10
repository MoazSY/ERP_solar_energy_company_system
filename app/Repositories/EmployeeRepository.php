<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Company_agency_employee;
use App\Models\Conflict_invoice;
use App\Models\Consumables;
use App\Models\Deliveries;
use App\Models\Employee;
use App\Models\Input_output_request;
use App\Models\Items;
use App\Models\Order_list;
use App\Models\Product_techicians;
use App\Models\Products;
use App\Models\Project_task;
use App\Models\Project_warranties;
use App\Models\Purchase_invoice;
use App\Models\Request_solar_system;
use App\Models\Solar_company;
use App\Models\Subscribe_offer;
use App\Support\RatingHelper;
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
            ->with(['employee.projectTasks'])
            ->latest('id')
            ->get();

        return $assignments->map(function ($assignment) {
            $employee = $assignment->employee;

            return [
                'assignment' => $assignment,
                'employee' => $employee,
                'rating' => $employee ? RatingHelper::forEmployee($employee->id) : RatingHelper::summarize(collect()),
                'rejected_tasks_count' => ($employee?->projectTasks ?? collect())->filter(function ($task) {
                    return $task->rejected_at !== null || $task->task_accepted === false;
                })->count(),
                'imageUrl' => $employee?->image ? asset('storage/' . $employee->image) : null,
                'identification_imageUrl' => $employee?->identification_image ? asset('storage/' . $employee->identification_image) : null,
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

    public function start_installation_task($employee, $task_id)
    {
        $task = Project_task::find($task_id);

        if (!$task) {
            return ['error' => 'Installation task not found'];
        }

        if ((int) $task->employee_id !== (int) $employee->id) {
            return ['error' => 'Unauthorized: This task is not assigned to you'];
        }

        if ($task->task_accepted != true) {
            return ['error' => 'Installation task has not been accepted yet'];
        }

        if ($task->started_at != null) {
            return ['error' => 'This installation task has already been started'];
        }

        $task->started_at = now();
        $task->task_status = 'in_progress';
        $task->save();

        return $task->load(['employee', 'company', 'taskable']);
    }

    public function installation_task_complete($employee, $task_id, array $data)
    {
        return DB::transaction(function () use ($employee, $task_id, $data) {
            $task = Project_task::find($task_id);

            if (!$task) {
                return ['error' => 'Installation task not found'];
            }

            if ((int) $task->employee_id !== (int) $employee->id) {
                return ['error' => 'Unauthorized: This task is not assigned to you'];
            }

            if ($task->task_status === 'completed' || $task->completed_at != null) {
                return ['error' => 'This installation task has already been completed'];
            }

            $isInspection = ($task->task_type_new ?? $task->task_type) === 'technical_inspection';
            $taskable = $task->taskable;
            $expectedSerial = null;

            if ($taskable instanceof Purchase_invoice) {
                $projectWarranty = Project_warranties::where('invoice_id', $taskable->id)
                    ->latest('id')
                    ->first();

                if ($projectWarranty && isset($projectWarranty->project_serial_number)) {
                    $expectedSerial = trim((string) $projectWarranty->project_serial_number);
                }
            }

            if (!$isInspection && $employee->employee_type === 'install_technician') {
                if (empty($data['system_sn'])) {
                    return ['error' => 'System serial (system_sn) is required to complete this installation task'];
                }

                if (empty($data['images']) || !is_array($data['images']) || count($data['images']) === 0) {
                    return ['error' => 'At least one image is required to complete this installation task'];
                }

                if ($expectedSerial !== null) {
                    $enteredSerial = trim((string) $data['system_sn']);
                    if ($enteredSerial === '') {
                        return ['error' => 'System serial cannot be empty'];
                    }
                    if (strcasecmp($enteredSerial, $expectedSerial) !== 0) {
                        return ['error' => 'The entered system serial does not match the serial assigned by the company manager'];
                    }
                } else {
                    return ['error' => 'No system serial is defined for this task by the company manager'];
                }
            }

            $existingImages = [];
            if (!empty($task->task_images)) {
                $existingImages = is_array($task->task_images) ? $task->task_images : (json_decode($task->task_images, true) ?? []);
            }

            if (!empty($data['images']) && is_array($data['images'])) {
                $existingImages = array_merge($existingImages, $data['images']);
            }

            $notes = $task->employee_notes ?? '';
            if (!empty($data['employee_notes'])) {
                $notes = trim($notes . "\n" . $data['employee_notes']);
            }
            if (!empty($data['system_sn'])) {
                $notes = trim($notes . "\n" . 'system_sn: ' . $data['system_sn']);
            }

            $task->task_images = $existingImages;
            $task->employee_notes = $notes;
            $task->completed_at = now();
            $task->task_status = 'completed';
            $task->save();

            $task->setAttribute('task_images_urls', array_map(fn($p) => asset('storage/' . $p), $existingImages));

            return $task->fresh();
        });
    }

    public function define_solar_system_for_customer($employee, $task_id, array $data)
    {
        return DB::transaction(function () use ($employee, $task_id, $data) {
            $task = Project_task::find($task_id);

            if (!$task || $task->task_type_new != 'technical_inspection') {
                return ['error' => 'Installation task not found'];
            }

            if ((int) $task->employee_id !== (int) $employee->id) {
                return ['error' => 'Unauthorized: This task is not assigned to you'];
            }

            if ($task->task_accepted != true) {
                return ['error' => 'Installation task has not been accepted yet'];
            }

            // $customer = $task->orderList?->request_entity;//
            $customer = $task->taskable?->buyer_entity;
            if (!$customer || !($customer instanceof \App\Models\Customer)) {
                return ['error' => 'Customer not found for this installation task'];
            }

            $company = $task->company;
            if (!$company) {
                return ['error' => 'Company not found for this installation task'];
            }

            $payload = array_filter($data, fn($value) => !is_null($value));
            $payload['customer_id'] = $customer->id;
            $payload['company_id'] = $company->id;

            $solarSystem = $customer
                ->requestSolarSystems()
                ->where('company_id', $company->id)
                ->first();

            if ($solarSystem) {
                $solarSystem->update($payload);
                $solarSystem->save();
            } else {
                $solarSystem = Request_solar_system::create($payload);
            }

            return $solarSystem->load(['customer', 'company']);
        });
    }

    public function show_orderList_for_inventory_manager($employee)
    {
        $input_output_request = Input_output_request::query()
            ->where('inventory_manager_id', $employee->id)
            ->where('request_type', 'input')
            ->with(['order', 'order.request_entity', 'order.Items', 'order.Items.product', 'order.Items.product.inverters', 'order.Items.product.batteries'])
            ->get();
        return $input_output_request;
    }

    public function show_output_orderList_for_inventory_manager($employee)
    {
        $outputRequests = Input_output_request::query()
            ->where('inventory_manager_id', $employee->id)
            ->where('request_type', 'output')
            // ->with([
            //     // // 'inventoryManager',
            //     // 'order.request_entity',
            //     // 'order.orderable_entity',
            //     // 'order.Items',
            //     // 'order.Items.product',
            //     // 'order.Items.product.inverters',
            //     // 'order.Items.product.batteries',
            //     // 'order.Items.product.solarPanals',
            //     // 'invoice.seller_entity',
            //     // 'invoice.buyer_entity',
            //     'invoice.object_entity.Items',
            // ])
            ->latest('request_datetime')
            ->latest('id')
            ->get();

        return $outputRequests->map(function ($request) {
            $invoiceItems = $request->invoice?->object_entity_type === Subscribe_offer::class
                ? ($request->invoice?->object_entity?->Items()->with(['product', 'product.inverters', 'product.batteries', 'product.solarPanals'])->get() ?? collect())
                : ($request->invoice?->object_entity?->Items->load(['product', 'product.inverters', 'product.batteries', 'product.solarPanals']) ?? collect());

            return [
                'output_request' => $request,
                // 'order_list' => $request->order,
                'items' => $invoiceItems,
                'invoice' => $request->invoice,
                'status' => $request->status,
                'request_datetime' => $request->request_datetime,
                'notes' => $request->notes,
            ];
        });
    }

    public function create_conflict_invoice(array $data, $invoice_id, $company, $employee)
    {
        return DB::transaction(function () use ($data, $invoice_id, $company, $employee) {
            $invoice = Purchase_invoice::with(['input_output_requests', 'seller_entity'])
                ->where('id', $invoice_id)
                ->where('buyer_entity_type', Solar_company::class)
                ->where('buyer_entity_id', $company->id)
                ->where('seller_entity_type', Agency::class)
                ->first();

            if (!$invoice) {
                return ['error' => 'Invoice not found or not received from an agency for your company'];
            }

            $inputRequestExists = $invoice
                ->input_output_requests()
                ->where('request_type', 'input')
                ->whereIn('status', ['pending', 'problem'])
                ->exists();

            if (!$inputRequestExists) {
                return ['error' => 'No related input shipment with pending or problem status found for this invoice'];
            }

            $agencyId = $invoice->seller_entity_id;
            if (!$agencyId) {
                return ['error' => 'Invoice seller agency not found'];
            }

            $conflictInvoice = Conflict_invoice::create([
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'agency_id' => $agencyId,
                'conflict_type' => $data['conflict_type'],
                'conflict_amount' => $data['conflict_amount'] ?? null,
                'conflict_description' => $data['conflict_description'] ?? null,
                'image_related' => $data['image_related'] ?? null,
                'conflict_state' => $data['conflict_state'] ?? 'pending',
            ]);

            return $conflictInvoice->load('invoice', 'agency')->setAttribute('imageUrl', $conflictInvoice->image_related ? asset('storage/' . $conflictInvoice->image_related) : null);
        });
    }

    public function proccess_input_output_order_request($data, $inputOutputRequest, $company, $employee)
    {
        return DB::transaction(function () use ($data, $inputOutputRequest, $company, $employee) {
            // Authorization: only assigned manager or unassigned
            if ($inputOutputRequest->inventory_manager_id && (int) $inputOutputRequest->inventory_manager_id !== (int) $employee->id) {
                return ['error' => 'Unauthorized'];
            }

            if ($inputOutputRequest->status === 'ready') {
                return ['error' => 'This request has already been processed'];
            }

            // get items associated with this request (from invoice.object_entity or order)
            $items = $this->fetchItemsForRequest($inputOutputRequest);
            if ($items->isEmpty()) {
                return ['error' => 'No items found for this request'];
            }

            $serialNumbers = $data['serial_numbers'] ?? [];
            $status = $data['status'] ?? 'pending';

            if ($status === 'ready') {
                if ($inputOutputRequest->request_type === 'output') {
                    $this->decreaseStockForOutputItems($items);
                }

                $saveResult = $this->saveSerialsForItems($items, $serialNumbers);
                if (isset($saveResult['error'])) {
                    return $saveResult;
                }
                $inputOutputRequest->ready_datetime = now();
            }

            $inputOutputRequest->status = $status;
            $inputOutputRequest->notes = $data['notes'] ?? $inputOutputRequest->notes;
            $inputOutputRequest->save();

            $invoiceItems = $inputOutputRequest->invoice?->object_entity_type === Subscribe_offer::class
                ? ($inputOutputRequest->invoice?->object_entity?->Items()->with(['product', 'product.inverters', 'product.batteries', 'product.solarPanals'])->get() ?? collect())
                : ($inputOutputRequest->invoice?->object_entity?->Items->load(['product', 'product.inverters', 'product.batteries', 'product.solarPanals']) ?? collect());

            $inputOutputRequest->load([
                // 'inventoryManager',
                'invoice.seller_entity',
                'invoice.buyer_entity',
                'invoice.object_entity',
                // 'order.Items.product',
                // 'invoice.object_entity'
            ]);
            $inputOutputRequest->setAttribute('items', $invoiceItems);
            return $inputOutputRequest;
        });
    }

    /**
     * Return a collection of Items for given Input_output_request.
     * For output requests items are read from the related invoice's object (order or subscription).
     */
    private function fetchItemsForRequest(Input_output_request $request)
    {
        $invoice = $request->invoice;
        if (!$invoice) {
            return collect();
        }

        if ($invoice->object_entity_type === Order_list::class) {
            $orderList = $invoice->orderList ?: $invoice->object_entity;
            if (!$orderList) {
                return collect();
            }
            $orderList->loadMissing(['Items.product']);
            return $orderList->Items ?? collect();
        }

        if ($invoice->object_entity_type === Subscribe_offer::class) {
            $subscription = $invoice->object_entity;
            if (!$subscription) {
                return collect();
            }
            return $subscription->Items()->with('product')->get();
        }

        return collect();
    }

    /**
     * Save multiple serial strings per item.
     * Expects $serialNumbers as [item_id => [serial1, serial2, ...]].
     */
    private function saveSerialsForItems($items, array $serialNumbers)
    {
        foreach ($items as $item) {
            $serial = $serialNumbers[$item->id] ?? null;

            if ($serial === null) {
                continue;
            }

            if (!is_array($serial)) {
                $serial = [$serial];
            }

            $cleanSerials = collect($serial)
                ->map(function ($serialNumber) {
                    return is_string($serialNumber) ? trim($serialNumber) : $serialNumber;
                })
                ->filter(fn($serialNumber) => $serialNumber !== '' && $serialNumber !== null)
                ->values()
                ->all();

            if (empty($cleanSerials)) {
                continue;
            }

            $item->serial_numbers = json_encode($cleanSerials);
            $item->save();
        }
        return ['ok' => true];
    }

    /**
     * Decrease stock for output items by the available quantity only.
     * If stock is empty, the item is skipped without failing the request.
     */
    private function decreaseStockForOutputItems($items): void
    {
        foreach ($items as $item) {
            $requestedQuantity = (int) ($item->quantity ?? 1);
            if ($requestedQuantity <= 0) {
                continue;
            }

            $product = $item->product ?? Products::find($item->product_id);
            if (!$product) {
                continue;
            }

            $availableQuantity = (int) ($product->quentity ?? 0);
            if ($availableQuantity <= 0) {
                continue;
            }

            $decrementQuantity = min($availableQuantity, $requestedQuantity);
            $product->quentity = $availableQuantity - $decrementQuantity;
            $product->save();
        }
    }

    /**
     * If the related invoice points to an Order_list, mark it discharged now.
     */
    public function insert_product_to_stock($data, $company)
    {
        return DB::transaction(function () use ($data, $company) {
            if (isset($data['product_name_for_validation'])) {
                $pruduct = $company->products()->where('product_name', $data['product_name_for_validation'])->first();
                if ($pruduct) {
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
        if (isset($data['update_technical_details']) && $data['update_technical_details'] == true) {
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

    public function filter_installation_tasks($employee, array $filters)
    {
        $query = $employee->projectTasks();

        if (!empty($filters['task_id'])) {
            $query->where('id', $filters['task_id']);
        }

        if (!empty($filters['task_type'])) {
            $query->where('task_type', $filters['task_type']);
        }

        if (!empty($filters['task_status'])) {
            $query->where('task_status', $filters['task_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('sheduled_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('sheduled_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['customer_name'])) {
            $customerName = '%' . $filters['customer_name'] . '%';
            $query->whereHas('orderList', function ($orderListQuery) use ($customerName) {
                $orderListQuery
                    ->where('customer_first_name', 'like', $customerName)
                    ->orWhere('customer_last_name', 'like', $customerName);
            });
        }

        if (array_key_exists('is_completed', $filters)) {
            if ($filters['is_completed']) {
                $query->whereNotNull('completed_at')->orWhere('task_status', 'completed');
            } else {
                $query->whereNull('completed_at');
            }
        }

        if (array_key_exists('payment_received', $filters)) {
            $query->where('payment_received', (bool) $filters['payment_received']);
        }

        if (!empty($filters['min_fee'])) {
            $query->where('task_fee', '>=', $filters['min_fee']);
        }

        if (!empty($filters['max_fee'])) {
            $query->where('task_fee', '<=', $filters['max_fee']);
        }

        return $query->with(['employee', 'taskable', 'customerRateFeedbacks.customer'])->latest('id')->get()->map(function ($task) {
            return RatingHelper::appendTaskRatings([
                'task' => $task->toArray(),
                'employee' => $task->employee?->toArray(),
                'taskable' => $task->taskable?->toArray(),
                'offer' => $task->taskable?->object_entity?->offer?->toArray(),
                'item' => $task->taskable?->object_entity?->offer?->Items->toArray(),
                'order_list' => $task->orderList?->toArray(),
                'customer' => $task->taskable?->buyer_entity?->toArray(),
                'address' => $task->taskable?->buyer_entity?->addresses()?->latest('id')->first(),
                'is_completed' => !is_null($task->completed_at),
            ], $task);
        });
    }

    public function proccess_installation_task($employee, $task_id, array $data)
    {
        return DB::transaction(function () use ($employee, $task_id, $data) {
            $task = Project_task::find($task_id);

            if (!$task) {
                return ['error' => 'Installation task not found'];
            }

            if ((int) $task->employee_id !== (int) $employee->id) {
                return ['error' => 'Unauthorized: This task is not assigned to you'];
            }

            if ($task->task_accepted === true) {
                return ['error' => 'This task has already been processed'];
            }

            $action = $data['action'] ?? null;

            if ($action === 'accept') {
                $task->task_accepted = true;
                $task->accepted_at = now();
                $task->task_status = 'pending';
                $task->rejected_reason = null;
            } elseif ($action === 'reject') {
                $task->task_accepted = false;
                $task->rejected_at = now();
                $task->rejected_reason = $data['rejected_reason'] ?? null;
                $task->task_status = 'pending';
            } else {
                return ['error' => 'Invalid action. Must be accept or reject'];
            }

            if (!empty($data['employee_notes'])) {
                $task->employee_notes = $data['employee_notes'];
            }

            $task->save();
            $task->refresh();

            return $task->load(['employee', 'company', 'taskable', 'orderList.request_entity']);
        });
    }

    public function recieve_cash_from_customer($employee, $task_id)
    {
        return DB::transaction(function () use ($employee, $task_id) {
            $task = Project_task::find($task_id);

            if (!$task) {
                return ['error' => 'Project task not found'];
            }

            if ((int) $task->employee_id !== (int) $employee->id) {
                return ['error' => 'This project task is not assigned to the current employee'];
            }

            if ($task->payment_method !== 'cash') {
                return ['error' => 'This task is not configured for cash payment'];
            }

            if ($task->payment_status !== 'pending') {
                return ['error' => 'Only pending cash payments can be confirmed'];
            }

            if ($task->payment_received) {
                return ['error' => 'Cash has already been received for this task'];
            }

            $task->payment_received = true;
            $task->payment_status = 'client_paid';
            $task->save();

            return $task->fresh()->load(['employee', 'company', 'orderList']);
        });
    }

    public function show_inventory_products($inventory_manager)
    {
        $company = $inventory_manager->companyAgencyEmployees()->first()->entityType()->first();
        if (!$company) {
            return null;
        }
        return $company->products()->get();
    }

    public function filter_profits_from_installation_tasks($employee, array $filters)
    {
        $query = $employee
            ->projectTasks()
            ->where('task_status', 'completed')
            ->whereNotNull('completed_at');

        if (!empty($filters['date_from'])) {
            $query->whereDate('completed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('completed_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['task_type'])) {
            $query->where('task_type_new', $filters['task_type']);
        }
        if (!empty($filters['company_name'])) {
            $companyName = '%' . $filters['company_name'] . '%';
            $query->whereHas('company', function ($companyQuery) use ($companyName) {
                $companyQuery->where('company_name', 'like', $companyName);
            });
        }

        $tasks = $query->latest('completed_at')->get();

        $totalProfit = 0;

        $tasks = $tasks->map(function ($task) use (&$totalProfit) {
            $profit = $task->task_fee ?? 0;
            $totalProfit += $profit;
            $task->loadMissing('customerRateFeedbacks.customer');

            return RatingHelper::appendTaskRatings([
                'task' => $task->load('company'),
                'task_type' => $task->task_type ?? $task->task_type_new,
                'completed_at' => $task->completed_at,
                'profit' => $task->task_fee,
            ], $task);
        });

        return [
            'tasks' => $tasks,
            'total_profit' => $totalProfit,
            'tasks_count' => count($tasks),
        ];
    }

    public function filter_system_attachments(array $filters, $employee)
    {
        $query = Product_techicians::with([
            'item.product.inverters',
            'item.product.batteries',
            'item.product.solarPanals',
            'task',
            'technician',
        ]);

        // الفني العادي يرى مرفقاته فقط — المدير يستطيع تمرير technician_id لرؤية مرفقات فني معين
        if ($employee) {
            $query->where('technician_id', $employee->id);
        }

        if (!empty($filters['technician_id'])) {
            $query->where('technician_id', $filters['technician_id']);
        }

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['product_type'])) {
            $query->whereHas('item.product', function ($q) use ($filters) {
                $q->where('product_type', $filters['product_type']);
            });
        }

        if (!empty($filters['product_name'])) {
            $query->whereHas('item.product', function ($q) use ($filters) {
                $q->where('product_name', 'like', '%' . $filters['product_name'] . '%');
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['extracted'])) {
            $query->where('extract_item', $filters['extracted']);
        }

        $attachments = $query->latest('id')->get();
        $taskIds = $attachments->pluck('task_id')->unique();

        $consumablesByTaskAndItem = Consumables::select('task_id', 'item_id', DB::raw('SUM(quantity_consume) as total_consumed'))
            ->whereIn('task_id', $taskIds)
            ->groupBy('task_id', 'item_id')
            ->get()
            ->groupBy('task_id')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    return [$item->item_id => (int) $item->total_consumed];
                });
            });

        return $attachments->groupBy('task_id')->map(function ($attachments, $taskId) use ($consumablesByTaskAndItem) {
            $attachmentsWithRemaining = $attachments->map(function ($attachment) use ($taskId, $consumablesByTaskAndItem) {
                $item = $attachment->item;
                $consumed = (int) ($consumablesByTaskAndItem[$taskId][$item->id] ?? 0);
                $remaining = max(0, (int) ($item->quantity ?? 0) - $consumed);

                return [
                    'attachment' => $attachment,
                    'consumed_quantity' => $consumed,
                    'remaining_quantity' => $remaining,
                    'has_consumables' => $consumed > 0,
                ];
            });

            return [
                'task_id' => $taskId,
                'attachments' => $attachmentsWithRemaining,
            ];
        })->values();
    }

    public function show_product_nearing_out_of_stock($company, int $threshold)
    {
        return Products::where('entity_type_type', Solar_company::class)
            ->where('entity_type_id', $company->id)
            ->where('quentity', '<=', $threshold)
            ->orderBy('quentity')
            ->with(['inverters', 'batteries', 'solarPanals'])
            ->get();
    }

    public function define_system_attachments($employee, $task, array $products)
    {
        return DB::transaction(function () use ($employee, $task, $products) {
            // حذف التعيينات القديمة (إذا أردت الاستبدال)
            // $task->productTechicians()->where('technician_id', $employee->id)->delete();

            $inventoryManager = Company_agency_employee::where('entity_type_type', Solar_company::class)
                ->where('entity_type_id', $task->company_id)
                ->where('role', 'inventory_manager')
                ->first();

            $itemsarray = [];
            $task_type_object = $task->taskable->object_entity;

            foreach ($products as $productarray) {
                $product = Products::findOrFail($productarray['id']);
                $unitPrice = (float) $product->price;
                $lineSubTotal = $unitPrice * $productarray['quantity'];

                $unitDiscountAmount = (float) ($product->disscount_value ?? 0);
                $lineDiscount = $product->disscount_type === 'percentage'
                    ? (($unitDiscountAmount / 100) * $lineSubTotal)
                    : ($unitDiscountAmount * $productarray['quantity']);

                if ($task_type_object instanceof \App\Models\Subscribe_offer) {
                    $item = $task_type_object->offer->Items()->create([
                        'product_id' => $product->id,
                        'item_name_snapshot' => $product->product_name,
                        'quantity' => $productarray['quantity'],
                        'unit_price' => $product->price,
                        'total_price' => max($lineSubTotal - $lineDiscount, 0),
                        'unit_discount_amount' => $unitDiscountAmount,
                        'total_discount_amount' => $lineDiscount,
                        'discount_type' => $product->disscount_type,
                        'currency' => $product->currency,
                    ]);
                } else {
                    // هنا تم إصلاح الخطأ: تخزين النتيجة في $item
                    $item = $task_type_object->Items()->create([
                        'product_id' => $product->id,
                        'item_name_snapshot' => $product->product_name,
                        'quantity' => $productarray['quantity'],
                        'unit_price' => $product->price,
                        'total_price' => max($lineSubTotal - $lineDiscount, 0),
                        'unit_discount_amount' => $unitDiscountAmount,
                        'total_discount_amount' => $lineDiscount,
                        'discount_type' => $product->disscount_type,
                        'currency' => $product->currency,
                    ]);
                }
                $itemsarray[] = $item->id;
            }

            $created = [];
            foreach ($itemsarray as $item_id) {
                $created[] = Product_techicians::create([
                    'technician_id' => $employee->id,
                    'task_id' => $task->id,
                    'item_id' => $item_id,
                    'inventory_manager_id' => $inventoryManager->id ?? null,
                ]);
            }

            // إصلاح تحميل العلاقات
            return (new \Illuminate\Database\Eloquent\Collection($created))->load('item.product');
        });
    }

    public function extract_system_attachments($employee, int $task_id)
    {
        $task = Project_task::find($task_id);

        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $company = $employee->companyAgencyEmployees()->first()?->entityType()->first();

        if (!$company || $company->id !== $task->company_id) {
            return ['error' => 'Task not found or not associated with your company'];
        }

        $attachments = Product_techicians::with(['item.product'])
            ->where('task_id', $task_id)
            ->where('extract_item', false)
            ->get();

        if ($attachments->isEmpty()) {
            return ['error' => 'No pending attachments found for extraction'];
        }

        foreach ($attachments as $attachment) {
            $item = $attachment->item;
            if (!$item) {
                return ['error' => "Missing item record for attachment id {$attachment->id}"];
            }

            $product = $item->product;
            if (!$product) {
                return ['error' => "Missing product record for item id {$item->id}"];
            }

            $quantity = (int) ($item->quantity ?? 0);
            if ($quantity <= 0) {
                return ['error' => "Invalid item quantity for item id {$item->id}"];
            }

            $availableQuantity = (int) ($product->quentity ?? 0);
            if ($availableQuantity < $quantity) {
                return ['error' => "Insufficient stock for product {$product->product_name}. Available {$availableQuantity}, required {$quantity}"];
            }
        }

        return DB::transaction(function () use ($attachments, $employee) {
            foreach ($attachments as $attachment) {
                $item = $attachment->item;
                $product = $item->product;
                $quantity = (int) ($item->quantity ?? 0);

                $product->quentity = max(0, (int) $product->quentity - $quantity);
                $product->save();

                $attachment->extract_item = true;
                $attachment->inventory_manager_id = $employee->id;
                $attachment->save();
            }

            return $attachments->groupBy('task_id')->map(function ($group, $taskId) {
                return [
                    'task_id' => $taskId,
                    'attachments' => $group,
                ];
            })->values();
        });
    }

    public function register_consumable_material($employee, int $task_id, array $consumables)
    {
        $task = Project_task::find($task_id);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        if ((int) $task->employee_id !== (int) $employee->id) {
            return ['error' => 'Unauthorized: This task is not assigned to you'];
        }

        $invoice = $this->findTaskInvoice($task);
        if (!$invoice) {
            return ['error' => 'No invoice found for this installation task'];
        }

        $payload = [];
        foreach ($consumables as $consumable) {
            $item = Items::find($consumable['item_id']);
            if (!$item) {
                return ['error' => "Consumable item not found: {$consumable['item_id']}"];
            }

            $attachment = Product_techicians::where('task_id', $task_id)
                ->where('item_id', $item->id)
                ->first();

            if (!$attachment) {
                return ['error' => "Item {$item->id} is not registered as an attachment for this task"];
            }

            $quantityConsume = (int) $consumable['quantity_consume'];
            if ($quantityConsume <= 0) {
                return ['error' => 'Consumable quantity must be greater than zero'];
            }

            if ($quantityConsume > (int) $item->quantity) {
                return ['error' => "Consumable quantity {$quantityConsume} exceeds available item quantity {$item->quantity}"];
            }

            $payload[] = [
                'item' => $item,
                'quantity_consume' => $quantityConsume,
            ];
        }

        return DB::transaction(function () use ($employee, $task_id,$task, $payload, $invoice) {
            foreach ($payload as $entry) {
                Consumables::create([
                    'technician_id' => $employee->id,
                    'task_id' => $task_id,
                    'item_id' => $entry['item']->id,
                    'quantity_consume' => $entry['quantity_consume'],
                ]);
            }

            $consumableItems = Consumables::with('item')
                ->where('task_id', $task_id)
                ->get();

            $totalAmount = $consumableItems->reduce(function ($carry, $consumable) {
                return $carry + ($consumable->quantity_consume * ((float) $consumable->item->unit_price ?? 0));
            }, 0);

            $originalAmount = (float) ($invoice->consumables_amount ?? 0);
            $invoice->consumables_amount = $totalAmount;
            if ($invoice->total_amount !== null) {
                $invoice->total_amount = max(0, (float) $invoice->total_amount + ($totalAmount - $originalAmount));
            }
            $invoice->save();
            $task->client_additional_cost_amount=$totalAmount;
            $task->save();
            return [
                'consumables' => $consumableItems,
                'invoice' => $invoice,
            ];
        });
    }

    public function update_consumable_material($employee, int $task_id, array $consumables)
    {
        $task = Project_task::find($task_id);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        if ((int) $task->employee_id !== (int) $employee->id) {
            return ['error' => 'Unauthorized: This task is not assigned to you'];
        }

        $invoice = $this->findTaskInvoice($task);
        if (!$invoice) {
            return ['error' => 'No invoice found for this installation task'];
        }

        $payload = [];
        foreach ($consumables as $consumable) {
            $record = Consumables::find($consumable['id']);
            if (!$record || (int) $record->task_id !== $task_id) {
                return ['error' => "Consumable record not found: {$consumable['id']}"];
            }

            $delete = !empty($consumable['delete']) || (isset($consumable['quantity_consume']) && (int) $consumable['quantity_consume'] <= 0);
            $quantityConsume = null;

            if (!$delete && isset($consumable['quantity_consume'])) {
                $quantityConsume = (int) $consumable['quantity_consume'];
                if ($quantityConsume <= 0) {
                    return ['error' => 'Consumable quantity must be greater than zero when updating'];
                }

                if ($quantityConsume > (int) $record->item->quantity) {
                    return ['error' => "Consumable quantity {$quantityConsume} exceeds available item quantity {$record->item->quantity}"];
                }
            }

            $payload[] = [
                'record' => $record,
                'delete' => $delete,
                'quantity_consume' => $quantityConsume,
            ];
        }

        return DB::transaction(function () use ($payload, $invoice, $task, $task_id) {
            foreach ($payload as $entry) {
                $record = $entry['record'];

                if ($entry['delete']) {
                    $record->delete();
                    continue;
                }

                if ($entry['quantity_consume'] !== null) {
                    $record->quantity_consume = $entry['quantity_consume'];
                    $record->save();
                }
            }

            $consumableItems = Consumables::with('item')
                ->where('task_id', $task_id)
                ->get();

            $totalAmount = $consumableItems->reduce(function ($carry, $consumable) {
                return $carry + ($consumable->quantity_consume * ((float) $consumable->item->unit_price ?? 0));
            }, 0);

            $originalAmount = (float) ($invoice->consumables_amount ?? 0);
            $invoice->consumables_amount = $totalAmount;
            if ($invoice->total_amount !== null) {
                $invoice->total_amount = max(0, (float) $invoice->total_amount + ($totalAmount - $originalAmount));
            }
            $invoice->save();
            $task->client_additional_cost_amount = $totalAmount;
            $task->save();  
            return [
                'consumables' => $consumableItems,
                'invoice' => $invoice,
            ];
        });
    }

    private function findTaskInvoice(Project_task $task)
    {
        $taskable = $task->taskable;
        if ($taskable instanceof Purchase_invoice) {
            return $taskable;
        }

        return null;
    }
}
