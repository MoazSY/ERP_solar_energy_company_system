<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Order_list;
use App\Models\Products;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function register_employee(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'date_of_birth' => 'required|date',
            'email' => 'required|email',
            'password' => 'required|alpha_num|min:8',
            'phoneNumber' => 'required|regex:/^09\d{8}$/',
            'account_number' => 'sometimes|string',
            'syriatel_cash_phone' => 'sometimes|regex:/^09\d{8}$/',
            'image' => 'sometimes|nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'identification_image' => 'required|mimes:jpg,jpeg,png,webp|max:2048',
            'about_him' => 'sometimes|string',
            'employee_type' => 'required|in:install_technician,metal_base_technician,inventory_manager,driver'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }

        $uniqueRequest = app(StoreUserRequest::class);
        $uniqueRequest->ignoreId = null;
        $uniqueRequest->ignoreTable = null;
        $uniqueRequest->merge([
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
        ]);
        $uniqueRequest->prepareForValidation();
        $data = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        )->validate();

        $result = $this->employeeService->create_internal_employee_request($request, $data);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'employee request created successfully',
            'employee' => $result['employee'],
        ]);
    }

    public function register_employee_company_agency(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:install_technician,metal_base_technician,blacksmith_workshop,driver,inventory_manager',
            'salary_type' => 'required|in:fixed,rate',
            'currency' => 'required|in:USD,SY',
            'work_type' => 'required|in:full_time,task_based',
            'payment_method' => 'sometimes|in:bank_transfer,cash',
            'payment_frequency' => 'sometimes|in:daily,weekly,monthly,after_task',
            'salary_rate' => 'required_if:salary_type,rate|nullable|numeric|min:0',
            'salary_amount' => 'required_if:salary_type,fixed|nullable|numeric|min:0',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->employeeService->register_employee_company_agency($request);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'employee assigned successfully',
            'employee_assignment' => $result,
        ]);
    }

    public function employee_profile()
    {
        $profile = $this->employeeService->employee_profile();

        if (!$profile) {
            return response()->json(['message' => 'employee profile not found'], 404);
        }

        return response()->json([
            'message' => 'employee profile retrieved successfully',
            'profile' => $profile,
        ]);
    }

    public function update_profile(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
            'employee_type' => 'sometimes|in:install_technician,metal_base_technician,inventory_manager,driver',
            'email' => 'sometimes|email',
            'password' => 'sometimes|alpha_num|min:8',
            'phoneNumber' => 'sometimes|regex:/^09\d{8}$/',
            'account_number' => 'sometimes|string',
            'syriatel_cash_phone' => 'sometimes|regex:/^09\d{8}$/',
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'about_him' => 'sometimes|string',
            'identification_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }

        if ($request->filled('phoneNumber')) {
            $intrnalPhone = '963' . substr($request['phoneNumber'], 1);
            $cached_phone = Cache::get('otp_' . $intrnalPhone);
            if (!$cached_phone) {
                return response()->json([
                    'message' => 'OTP expired or not verified'
                ], 400);
            }
            if ($cached_phone['status'] !== 'verified') {
                return response()->json([
                    'message' => 'OTP not verified',
                    'phone' => $cached_phone['status'],
                ], 400);
            }
        }
        if ($request->filled('email')) {
            $cached_email = Cache::get('otp_' . $request['email']);
            if (!$cached_email) {
                return response()->json([
                    'message' => 'OTP expired or not verified'
                ], 400);
            }
            if ($cached_email['status'] !== 'verified') {
                return response()->json([
                    'message' => 'OTP not verified',
                    'email' => $cached_email['status'],
                ], 400);
            }
        }

        $uniqueRequest = app(StoreUserRequest::class);
        $uniqueRequest->ignoreId = Auth::guard('employee')->user()->id;
        $uniqueRequest->ignoreTable = 'employees';

        $uniqueData = [];
        if ($request->has('email')) {
            $uniqueData['email'] = $request->email;
        }
        if ($request->has('phoneNumber')) {
            $uniqueData['phoneNumber'] = $request->phoneNumber;
        }

        $uniqueRequest->merge($uniqueData);
        $uniqueRequest->prepareForValidation();

        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        )->validate();

        $data = array_merge($uniqueValidator, $validate->validated());
        $profile = $this->employeeService->update_profile($request, $data);

        return response()->json([
            'message' => 'employee profile update',
            'profile' => $profile[0],
            'imageUrl' => $profile[1],
            'identification_imageUrl' => $profile[2],
        ]);
    }

    public function filter_employee(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'employee_type' => 'sometimes|in:install_technician,metal_base_technician,inventory_manager,driver',
            'email' => 'sometimes|email',
            'phoneNumber' => 'sometimes|regex:/^09\d{8}$/',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $filters = $validate->validated();
        $employees = $this->employeeService->filter_employee($filters);
        return response()->json([
            'message' => 'employees retrieved successfully',
            'employees' => $employees,
        ]);
    }

    public function show_entity_employees()
    {
        $employees = $this->employeeService->show_entity_employees();

        if (isset($employees['error'])) {
            return response()->json(['message' => $employees['error']], 400);
        }

        return response()->json([
            'message' => 'entity employees retrieved successfully',
            'employees' => $employees,
        ]);
    }

    public function show_delivery_tasks()
    {
        $delivery_task = $this->employeeService->show_delivery_tasks();
        if (isset($delivery_task['error'])) {
            return response()->json(['message' => $delivery_task['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery tasks retrieved successfully',
            'delivery_tasks' => $delivery_task,
        ]);
    }

    public function proccess_delivery_task(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
            'action' => 'required|in:approve,reject',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $result = $this->employeeService->proccess_delivery_task($request);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery task processed successfully',
            'delivery_task' => $result,
        ]);
    }

    public function deliver_orderList(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'delivery_task_id' => 'required|exists:deliveries,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $result = $this->employeeService->deliver_orderList($request);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Order list marked as delivered successfully',
            'delivery_task' => $result,
        ]);
    }

    public function delivery_task_start(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'delivery_task_id' => 'required|exists:deliveries,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $result = $this->employeeService->task_start($request);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery task started successfully',
            'delivery_task' => $result,
        ]);
    }

    public function show_input_orderList_for_inventory_manager()
    {
        $orderLists = $this->employeeService->show_orderList_for_inventory_manager();
        if (isset($orderLists['error'])) {
            return response()->json(['message' => $orderLists['error']], 400);
        }
        return response()->json([
            'message' => 'Order lists retrieved successfully',
            'order_lists' => $orderLists,
        ]);
    }

    public function insert_product_to_stock(Request $request)
    {
        $rules = [
            'product_name' => 'sometimes|string',
            'product_type' => 'sometimes|string|in:solar_panel,inverter,battery,accessory',
            'product_brand' => 'sometimes|string',
            'model_number' => 'sometimes|string',
            'quentity' => 'sometimes|integer|min:0',
            'price' => 'required|numeric|min:0',
            'disscount_type' => 'sometimes|string|in:percentage,fixed',
            'disscount_value' => 'sometimes|numeric|min:0',
            'currency' => 'required|string|in:USD,SY',
            'manufacture_date' => 'sometimes|date',
            'product_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            'with_technical_details' => 'sometimes|boolean',
            'product_name_for_validation' => 'sometimes|string'
        ];

        if ($request->boolean('with_technical_details')) {
            if ($request->input('product_type') === 'battery') {
                $rules = array_merge($rules, [
                    'battery_type' => 'required|string|in:lithium_ion,lead_acid,nickel_cadmium',
                    'capacity_kwh' => 'required|numeric|min:0',
                    'voltage_v' => 'required|string|in:12V,24V,48V',
                    'cycle_life' => 'required|integer|min:0',
                    'warranty_years' => 'required|numeric|min:0',
                    'weight_kg' => 'required|numeric|min:0',
                    'Amperage_Ah' => 'required|string|in:100Ah,200Ah,300Ah',
                    'celles_type' => 'required|string|in:new,renewed',
                    'celles_name' => 'sometimes|string',
                ]);
            } elseif ($request->input('product_type') === 'inverter') {
                $rules = array_merge($rules, [
                    'grid_type' => 'required|string|in:on_grid,off_grid,hybrid',
                    'voltage_v' => 'required|string|in:12V,24V,48V',
                    'grid_capacity_kw' => 'required|numeric|min:0',
                    'solar_capacity_kw' => 'required|numeric|min:0',
                    'inverter_open' => 'required|boolean',
                    'voltage_open' => 'required|numeric|min:0',
                    'weight_kg' => 'required|numeric|min:0',
                    'warranty_years' => 'required|numeric|min:0',
                ]);
            } elseif ($request->input('product_type') === 'solar_panel') {
                $rules = array_merge($rules, [
                    'capacity_kw' => 'required|string|in:250w,300w,350w,400w,580w,620w',
                    'basbar_number' => 'required|numeric|min:0',
                    'is_half_cell' => 'required|boolean',
                    'is_bifacial' => 'required|boolean',
                    'warranty_years' => 'required|numeric|min:0',
                    'weight_kg' => 'required|numeric|min:0',
                    'length_m' => 'required|numeric|min:0',
                    'width_m' => 'required|numeric|min:0',
                ]);
            } else {
                return response()->json(['message' => 'technical details are only supported for battery, inverter, and solar_panel products'], 422);
            }
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $data = $validate->validated();
        $result = $this->employeeService->insert_product_to_stock($request, $data);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        if (!$result) {
            return response()->json(['message' => 'invalid entity type'], 400);
        }
        $operationResult = $result[0];
        $message = ($operationResult['action'] ?? 'created') === 'updated'
            ? 'product quantity updated successfully'
            : 'product added successfully';

        return response()->json([
            'message' => $message,
            'action' => $operationResult['action'] ?? 'created',
            'product' => $operationResult['product'],
            'product_image' => $result[1],
        ]);
    }

    public function add_inventory_product_battery(Request $request, Products $product_id)
    {
        $validate = Validator::make($request->all(), [
            'battery_type' => 'required|string|in:lithium_ion,lead_acid,nickel_cadmium',
            'capacity_kwh' => 'required|numeric|min:0',
            'voltage_v' => 'required|string|in:12V,24V,48V',
            'cycle_life' => 'required|integer|min:0',
            'warranty_years' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'Amperage_Ah' => 'required|string|in:100Ah,200Ah,300Ah',
            'celles_type' => 'required|string|in:new,renewed',
            'celles_name' => 'sometimes|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $data = $validate->validated();
        $result = $this->employeeService->add_inventory_product_battery($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this company'], 400);
        }
        return response()->json(['message' => 'battery details added successfully', 'battery_details' => $result]);
    }

    public function add_inventory_product_inverter(Request $request, Products $product_id)
    {
        $validate = Validator::make($request->all(), [
            'grid_type' => 'required|string|in:on_grid,off_grid,hybrid',
            'voltage_v' => 'required|string|in:12V,24V,48V',
            'grid_capacity_kw' => 'required|numeric|min:0',
            'solar_capacity_kw' => 'required|numeric|min:0',
            'inverter_open' => 'required|boolean',
            'voltage_open' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'warranty_years' => 'required|numeric|min:0',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $data = $validate->validated();
        $result = $this->employeeService->add_inventory_product_inverter($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this company'], 400);
        }
        return response()->json(['message' => 'inverter details added successfully', 'inverter_details' => $result]);
    }

    public function add_inventory_product_solar_panel(Request $request, Products $product_id)
    {
        $validate = Validator::make($request->all(), [
            'capacity_kw' => 'required|string|in:250w,300w,350w,400w,580w,620w',
            'basbar_number' => 'required|numeric|min:0',
            'is_half_cell' => 'required|boolean',
            'is_bifacial' => 'required|boolean',
            'warranty_years' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'length_m' => 'required|numeric|min:0',
            'width_m' => 'required|numeric|min:0',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $data = $validate->validated();
        $result = $this->employeeService->add_inventory_product_solar_panel($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this agency'], 400);
        }
        return response()->json(['message' => 'solar panel details added successfully', 'solar_panel_details' => $result]);
    }

    public function show_inventory_products()
    {
        $products = $this->employeeService->show_inventory_products();
        return response()->json(['message' => 'Stock products retrieved successfully', 'products' => $products]);
    }

    public function update_inventory_product(Request $request, $product_id)
    {
        $product = Products::findOrFail($product_id);

        $rules = [
            'product_id' => 'required|integer|exists:products,id',
            'product_name' => 'sometimes|string',
            'product_type' => 'sometimes|string|in:solar_panel,inverter,battery,accessory',
            'product_brand' => 'sometimes|string',
            'model_number' => 'sometimes|string',
            'quentity' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'disscount_type' => 'sometimes|string|in:percentage,amount',
            'disscount_value' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|in:USD,SY',
            'manufacture_date' => 'sometimes|date',
            'product_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'update_technical_details' => 'sometimes|boolean',
        ];

        $updateTechnical = $request->boolean('update_technical_details');

        if ($product->product_type == 'inverter' && $updateTechnical) {
            $rules = array_merge($rules, [
                'grid_type' => 'sometimes|string|in:on_grid,off_grid,hybrid',
                'voltage_v' => 'sometimes|string|in:12V,24V,48V',
                'grid_capacity_kw' => 'sometimes|numeric|min:0',
                'solar_capacity_kw' => 'sometimes|numeric|min:0',
                'inverter_open' => 'sometimes|boolean',
                'voltage_open' => 'sometimes|numeric|min:0',
                'weight_kg' => 'sometimes|numeric|min:0',
                'warranty_years' => 'sometimes|numeric|min:0',
            ]);
        }

        if ($product->product_type == 'battery' && $updateTechnical) {
            $rules = array_merge($rules, [
                'battery_type' => 'sometimes|string|in:lithium_ion,lead_acid,nickel_cadmium',
                'capacity_kwh' => 'sometimes|numeric|min:0',
                'voltage_v' => 'sometimes|string|in:12V,24V,48V',
                'cycle_life' => 'sometimes|integer|min:0',
                'warranty_years' => 'sometimes|numeric|min:0',
                'weight_kg' => 'sometimes|numeric|min:0',
                'Amperage_Ah' => 'sometimes|string|in:100Ah,200Ah,300Ah',
                'celles_type' => 'sometimes|string|in:new,renewed',
                'celles_name' => 'sometimes|string',
            ]);
        }

        if ($product->product_type == 'solar_panel' && $updateTechnical) {
            $rules = array_merge($rules, [
                'capacity_kw' => 'sometimes|string|in:250w,300w,350w,400w,580w,620w',
                'basbar_number' => 'sometimes|numeric|min:0',
                'is_half_cell' => 'sometimes|boolean',
                'is_bifacial' => 'sometimes|boolean',
                'warranty_years' => 'sometimes|numeric|min:0',
                'weight_kg' => 'sometimes|numeric|min:0',
                'length_m' => 'sometimes|numeric|min:0',
                'width_m' => 'sometimes|numeric|min:0',
            ]);
        }

        $validator = Validator::make(
            array_merge($request->all(), ['product_id' => $product_id]),
            $rules
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 400);
        }

        $data = $validator->validated();

        $result = $this->employeeService->update_inventory_product($request, $data, $product_id);

        if (!$result) {
            return response()->json([
                'message' => 'Product not found or not owned by this agency'
            ], 404);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $result[0],
            'product_image' => $result[1]
        ]);
    }

    public function delete_inventory_product($product_id)
    {
        $validate = Validator::make(['product_id' => $product_id], [
            'product_id' => 'required|integer|exists:products,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->employeeService->delete_inventory_product($product_id);

        if (!$result) {
            return response()->json(['message' => 'Product not found or not owned by this inventory'], 404);
        }

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function delete_inventory_product_details($product_id)
    {
        $validate = Validator::make(['product_id' => $product_id], [
            'product_id' => 'required|integer|exists:products,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->employeeService->delete_inventory_product_details($product_id);

        if (!$result) {
            return response()->json(['message' => 'Product not found or not owned by this inventory'], 404);
        }

        return response()->json(['message' => 'Product detail records deleted successfully']);
    }

    public function filter_inventory_products(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'product_type' => 'nullable|string|in:battery,inverter,solar_panel,accessory',
            'product_name' => 'nullable|string',
            'product_brand' => 'nullable|string',
            'model_number' => 'nullable|string',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string',
            'quentity_min' => 'nullable|integer|min:0',
            'quentity_max' => 'nullable|integer|min:0',
            // Battery details
            'battery_type' => 'nullable|string|in:lithium_ion,lead_acid,nickel_cadmium',
            'capacity_kwh' => 'nullable|numeric|min:0',
            'voltage_v' => 'nullable|string|in:12V,24V,48V',
            'cycle_life_min' => 'nullable|integer|min:0',
            'cycle_life_max' => 'nullable|integer|min:0',
            'warranty_years_min' => 'nullable|numeric|min:0',
            'warranty_years_max' => 'nullable|numeric|min:0',
            'weight_kg_min' => 'nullable|numeric|min:0',
            'weight_kg_max' => 'nullable|numeric|min:0',
            'Amperage_Ah' => 'nullable|string|in:100Ah,200Ah,300Ah',
            'celles_type' => 'nullable|string|in:new,renewed',
            'celles_name' => 'nullable|string',
            // Inverter details
            'grid_type' => 'nullable|string|in:on_grid,off_grid,hybrid',
            'grid_capacity_kw_min' => 'nullable|numeric|min:0',
            'grid_capacity_kw_max' => 'nullable|numeric|min:0',
            'solar_capacity_kw_min' => 'nullable|numeric|min:0',
            'solar_capacity_kw_max' => 'nullable|numeric|min:0',
            'inverter_open' => 'nullable|boolean',
            'voltage_open_min' => 'nullable|numeric|min:0',
            'voltage_open_max' => 'nullable|numeric|min:0',
            // Solar panel details
            'capacity_kw' => 'nullable|string|in:250w,300w,350w,400w,580w,620w',
            'basbar_number_min' => 'nullable|numeric|min:0',
            'basbar_number_max' => 'nullable|numeric|min:0',
            'is_half_cell' => 'nullable|boolean',
            'is_bifacial' => 'nullable|boolean',
            'length_m_min' => 'nullable|numeric|min:0',
            'length_m_max' => 'nullable|numeric|min:0',
            'width_m_min' => 'nullable|numeric|min:0',
            'width_m_max' => 'nullable|numeric|min:0',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $filters = $validate->validated();
        $products = $this->employeeService->filter_inventory_products($filters);

        if (empty($products)) {
            return response()->json(['message' => 'No products found matching the filters', 'data' => []], 200);
        }
        return response()->json(['message' => 'Products retrieved successfully', 'data' => $products], 200);
    }

    public function recieve_cash_from_manager(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'task_id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->employeeService->recieve_cash_from_manager($request->task_id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'Cash received from manager successfully',
            'payment' => $result['payment'],
            'transaction' => $result['transaction'],
        ], 200);
    }

    public function show_output_orderList_for_inventory_manager(){
    // رؤية قائمة الطلبات التي يجب اخراجها او تخصيصها بوقت وتاريخ معين 

    }
    public function proccess_input_output_order_request(Request $request, Order_list $orderlist) {
        // تعيين الحالة والتاريخ الاستجابة اي تعيين انه تم ادخالها او اخراجها بنجاح
        // في حال كانت طلبية اخراج يجب تسجيل الارقام التسلسلية للمنتجات في الطلبية 
    }
    public function create_conflict_invoice(Request $request,$invoice_id){
        // في حال وجود اي مشكلة في الطلبية او في عملية التسليم او الاستلام يتم انشاء فاتورة خلافية لتوثيق المشكلة وحلها
        // يتم تعيين التضارب في الفاتورة المستلمة من الوكالة 
    }
    public function show_installation_tasks(){
        //رؤية مهام التركيب المخصصة للتقنيين مع كافة المعلومات والتفاصيل عن المهمة 

    }
    public function filter_installation_tasks(Request $request){
        // فلترة مهام التركيب حسب الحالة او حسب التاريخ او حسب العميل او حسب نوع النظام او المنتهي او المقبوض اختر ما تراه مناسبا للفلترة
    }
    public function proccess_installation_task(Request $request){
        // معالجة مهمة التركيب من حيث تعيينها كمقبولة او مرفوضة من قبل التقني  
    }
    public function define_solar_system_for_customer(Request $request){
        // في حال قبول مهمة التركيب من قبل التقني يتم تعريف نظام الطاقة الشمسية المناسب للعميل حسب طلبه واحتياجه وامكانياته المادية في حال قام العميل بطلب كشف فني 
    }
    public function installation_task_start(Request $request){
        // بدء مهمة التركيب من قبل التقني وتسجيل الوقت والتاريخ لبداية المهمة
    }
    public function installation_task_complete(Request $request){
    /*
        اكمال مهمة التركيب من قبل التقني وتسجيل الوقت والتاريخ لنهاية المهمة 
    تسجيل الملاحظات وتصوير المنظومة اذا كان فني تركيب ورفع صور القاعدة اذا كان فني قواعد 
    اذا كان فني تركيب يجب مسح QR code  لاتمام التركيب 
    */
    }
    public function define_system_attachments(Request $request){
        /*
        عندما يستلم الفني مهمة التركيب ويوافق عليها 
        بعد ما يطلع على قائمة الطلبات يستطيع اضافة قائمة من المنتجات ليقوم مدير المستودع لاحقا بتعديل قائمة الطلبات استجابة لطلب الفني
        التي هي غالبا من فئة الاكسوارات اي المرفقات للطاقة
        القائمة في جدول products technicians مع ربطها بمهمة التركيب وبالعميل وبالطلبية وبالفاتورة
        */
    }

    public function show_system_attachments_for_technician(Request $request){
    // رؤية القائمة التي يريدها الفني من المرفقات الاضافية عن الطلبية او الفاتورة 
    }
    public function extract_attachments(Request $request){
    // ادخالها للطلبية او تعديل كميات او حذفها حسب الحاجة طبعا الذي يقوم بذلك مدير المستودع
    }
    public function register_consumable_material(Request $request){
    // تسجيل المستهلكات التي استهلكها الفني في التركيب عند الفني اي المرفقات من كابلات وقواطع ومستلزمات واكسسوارت كهربائية
    }
    public function update_consumable_material(Request $request){
    // امكانية تعديلها او كمياتها او حذف البعض 
    }
    public function recieve_cash_from_customer(Request $request){
    // في حال كان العميل سيدفع كاش عند التسليم يقوم الفني بتسجيل استلام المبلغ من العميل وتوثيقه في النظام
    }
    public function show_profits_from_installation_tasks(Request $request){
    // رؤية الارباح التي حققها من مهام التركيب التي قام بها 
    }
    public function filter_profits_from_installation_tasks(Request $request){
    // فلترة الارباح حسب التاريخ او حسب العميل او حسب نوع النظام او المنتهي او المقبوض اختر ما تراه مناسبا للفلترة تاريخ يومي شهري 
    // من شركة محددة فلتر بما تراه مناسبا 
    }



















}
