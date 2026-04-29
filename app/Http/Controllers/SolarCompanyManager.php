<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterAgencyRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\Order_list;
use App\Models\Solar_company;
use App\Services\SolarCompanyManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SolarCompanyManager extends \App\Http\Controllers\Controller
{
    protected $solarCompanyManagerService;

    public function __construct(SolarCompanyManagerService $solarCompanyManagerService)
    {
        $this->solarCompanyManagerService = $solarCompanyManagerService;
    }

    public function Register(Request $request)
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
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $intrnalPhone = '963' . substr($request['phoneNumber'], 1);
        $cached_phone = Cache::get('otp_' . $intrnalPhone);
        $cached_email = Cache::get('otp_' . $request['email']);
        if (!$cached_phone || !$cached_email) {
            return response()->json([
                'message' => 'OTP expired or not verified'
            ], 400);
        }
        $uniqueRequest = app(StoreUserRequest::class);
        $uniqueRequest->ignoreId = null;
        $uniqueRequest->ignoreTable = null;
        $uniqueRequest->merge([
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
        ]);
        $uniqueRequest->prepareForValidation();
        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        );
        $data = $uniqueValidator->validate();
        $result = $this->solarCompanyManagerService->register($request, $data);
        return response()->json(['message' => 'company manager register successfully', 'company_manager' => $result['company_manager'], 'imageUrl' => $result['imageUrl'], 'token' => $result['token'], 'refresh_token' => $result['refresh_token'], 'identification_image_URL' => $result['identification_image_URL']]);
    }

    public function Company_register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'company_name' => 'required|string',
            'company_logo' => 'sometimes|nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'commerical_register_number' => 'required|string',
            'company_description' => 'sometimes|string',
            'company_email' => 'required|email',
            'company_phone' => 'required|regex:/^09\d{8}$/',
            'tax_number' => 'sometimes|string',
            // 'company_status',
            // 'verified_at',
            'working_hours_start' => 'sometimes|date_format:H:i',
            'working_hours_end' => 'sometimes|date_format:H:i',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $intrnalPhone = '963' . substr($request['company_phone'], 1);
        $cached_phone = Cache::get('otp_' . $intrnalPhone);
        $cached_email = Cache::get('otp_' . $request['company_email']);
        if (!$cached_phone || !$cached_email) {
            return response()->json([
                'message' => 'OTP expired or not verified'
            ], 400);
        }
        if ($cached_phone['status'] !== 'verified' || $cached_email['status'] !== 'verified') {
            return response()->json([
                'message' => 'OTP not verified',
                'phone' => $cached_phone['status'],
                'email' => $cached_email['status']
            ], 400);
        }

        $uniqueRequest = app(StoreUserRequest::class);
        $uniqueRequest->ignoreId = null;
        $uniqueRequest->ignoreTable = null;
        $uniqueRequest->merge([
            'company_email' => $request->company_email,
            'company_phone' => $request->company_phone,
        ]);
        $uniqueRequest->prepareForValidation();
        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        );
        $data = $uniqueValidator->validate();
        $company = $this->solarCompanyManagerService->Company_register($request, $data);
        return response()->json(['message' => 'solar company register successfully and watting for approve', 'company' => $company['solarCompany'], 'company_logo' => $company['companyLogo']]);
    }

    public function company_manager_profile()
    {
        $profile = $this->solarCompanyManagerService->company_manager_profile();
        if (!$profile) {
            return response()->json(['message' => 'company manager profile not found', 404]);
        }
        return response()->json(['message' => 'company manager profile retrieved successfully',
            'profile' => $profile]);
    }

    public function update_profile(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
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
        $uniqueRequest->ignoreId = Auth::guard('company_manager')->user()->id;
        $uniqueRequest->ignoreTable = 'solar_company_managers';

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
        $profile = $this->solarCompanyManagerService->update_profile($request, $data);
        return response()->json(['message' => 'company manager profile update', 'profile' => $profile[0], 'imageUrl' => $profile[1]]);
    }

    public function Update_company(Request $request, Solar_company $solarCompany)
    {
        $validate = Validator::make($request->all(), [
            'company_name' => 'required|string',
            'company_logo' => 'sometimes|nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'commerical_register_number' => 'sometimes|string',
            'company_description' => 'sometimes|string',
            'company_email' => 'sometimes|email',
            'company_phone' => 'somatimes|regex:/^09\d{8}$/',
            'tax_number' => 'sometimes|string',
            'working_hours_start' => 'sometimes|date_format:H:i',
            'working_hours_end' => 'sometimes|date_format:H:i',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }

        if ($request->filled('company_phone')) {
            $intrnalPhone = '963' . substr($request['company_phone'], 1);
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
        if ($request->filled('company_email')) {
            $cached_email = Cache::get('otp_' . $request['company_email']);
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
        $uniqueRequest->ignoreId = $solarCompany->id;
        $uniqueRequest->ignoreTable = 'solar_companies';

        $uniqueData = [];
        if ($request->has('company_email')) {
            $uniqueData['company_email'] = $request->company_email;
        }
        if ($request->has('company_phone')) {
            $uniqueData['company_phone'] = $request->company_phone;
        }

        $uniqueRequest->merge($uniqueData);

        $uniqueRequest->prepareForValidation();
        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        )->validate();

        $data = array_merge($uniqueValidator, $validate->validated());
        $updated = $this->solarCompanyManagerService->update_company($request, $data, $solarCompany);
        return response()->json(['message' => 'solar company updated successfully', 'solar_company' => $updated[0], 'logo' => $updated[1]]);
    }

    public function Add_company_address(Request $request, Solar_company $solarCompany)
    {
        $validate = Validator::make($request->all(), [
            'governorate_id' => 'nullable|exists:governorates,id',
            'area_id' => 'nullable|exists:areas,id',
            'neighborhood_id' => 'nullable|exists:neighborhoods,id',
            'address_description' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $company_address = $this->solarCompanyManagerService->company_address($request, $solarCompany);
        return response()->json(['message' => 'company address added successfully', 'company_address' => $company_address]);
    }

    public function show_custom_subscriptions()
    {
        $subscriptions = $this->solarCompanyManagerService->show_custom_subscriptions();
        return response()->json(['message' => 'custom subscriptions retrieved successfully', 'subscriptions' => $subscriptions]);
    }

    public function subscribe_in_policy(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'subscribe_policy_id' => 'required|exists:subscribe_polices,id',
            're_subscribed' => 'sometimes|boolean',
            'payment_method' => 'required|in:syriatel_cash,shamcash',
            'gsm' => 'required_if:payment_method,syriatel_cash|regex:/^09\d{8}$/',
            'pin_code' => 'required_if:payment_method,syriatel_cash|string',
            'account_address' => 'required_if:payment_method,shamcash|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->solarCompanyManagerService->subscribe_in_policy($request);

        if ($result == null) {
            return response()->json(['message' => 'invalid subscribe policy or not active'], 400);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'company subscribed in policy successfully',
            'subscription' => $result[0],
            'payment' => $result[1],
        ]);
    }

    public function show_all_agency()
    {
        $agencies = $this->solarCompanyManagerService->show_all_agency();
        return response()->json(['agencies' => $agencies]);
    }

    public function filter_agency(FilterAgencyRequest $request)
    {
        $validated = $request->validated();

        $result = $this->solarCompanyManagerService->filter_agency($validated);

        if (!$result) {
            return response()->json(['message' => 'No agencies found matching the criteria'], 404);
        }

        return response()->json([
            'message' => 'Agencies filtered successfully',
            'agencies' => $result
        ]);
    }

    public function show_agency_products(Request $request, $agency_id)
    {
        $validate = Validator::make(['agency_id' => $agency_id], [
            'agency_id' => 'required|integer|exists:agencies,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $products = $this->solarCompanyManagerService->show_agency_products($agency_id);

        return response()->json([
            'message' => 'Agency products retrieved successfully',
            'products' => $products
        ]);
    }

    public function request_purchase_invoice_agency(Request $request, $agency_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['agency_id' => $agency_id]), [
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:syriatel_cash,shamcash',
            'gsm' => 'required_if:payment_method,syriatel_cash|regex:/^09\d{8}$/',
            'pin_code' => 'required_if:payment_method,syriatel_cash|string',
            'account_address' => 'required_if:payment_method,shamcash|string',
            'with_delivery' => 'sometimes|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->solarCompanyManagerService->request_purchase_invoice_agency($agency_id, $request);

        if (!$result) {
            return response()->json(['message' => 'Failed to create purchase invoice request'], 500);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Purchase invoice request created successfully',
            'purchase_request_order' => $result[0],
            'order_items' => $result[1],
            'transaction' => $result[2],
        ]);
    }

    public function delivery_rules(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'rule_name' => 'sometimes|string',
            'governorate_id' => 'sometimes|exists:governorates,id',
            'area_id' => 'sometimes|exists:areas,id',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'price_per_km' => 'sometimes|numeric|min:0',
            'max_weight_kg' => 'sometimes|integer|min:0',
            'price_per_extra_kg' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|in:USD,SY',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $payload = $validate->validated();
        $rule = $this->solarCompanyManagerService->delivery_rules($payload);

        if (isset($rule['error'])) {
            return response()->json(['message' => $rule['error']], 400);
        }

        return response()->json(['message' => 'Delivery rule created successfully', 'rule' => $rule], 201);
    }

    public function show_delivery_rules()
    {
        $rules = $this->solarCompanyManagerService->show_delivery_rules();

        if ($rules->isEmpty()) {
            return response()->json([
                'message' => 'No delivery rules found',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Delivery rules retrieved successfully',
            'data' => $rules
        ], 200);
    }

    public function update_delivery_rule(Request $request, $rule_id)
    {
        $validate = Validator::make($request->all(), [
            'rule_name' => 'sometimes|string',
            'governorate_id' => 'sometimes|exists:governorates,id',
            'area_id' => 'sometimes|exists:areas,id',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'price_per_km' => 'sometimes|numeric|min:0',
            'max_weight_kg' => 'sometimes|integer|min:0',
            'price_per_extra_kg' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|in:USD,SY',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $data = $validate->validated();

        if (empty($data)) {
            return response()->json(['message' => 'No fields provided for update'], 422);
        }

        $rule = $this->solarCompanyManagerService->update_delivery_rule($rule_id, $data);

        if (!$rule) {
            return response()->json(['message' => 'Delivery rule not found or unauthorized'], 404);
        }

        return response()->json([
            'message' => 'Delivery rule updated successfully',
            'rule' => $rule
        ], 200);
    }

    public function delete_delivery_rule($rule_id)
    {
        $deleted = $this->solarCompanyManagerService->delete_delivery_rule($rule_id);

        if (!$deleted) {
            return response()->json(['message' => 'Delivery rule not found or unauthorized'], 404);
        }

        return response()->json(['message' => 'Delivery rule deleted successfully'], 200);
    }

    public function get_purchase_requests_from_agencies()
    {
        $requests = $this->solarCompanyManagerService->get_purchase_requests_from_agencies();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => 'No purchase requests found from agencies',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Purchase requests retrieved successfully',
            'data' => $requests,
        ], 200);
    }

    public function assign_delivery_task(Request $request, Order_list $orderList)
    {
        $validate = Validator::make($request->all(), [
            'driver_id' => 'required|exists:company_agency_employees,id',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->solarCompanyManagerService->assign_delivery_task($request, $orderList);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'Delivery task assigned successfully',
            'task' => $result,
        ], 201);
    }

    public function show_delivery_task()
    {
        $delivery_tasks = $this->solarCompanyManagerService->show_delivery_task();
        if (isset($delivery_tasks['error'])) {
            return response()->json(['message' => $delivery_tasks['error']], 400);
        }
        return response()->json([
            'message' => 'Delivery tasks retrieved successfully',
            'delivery_tasks' => $delivery_tasks,
        ]);
    }

    public function filter_delivery_tasks(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'is_completed' => 'sometimes|boolean',
            'driver_payment_status' => 'sometimes|string|in:paid,unpaid',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $filters = $validate->validated();
        $deliveryTasks = $this->solarCompanyManagerService->filter_delivery_tasks($filters);

        if (isset($deliveryTasks['error'])) {
            return response()->json(['message' => $deliveryTasks['error']], 400);
        }

        return response()->json([
            'message' => 'Delivery tasks filtered successfully',
            'delivery_tasks' => $deliveryTasks,
        ], 200);
    }

    public function recieve_orderList(Order_list $orderList)
    {
        $result = $this->solarCompanyManagerService->recieve_orderList($orderList);

        if (!$result) {
            return response()->json(['message' => 'Failed to process the order list'], 500);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Order list received and delivery task assigned successfully',
            'orderList' => $result,
        ]);
    }
}
