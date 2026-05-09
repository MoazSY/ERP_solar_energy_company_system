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
        $result = $this->customerService->request_solar_system($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'solar system request created successfully', 'request' => $result], 201);
    }

    public function request_technical_inspection(Request $request)
    {
        $result = $this->customerService->request_technical_inspection($request);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'technical inspection request created successfully', 'request' => $result], 201);
    }

    public function show_my_requests()
    {
        $result = $this->customerService->show_my_requests();
        return response()->json(['message' => 'customer requests retrieved successfully', 'requests' => $result]);
    }

    public function show_my_solar_systems()
    {
        $result = $this->customerService->show_my_solar_systems();
        return response()->json(['message' => 'customer solar systems retrieved successfully', 'solar_systems' => $result]);
    }

    public function filter_requests(Request $request)
    {
        $result = $this->customerService->filter_requests($request);
        return response()->json(['message' => 'requests filtered successfully', 'requests' => $result]);
    }

    public function cancel_solar_system_request(Request $request, $request_id)
    {
        $result = $this->customerService->cancel_solar_system_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'solar system request cancelled successfully', 'request' => $result]);
    }

    public function update_solar_system_request(Request $request, $request_id)
    {
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
        $result = $this->customerService->approve_pay_invoice($request, $invoice_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'invoice approval recorded successfully', 'result' => $result]);
    }

    public function recieve_invoice(Request $request, $invoice_id)
    {
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
        $result = $this->customerService->pay_for_additional_consumables($request, $installation_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'additional consumables payment recorded successfully', 'result' => $result], 201);
    }

    public function technical_employee_rating(Request $request, $installation_id)
    {
        $result = $this->customerService->technical_employee_rating($request, $installation_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'technical employee rating recorded successfully', 'rating' => $result], 201);
    }

    public function task_feedsback(Request $request, $task_id)
    {
        $result = $this->customerService->task_feedsback($request, $task_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'task feedback recorded successfully', 'feedback' => $result], 201);
    }

    public function company_feedsback(Request $request, $company_id)
    {
        $result = $this->customerService->company_feedsback($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'company feedback recorded successfully', 'report' => $result], 201);
    }

    public function company_rating(Request $request, $company_id)
    {
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
        $result = $this->customerService->cancel_maintenance_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance request cancelled successfully', 'request' => $result]);
    }

    public function update_maintenance_request(Request $request, $request_id)
    {
        $result = $this->customerService->update_maintenance_request($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance request updated successfully', 'request' => $result]);
    }

    public function recieve_maintenance_service(Request $request, $request_id)
    {
        $result = $this->customerService->recieve_maintenance_service($request, $request_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'maintenance service received successfully', 'request' => $result]);
    }

    public function simulation_solar_system_finacial_savings(Request $request)
    {
        $result = $this->customerService->simulation_solar_system_finacial_savings($request);
        return response()->json(['message' => 'financial savings simulation calculated successfully', 'result' => $result]);
    }

    public function company_report(Request $request, $company_id)
    {
        $result = $this->customerService->company_report($request, $company_id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json(['message' => 'company report submitted successfully', 'report' => $result], 201);
    }
}
