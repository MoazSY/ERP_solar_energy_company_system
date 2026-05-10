<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
// use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
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
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'about_him' => 'sometimes|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $internalPhone = '963' . substr($request['phoneNumber'], 1);
        $cachedPhone = Cache::get('otp_' . $internalPhone);
        $cachedEmail = Cache::get('otp_' . $request['email']);

        if (!$cachedPhone || !$cachedEmail) {
            return response()->json(['message' => 'OTP expired or not verified'], 400);
        }

        if (($cachedPhone['status'] ?? null) !== 'verified' || ($cachedEmail['status'] ?? null) !== 'verified') {
            return response()->json([
                'message' => 'OTP not verified',
                'phone' => $cachedPhone['status'] ?? null,
                'email' => $cachedEmail['status'] ?? null,
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

        $uniqueValidator = Validator::make($uniqueRequest->all(), $uniqueRequest->rules());
        $data = $uniqueValidator->validate();

        $result = $this->customerService->register($request, $data);

        cache()->forget('otp_' . $internalPhone);
        cache()->forget('otp_' . $request['email']);

        return response()->json([
            'message' => 'customer register successfully',
            'customer' => $result['customer'],
            'imageUrl' => $result['imageUrl'],
            'token' => $result['token'],
            'refresh_token' => $result['refresh_token'],
        ]);
    }

    public function customer_profile()
    {
        $profile = $this->customerService->customer_profile();
        if (!$profile) {
            return response()->json(['message' => 'customer profile not found'], 404);
        }

        return response()->json(['message' => 'customer profile retrieved successfully', 'profile' => $profile]);
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
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        if ($request->filled('phoneNumber')) {
            $internalPhone = '963' . substr($request['phoneNumber'], 1);
            $cachedPhone = Cache::get('otp_' . $internalPhone);
            if (!$cachedPhone || ($cachedPhone['status'] ?? null) !== 'verified') {
                return response()->json(['message' => 'OTP expired or not verified', 'phone' => $cachedPhone['status'] ?? null], 400);
            }
        }

        if ($request->filled('email')) {
            $cachedEmail = Cache::get('otp_' . $request['email']);
            if (!$cachedEmail || ($cachedEmail['status'] ?? null) !== 'verified') {
                return response()->json(['message' => 'OTP expired or not verified', 'email' => $cachedEmail['status'] ?? null], 400);
            }
        }

        $uniqueRequest = app(StoreUserRequest::class);
        $uniqueRequest->ignoreId = Auth::guard('customer')->user()->id;
        $uniqueRequest->ignoreTable = 'customers';

        $uniqueData = [];
        if ($request->has('email')) {
            $uniqueData['email'] = $request->email;
        }
        if ($request->has('phoneNumber')) {
            $uniqueData['phoneNumber'] = $request->phoneNumber;
        }

        $uniqueRequest->merge($uniqueData);
        $uniqueRequest->prepareForValidation();

        $uniqueValidator = Validator::make($uniqueRequest->all(), $uniqueRequest->rules())->validate();
        $data = array_merge($uniqueValidator, $validate->validated());

        $profile = $this->customerService->update_profile($request, $data);

        return response()->json(['message' => 'customer profile update', 'profile' => $profile[0], 'imageUrl' => $profile[1]]);
    }

    public function show_company_offers($company_id)
    {
        $validate = Validator::make(['company_id' => $company_id], [
            'company_id' => 'required|exists:solar_companies,id'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $offers = $this->customerService->show_company_offers($company_id);
        return response()->json(['message' => 'company offers retrieved successfully', 'offers' => $offers]);
    }

    public function electrical_devices()
    {
        $devices = $this->customerService->get_all_electrical_devices();

        return response()->json(['message' => 'electrical devices retrieved successfully', 'devices' => $devices]);
    }

    public function show_my_specific_offers()
    {
        $result = $this->customerService->show_my_specific_offers();
        return response()->json(['message' => 'customer specific offers retrieved successfully', 'offers' => $result]);
    }

    public function subscribe_offer(Request $request, $offer_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['offer_id' => $offer_id]), [
            'offer_id' => 'required|exists:offers,id',
            'with_installation' => 'sometimes|boolean',
            'additional_cost_amount' => 'sometimes|numeric|min:0',
            'additional_entitlement_amount' => 'sometimes|numeric|min:0',
            'system_sn' => 'sometimes|nullable|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->subscribe_offer($request, $offer_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'offer subscribed successfully', 'subscription' => $result], 201);
    }

    public function unsubscribe_offer(Request $request, $offer_id)
    {
        $validate = Validator::make(['offer_id' => $offer_id], [
            'offer_id' => 'required|exists:offers,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->unsubscribe_offer($request, $offer_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'offer subscription cancelled successfully', 'subscription' => $result]);
    }

    public function show_subscribe_offers()
    {
        $result = $this->customerService->show_subscribe_offers();
        return response()->json(['message' => 'subscribed offers retrieved successfully', 'subscriptions' => $result]);
    }

    public function request_solar_system(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'company_id' => 'sometimes|nullable|exists:solar_companies,id',
            'requested_capacity_kw' => 'sometimes|numeric|min:0',
            'dayly_consumption_kwh' => 'sometimes|numeric|min:0',
            'nightly_consumption_kwh' => 'sometimes|numeric|min:0',
            'system_type' => 'sometimes|string|in:on_grid,off_grid,hybrid',
            // 'invertar_type' => 'sometimes|string',
            'inverter_brand' => 'sometimes|string',
            'battery_type' => 'sometimes|string|in:lithium_ion,lead_acid,nickel_cadmium',
            'battery_brand' => 'sometimes|string',
            // 'solar_panel_type' => 'sometimes|string',
            'solar_panel_brand' => 'sometimes|string',
            'inverter_capacity_kw' => 'sometimes|numeric|min:0',
            'solar_panel_capacity_kw' => 'sometimes|numeric|min:0',
            'solar_panel_number' => 'sometimes|integer|min:0',
            'battery_capacity_kwh' => 'sometimes|numeric|min:0',
            'battery_number' => 'sometimes|integer|min:0',
            'inverter_voltage_v' => 'sometimes|string|in:12V,24V,48V',
            'battery_voltage_v' => 'sometimes|string|in:12V,24V,48V',
            'expected_budget' => 'sometimes|string|in:low,medium,high',
            'metal_base_type' => 'sometimes|string|in:installation,blacksmith_workshop',
            'front_base_height_m' => 'sometimes|numeric|min:0',
            'back_base_height_m' => 'sometimes|numeric|min:0',
            'additional_details' => 'sometimes|nullable|string',
            'surface_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->request_solar_system($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'solar system request created successfully', 'request' => $result], 201);
    }

    public function add_electrical_devices_to_request_solar_system(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'request_id' => 'sometimes|nullable|exists:request_solar_systems,id',
            'electrical_devices' => 'required|array|min:1',
            'electrical_devices.*.electrical_device_id' => 'required|exists:electrical_devices,id',
            'electrical_devices.*.capacity' => 'required|numeric|min:0',
            'electrical_devices.*.unit' => 'sometimes|nullable|string',
            'electrical_devices.*.usage_time' => 'sometimes|in:dayly,nightly',
            'electrical_devices.*.notes' => 'sometimes|nullable|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->add_electrical_devices_to_request_solar_system($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'electrical devices attached successfully',
            'requests' => $result,
            // 'power_summary' => $result['power_summary'] ?? null,
        ], 201);
    }

    public function request_technical_inspection(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'company_id' => 'required|exists:solar_companies,id',
            'issue_description' => 'sometimes|nullable|string',
            'customer_address'=> 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->request_technical_inspection($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'technical inspection request created successfully', 'inspection' => $result], 201);
    }

    public function show_my_requests()
    {
        $result = $this->customerService->show_my_requests();
        return response()->json(['message' => 'customer requests retrieved successfully', 'requests' => $result]);
    }
////////////////////////////////////////////////////////////////
    public function show_my_solar_systems()
    {
        $result = $this->customerService->show_my_solar_systems();
        return response()->json(['message' => 'customer solar systems retrieved successfully', 'solar_systems' => $result]);
    }
///////////////////////////////////////////////////////////////////
    public function filter_requests(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'request_kind' => 'sometimes|in:all,solar,maintenance,orders',
            'company_id' => 'sometimes|exists:solar_companies,id',
            'system_type' => 'sometimes|string',
            'metainence_type' => 'sometimes|string',
            'issue_category' => 'sometimes|string',
            'metainence_status' => 'sometimes|in:pending,cancelled,completed',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->filter_requests($request);
        return response()->json(['message' => 'requests filtered successfully', 'requests' => $result]);
    }

    public function cancel_solar_system_request(Request $request, $request_id)
    {
        $validate = Validator::make(['request_id' => $request_id], [
            'request_id' => 'required|exists:request_solar_systems,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->cancel_solar_system_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'solar system request cancelled successfully', 'request' => $result]);
    }

    public function update_solar_system_request(Request $request, $request_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['request_id' => $request_id]), [
            'request_id' => 'required|exists:request_solar_systems,id',
            'company_id' => 'sometimes|exists:solar_companies,id',
            'requested_capacity_kw' => 'sometimes|numeric|min:0',
            'dayly_consumption_kwh' => 'sometimes|numeric|min:0',
            'nightly_consumption_kwh' => 'sometimes|numeric|min:0',
            'system_type' => 'sometimes|string',
            'invertar_type' => 'sometimes|string',
            'inverter_brand' => 'sometimes|string',
            'battery_type' => 'sometimes|string',
            'battery_brand' => 'sometimes|string',
            'solar_panel_type' => 'sometimes|string',
            'solar_panel_brand' => 'sometimes|string',
            'inverter_capacity_kw' => 'sometimes|numeric|min:0',
            'solar_panel_capacity_kw' => 'sometimes|numeric|min:0',
            'solar_panel_number' => 'sometimes|integer|min:0',
            'battery_capacity_kwh' => 'sometimes|numeric|min:0',
            'battery_number' => 'sometimes|integer|min:0',
            'inverter_voltage_v' => 'sometimes|numeric|min:0',
            'battery_voltage_v' => 'sometimes|numeric|min:0',
            'expected_budget' => 'sometimes|numeric|min:0',
            'metal_base_type' => 'sometimes|string',
            'front_base_height_m' => 'sometimes|numeric|min:0',
            'back_base_height_m' => 'sometimes|numeric|min:0',
            'surface_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->update_solar_system_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'solar system request updated successfully', 'request' => $result]);
    }

    public function show_invoices_details()
    {
        $result = $this->customerService->show_invoices_details();
        return response()->json(['message' => 'customer invoices retrieved successfully', 'invoices' => $result]);
    }

    public function approve_pay_invoice(Request $request, $invoice_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['invoice_id' => $invoice_id]), [
            'invoice_id' => 'required|exists:purchase_invoices,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'payment_reference' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->approve_pay_invoice($request, $invoice_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'invoice approval recorded successfully', 'result' => $result]);
    }

    public function recieve_invoice(Request $request, $invoice_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['invoice_id' => $invoice_id]), [
            'invoice_id' => 'required|exists:purchase_invoices,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->recieve_invoice($request, $invoice_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'invoice received successfully', 'invoice' => $result]);
    }

    public function show_installations_services_status()
    {
        $result = $this->customerService->show_installations_services_status();
        return response()->json(['message' => 'installations and services status retrieved successfully', 'data' => $result]);
    }

    public function pay_for_additional_consumables(Request $request, $installation_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['installation_id' => $installation_id]), [
            'installation_id' => 'required|exists:project_tasks,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'payment_reference' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->pay_for_additional_consumables($request, $installation_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'additional consumables payment recorded successfully', 'result' => $result], 201);
    }

    public function technical_employee_rating(Request $request, $installation_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['installation_id' => $installation_id]), [
            'installation_id' => 'required|exists:project_tasks,id',
            'rate' => 'required|numeric|min:1|max:5',
            'feedback' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->technical_employee_rating($request, $installation_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'technical employee rating recorded successfully', 'rating' => $result], 201);
    }

    public function task_feedsback(Request $request, $task_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['task_id' => $task_id]), [
            'task_id' => 'required|exists:project_tasks,id',
            'rate' => 'required|numeric|min:1|max:5',
            'feedback' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->task_feedsback($request, $task_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'task feedback recorded successfully', 'feedback' => $result], 201);
    }

    public function company_feedsback(Request $request, $company_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['company_id' => $company_id]), [
            'company_id' => 'required|exists:solar_companies,id',
            'feedback' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->company_feedsback($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'company feedback recorded successfully', 'report' => $result], 201);
    }

    public function company_rating(Request $request, $company_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['company_id' => $company_id]), [
            'company_id' => 'required|exists:solar_companies,id',
            'rate' => 'required|numeric|min:1|max:5',
            'feedback' => 'sometimes|nullable|string',
            'comment' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->company_rating($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'company rating recorded successfully', 'report' => $result], 201);
    }

    public function show_company_gallary($company_id)
    {
        $result = $this->customerService->show_company_gallary($company_id);
        return response()->json(['message' => 'company gallery retrieved successfully', 'gallery' => $result]);
    }

    public function request_products_order(Request $request, $company_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['company_id' => $company_id]), [
            'company_id' => 'required|exists:solar_companies,id',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'sometimes|numeric|min:0',
            'products.*.discount_type' => 'sometimes|nullable|string',
            'products.*.discount_value' => 'sometimes|numeric|min:0',
            'products.*.discount_amount' => 'sometimes|numeric|min:0',
            'with_delivery' => 'sometimes|boolean',
            'request_note' => 'sometimes|nullable|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->request_products_order($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'product order created successfully', 'order' => $result], 201);
    }

    public function show_requested_products_orders()
    {
        $result = $this->customerService->show_requested_products_orders();
        return response()->json(['message' => 'requested products orders retrieved successfully', 'orders' => $result]);
    }

    public function request_maintenance_service(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'company_id' => 'required|exists:solar_companies,id',
            'metainence_type' => 'sometimes|string',
            'issue_category' => 'sometimes|string',
            'priority' => 'sometimes|string',
            'issue_description' => 'sometimes|nullable|string',
            'system_sn' => 'sometimes|nullable|string',
            'warranty_number' => 'sometimes|nullable|string',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'problem_name' => 'sometimes|nullable|string',
            'problem_cause' => 'sometimes|nullable|string',
            'payment_method' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'image_state' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->request_maintenance_service($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance request created successfully', 'request' => $result], 201);
    }

    public function show_my_maintenance_requests()
    {
        $result = $this->customerService->show_my_maintenance_requests();
        return response()->json(['message' => 'maintenance requests retrieved successfully', 'requests' => $result]);
    }

    public function cancel_maintenance_request(Request $request, $request_id)
    {
        $validate = Validator::make(['request_id' => $request_id], [
            'request_id' => 'required|exists:metainence_requests,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->cancel_maintenance_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance request cancelled successfully', 'request' => $result]);
    }

    public function update_maintenance_request(Request $request, $request_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['request_id' => $request_id]), [
            'request_id' => 'required|exists:metainence_requests,id',
            'company_id' => 'sometimes|exists:solar_companies,id',
            'metainence_type' => 'sometimes|string',
            'issue_category' => 'sometimes|string',
            'priority' => 'sometimes|string',
            'issue_description' => 'sometimes|nullable|string',
            'system_sn' => 'sometimes|nullable|string',
            'warranty_number' => 'sometimes|nullable|string',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'problem_name' => 'sometimes|nullable|string',
            'problem_cause' => 'sometimes|nullable|string',
            'payment_method' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'image_state' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->update_maintenance_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance request updated successfully', 'request' => $result]);
    }

    public function recieve_maintenance_service(Request $request, $request_id)
    {
        $validate = Validator::make(['request_id' => $request_id], [
            'request_id' => 'required|exists:metainence_requests,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->recieve_maintenance_service($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance service received successfully', 'request' => $result]);
    }

    public function simulation_solar_system_finacial_savings(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'system_cost' => 'required|numeric|min:0',
            'current_monthly_cost' => 'required|numeric|min:0',
            'monthly_generation_kwh' => 'sometimes|numeric|min:0',
            'value_per_kwh' => 'sometimes|numeric|min:0',
            'monthly_savings' => 'sometimes|numeric|min:0',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->simulation_solar_system_finacial_savings($request);
        return response()->json(['message' => 'financial savings simulation calculated successfully', 'result' => $result]);
    }

    public function company_report(Request $request, $company_id)
    {
        $validate = Validator::make(array_merge($request->all(), ['company_id' => $company_id]), [
            'company_id' => 'required|exists:solar_companies,id',
            'report_content' => 'required_without:message|string',
            'message' => 'required_without:report_content|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->customerService->company_report($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'company report submitted successfully', 'report' => $result], 201);
    }
}
