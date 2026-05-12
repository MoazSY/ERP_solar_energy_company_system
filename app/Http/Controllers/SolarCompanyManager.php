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

    public function show_company_products()
    {
        $products = $this->solarCompanyManagerService->show_company_products();

        if ($products === null) {
            return response()->json(['message' => 'company not found'], 404);
        }

        return response()->json([
            'message' => 'Company products retrieved successfully',
            'products' => $products,
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

    public function assign_delivery_task(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'driver_id' => 'required|exists:company_agency_employees,id',
            'order_list_id' => 'required|exists:order_lists,id',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->solarCompanyManagerService->assign_delivery_task($request);

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

    public function recieve_orderList(Request $request, Order_list $orderList)
    {
        $validate = Validator::make(array_merge($request->all(), ['order_list_id' => $orderList->id]), [
            'inventory_manager_id' => 'required|exists:company_agency_employees,id',
            'notes' => 'sometimes|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $result = $this->solarCompanyManagerService->recieve_orderList($request, $orderList);

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

    public function paid_to_employee(Request $request, $task_id)
    {
        $validate = Validator::make($request->all(), [
            'payment_method' => 'required|in:syriatel_cash,shamcash,cash',
            'gsm' => 'required_if:payment_method,syriatel_cash|regex:/^09\d{8}$/',
            'pin_code' => 'required_if:payment_method,syriatel_cash|string',
            'account_address' => 'required_if:payment_method,shamcash|string',
            'task_type' => 'required|string|in:delivery,project_task',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->solarCompanyManagerService->paid_to_employee($request, $task_id);

        if (!$result) {
            return response()->json(['message' => 'Failed to process payment'], 500);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Payment processed successfully',
            'transaction' => $result,
        ]);
    }

    public function solar_system_offers(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'customer_name' => 'sometimes|string|max:255',
            'offer_name' => 'sometimes|string|max:255',
            'offer_details' => 'sometimes|string',
            'system_type' => 'sometimes|string|in:on_grid,off_grid,hybrid',
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|in:USD,SY',
            'validity_days' => 'sometimes|integer|min:1',
            'average_delivery_cost' => 'sometimes|numeric|min:0',
            'average_installation_cost' => 'sometimes|numeric|min:0',
            'average_metal_installation_cost' => 'sometimes|numeric|min:0',
            'panar_image' => 'sometimes|array',
            'panar_image.*' => 'sometimes|mimes:jpg,jpeg,png,webp|max:2048',
            'video' => 'sometimes|file|mimes:mp4,mkv,avi|max:10240',
            'public_private' => 'sometimes|string|in:public,private',
            'offer_date' => 'sometimes|date',
            'offer_expired_date' => 'sometimes|date|after_or_equal:offer_date',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $data = $validate->validated();
        $result = $this->solarCompanyManagerService->solar_system_offers($request, $data);
        if (!$result) {
            return response()->json(['message' => 'Failed to create offer'], 500);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }
        return response()->json([
            'message' => 'Solar system offer created successfully',
            'offer' => $result[0],
            'panar_image_urls' => $result[1],
            'video_url' => $result[2],
        ], 201);
    }

    public function show_company_offers()
    {
        // رؤية العروض التي انشاها المدير مع كافة تفاصيلها والمنتجات وحتى المشتركين وعدد المشتركين بهذا العرض
        $offers = $this->solarCompanyManagerService->show_company_offers();

        if (isset($offers['error'])) {
            return response()->json(['message' => $offers['error']], 400);
        }

        return response()->json([
            'message' => 'Company offers retrieved successfully',
            'offers' => $offers
        ]);
    }

    public function show_subscribers_in_offer($offer_id)
    {
        // رؤية المشتركين في عرض معين مع تفاصيلهم اي الذين طلبوا هذا العرض
        // ليسهل عليه تعيين المرفقات وتوليد الفاتورة لاحقا
        $validate = Validator::make(['offer_id' => $offer_id], [
            'offer_id' => 'required|integer|exists:offers,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $subscribers = $this->solarCompanyManagerService->show_subscribers_in_offer($offer_id);

        if (isset($subscribers['error'])) {
            return response()->json(['message' => $subscribers['error']], 400);
        }

        return response()->json([
            'message' => 'Offer subscribers retrieved successfully',
            'subscribers' => $subscribers
        ]);
    }

    public function update_company_offer(Request $request, $offer_id)
    {
        // يمكنه تعديل العرض وتفاصيله
        $validate = Validator::make(array_merge($request->all(), ['offer_id' => $offer_id]), [
            'offer_id' => 'required|integer|exists:offers,id',
            'offer_name' => 'sometimes|string|max:255',
            'offer_details' => 'sometimes|string',
            'system_type' => 'sometimes|string|in:on_grid,off_grid,hybrid',
            // 'status_reply' => 'sometimes|string|in:pending,approved,rejected',
            'offer_available' => 'sometimes|boolean',
            'validity_days' => 'sometimes|integer|min:1',
            'average_delivery_cost' => 'sometimes|numeric|min:0',
            'average_installation_cost' => 'sometimes|numeric|min:0',
            'average_metal_installation_cost' => 'sometimes|numeric|min:0',
            'offer_expired_date' => 'sometimes|date|after_or_equal:today',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $request = $validate->validated();
        $result = $this->solarCompanyManagerService->update_company_offer($request, $offer_id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'Company offer updated successfully',
            'offer' => $result
        ]);
    }

    public function delete_company_offer($offer_id)
    {
        //  يمكنه حذف عرض معين
        $validate = Validator::make(['offer_id' => $offer_id], [
            'offer_id' => 'required|integer|exists:offers,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->solarCompanyManagerService->delete_company_offer($offer_id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        if ($result === true) {
            return response()->json([
                'message' => 'Company offer deleted successfully'
            ]);
        }

        return response()->json(['message' => 'Failed to delete offer'], 500);
    }

    public function show_customer_requests()
    {
        $requests = $this->solarCompanyManagerService->show_customer_requests();

        if (isset($requests['error'])) {
            return response()->json(['message' => $requests['error']], 404);
        }

        return response()->json([
            'message' => 'Customer requests retrieved successfully',
            'requests' => $requests,
        ]);
    }

    public function show_public_customer_requests(Request $request)
    {
        $maxKm = $request->input('max_km', 10);
        $requests = $this->solarCompanyManagerService->show_public_customer_requests((float) $maxKm);

        if (isset($requests['error'])) {
            return response()->json(['message' => $requests['error']], 404);
        }

        return response()->json([
            'message' => 'Nearby public customer requests retrieved successfully',
            'requests' => $requests,
        ], 200);
    }

    public function filter_customer_requests()
    {
        // تتم الفلترة بناء على بيانات معينة مثلا حسب طلبات المنظومات او المنتجات المنفردة اي طلبية order عادي او بجدول  request system
        // اختر بيانات مهمة للفلترة مثل التاريخ الخ
        // او تم توليد فاتورة لهذه الطلبية ام لا منجزة او لا
        // او تم تعيين مرفقات للمنظومة اذا كانت  request system   ام لا
    }

    public function show_technical_inspection_request()
    {
        $inspections = $this->solarCompanyManagerService->show_technical_inspection_requests();

        if (isset($inspections['error'])) {
            return response()->json(['message' => $inspections['error']], 404);
        }

        return response()->json([
            'message' => 'Technical inspection requests retrieved successfully',
            'inspections' => $inspections,
        ]);
    }

    public function create_invoice(Request $request, $order_id)
    {
        /*
         * توليد فاتورة لطلبية معينة سواء كانت طلبية منظومة او طلبية منتجات منفردة
         * او حتى صيانة او كشف فني
         * تتم تعيين الطلبية والمنتجات المتوافقة مع طلبات العملاء من قبل مدير الشركة وتعيين المرفقات ان كان حاجة
         * وتوليد الفاتورة
         * ليتم لاحقا الموافقة عليها والدفع من قبل العميل  ليتم لاحقا اسناد المهام والمباشرة بالتنفيذ
         * لكن طلبات الشراء المنفردة تتم توليد فاتورة لها تلقائيا حال الدفع لكن ممكن تعديل الفاتورة من قبل المدير مثلا تاريخ التسليم
         */
    }

    public function show_invoices()
    {
        /*
         * رؤية الفواتير التي تم توليدها  مع كافة التفاصيل والمعلومات المتعلقة بها
         */
    }

    public function filter_invoices(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ الفاتورة او حالة الفاتورة تم الدفع ام لا او تم الموافقة عليها ام لا او حسب العميل او حسب نوع الطلبية منظومة او منتجات منفردة او صيانة او كشف فني
         */
    }

    public function update_invoice(Request $request, $invoice_id)
    {
        /*
         * تعديل الفاتورة من قبل المدير مثلا تغيير تاريخ التسليم او اضافة مرفقات او تعديل المنتجات او حتى تعديل السعر في حال كان هناك خصم معين
         */
    }

    //  public function defining_system_contents(Request $request){}
    public function assign_installation_task(Request $request)
    {
        /*
         * تعيين مهمة تركيب لطلبية منظومة معينة بعد توليد الفاتورة والموافقة عليها من قبل العميل
         * يتم تعيين الفني المناسب حسب نوع المنظومة والاحمال المطلوبة
         * ويمكن تعيين اكثر من فني في حال كانت المنظومة كبيرة او معقدة
         * تشمل مهمات الصيانة والكشف
         * التركيب يشمل تركيب المنظومة ذاتها وهناك تركيب القواعد المعدنية للمنظومة اذا كانت مطلوبة حسب نوع المنظومة والاحمال المطلوبة
         * تتم اسناد كافة التفاصيل مع المهمة مع سعر التركيب بناء على الحساب المسبق للتركيب حسب نوع المنظومة والاحمال المطلوبة
         */
    }

    public function show_installation_tasks()
    {
        /*
         * رؤية مهام التركيب المعينة مع كافة التفاصيل المتعلقة بها من نوع المنظومة والاحمال المطلوبة والفنيين المعينين وتاريخ المهمة وحالتها هل تم الانجاز ام لا
         * وهل تم دفع المستحقات للفنيين ام لا
         */
    }

    public function filter_installation_tasks(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ المهمة او حالة المهمة تم الانجاز ام لا او تم الدفع للفنيين ام لا او حسب نوع المنظومة او حسب الفني المعين للمهمة
         */
    }

    public function installation_rules(Request $request)
    {
        /*
         * انشاء قواعد التركيب التي يتم بناء عليها حساب سعر التركيب لطلبية المنظومة
         * تتم القواعد بناء على نوع المنظومة والاحمال المطلوبة
         */
    }

    public function show_installation_rules()
    {
        /*
         * رؤية قواعد التركيب التي تم انشائها مع كافة التفاصيل المتعلقة بها من نوع المنظومة والاحمال المطلوبة وسعر التركيب بناء على هذه القواعد
         */
    }

    public function update_installation_rule(Request $request, $rule_id) {}
    public function delete_installation_rule($rule_id) {}

    public function metal_installation_rules(Request $request)
    {
        /*
         * انشاء قواعد تركيب القواعد المعدنية للمنظومة التي يتم بناء عليها حساب سعر تركيب القواعد المعدنية لطلبية المنظومة
         * تتم القواعد بناء على نوع المنظومة والاحمال المطلوبة اذا كانت مطلوبة حسب نوع المنظومة والاحمال المطلوبة
         */
    }

    public function show_metal_installation_rules() {}
    public function update_metal_installation_rule(Request $request, $rule_id) {}
    public function delete_metal_installation_rule($rule_id) {}

    public function extract_orderlist_request()
    {
        /*
         * طلب استخراج طلبية التركيب موجه لمدير المستودع من طلبات العملاء بعد توليد الفاتورة وتعيين مهمة التركيب لها من قبل المدير
         * يتم استخراج طلبية التركيب من الطلبات التي تم توليد فاتورة لها وتم تعيين مهمة تركيب لها من قبل المدير
         * تحتوي طلبية التركيب على كافة التفاصيل المتعلقة بالمنظومة المطلوبة والاحمال المطلوبة والفنيين المعينين وتاريخ المهمة وحالتها
         */
    }

    public function show_conflict_agency_invoice()
    {
        /*
         * رؤية التضارب في الفاتورة المستلمة من الوكالة التي حددها مدير المستودع
         */
    }

    public function show_product_nearing_out_of_stock()
    {
        /*
         * رؤية المنتجات التي اوشكت على النفاد مع كمياتها
         */
    }

    public function register_inner_sales(Request $request)
    {
        /*
         * تسجيل المبيعات الداخلية التي تحصل عنده في الشركة
         * اي الطلبية مع توليد الفاتورة
         * بحال كان الزبون يطلب منظومة فانه ينشا له منظومة اي طلبية وفاتورة
         * وتعيين المعلومات اللازمة
         */
    }

    public function show_inner_sales()
    {
        /*
         * رؤية المبيعات الداخلية التي قام مدير الشركة بتسجيلها
         */
    }

    public function filter_inner_sales(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ البيع او حسب نوع الطلبية منظومة او منتجات منفردة
         */
    }

    public function create_warranty(Request $request)
    {
        /*
         * انشاء كفالة مرتبطة بالفاتورة مع تسجيل البيانات اللازمة لكل منتج مثل بطارية الواح انفرتر ان احتاجو وللمنظومة ككل
         */
    }

    public function filter_warranty(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ الكفالة او حالة الكفالة منتهية ام لا او حسب نوع الطلبية منظومة او منتجات منفردة او حسب العميل
         */
    }

    public function add_project_to_company_protofolio(Request $request)
    {
        /*
         * اضافة مشروع الى بورتفوليو الشركة لعرضه في صفحة البورتفوليو في الموقع
         * يتم اضافة المشروع مع كافة التفاصيل المتعلقة به من نوع المنظومة والاحمال المطلوبة والموقع الجغرافي للمشروع وصور المشروع قبل وبعد التنفيذ واي مرفقات اخرى متعلقة بالمشروع
         * واراء العملاء وتقيماتهم
         */
    }

    public function show_company_protofolio()
    {
        /*
         * رؤية بورتفوليو الشركة الذي يحتوي على المشاريع التي تم تنفيذها مع كافة التفاصيل المتعلقة بكل مشروع من نوع المنظومة والاحمال المطلوبة والموقع الجغرافي للمشروع وصور المشروع قبل وبعد التنفيذ واي مرفقات اخرى متعلقة بالمشروع واراء العملاء وتقيماتهم
         */
    }

    public function filter_company_protofolio(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب نوع المنظومة او حسب الموقع الجغرافي للمشروع او حسب تقييم العملاء للمشروع او حسب تاريخ تنفيذ المشروع
         */
    }

    public function update_company_protofolio(Request $request, $project_id)
    {
        /*
         * تعديل مشروع في بورتفوليو الشركة لعرضه في صفحة البورتفوليو في الموقع
         * يتم تعديل المشروع مع كافة التفاصيل المتعلقة به من نوع المنظومة والاحمال المطلوبة والموقع الجغرافي للمشروع وصور المشروع قبل وبعد التنفيذ واي مرفقات اخرى متعلقة
         */
    }

    public function delete_company_protofolio($project_id)
    {
        /*
         * حذف مشروع من بورتفوليو الشركة لعرضه في صفحة البورتفوليو في الموقع
         */
    }

    public function agency_rating(Request $request, $agency_id)
    {
        /*
         * تقيمم وكالة لكن يجب ان يكون قد اتم استلام اي فاتورة منها
         */
    }

    public function show_mantainance_requests()
    {
        /*
         * عرض طلبات الصيانة مع كافة التفاصيل المتعلقة بها والكفالة
         */
    }

    public function check_mantainance_warranty(Request $request)
    {
        /*
         * التحقق من حالة الكفالة لطلب الصيانة هل هي ما زالت سارية ام لا بناء على تاريخ الكفالة وحالة الكفالة منتهية ام لا
         */
    }

    public function show_company_profits(Request $request)
    {
        /*
         * رؤية ارباح الشركة من خلال الفواتير التي تم توليدها سواء كانت لطلبات منظومات او طلبات منتجات منفردة او طلبات صيانة او كشف فني
         * يتم حساب الارباح بناء على الفواتير التي تم توليدها مع خصم التكاليف المتعلقة بالمنظومات او المنتجات او الصيانة او كشف الفني من سعر الفاتورة
         */
    }

    public function filter_company_profits(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ البيع او حسب نوع الطلبية منظومة او منتجات منفردة
         */
    }

    public function show_company_expenses(Request $request)
    {
        /*
         * رؤية مصاريف الشركة المتعلقة بالمنظومات او المنتجات او المدفوعات واجور الموظفين
         */
    }

    public function filter_company_expenses(Request $request)
    {
        /*
         * تتم الفلترة بناء على بيانات معينة مثلا حسب تاريخ المصروف
         */
    }

    public function recieve_cash_from_employee(Request $request)
    {
        /*
         * استلام الاموال من الفني التي استلمها من العملاء في حال دفع العميل للمهمة كاش او ثمن المستهلكات الاضاقية
         */
    }

    public function show_custom_disscount_from_agency()
    {
        /*
         * رؤية الخصومات على المنتجات التي عملتها الوكالة للشركة
         */
    }
}
