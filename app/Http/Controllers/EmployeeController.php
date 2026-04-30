<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
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
    public function show_delivery_tasks(){
        $delivery_task=$this->employeeService->show_delivery_tasks();
        if (isset($delivery_task['error'])) {
            return response()->json(['message' => $delivery_task['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery tasks retrieved successfully',
            'delivery_tasks' => $delivery_task,
        ]);

    }
    public function proccess_delivery_task(Request $request){
        $validate = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
            'action' => 'required|in:approve,reject',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
            $result=$this->employeeService->proccess_delivery_task($request);
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 400);
            }
            return response()->json([
            'message' => 'Delivery task processed successfully',
            'delivery_task' => $result,
        ]);
    }
        public function deliver_orderList(Request $request){
        $validate = Validator::make($request->all(), [
            'delivery_task_id' => 'required|exists:deliveries,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $result=$this->employeeService->deliver_orderList($request);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Order list marked as delivered successfully',
            'delivery_task' => $result,
        ]);
    }
    public function task_start(Request $request){
        $validate = Validator::make($request->all(), [
            'delivery_task_id' => 'required|exists:deliveries,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }
        $result=$this->employeeService->task_start($request);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery task started successfully',
            'delivery_task' => $result,
        ]);
}
    public function show_orderList_for_inventory_manager()
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
}
