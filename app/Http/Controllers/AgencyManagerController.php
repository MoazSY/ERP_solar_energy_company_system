<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterSolarCompanyRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\Agency;
use App\Models\Products;
use App\Models\Solar_company;
use App\Services\AgencyManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AgencyManagerController extends Controller
{
    protected $agencyManagerService;

    public function __construct(AgencyManagerService $agencyManagerService)
    {
        $this->agencyManagerService = $agencyManagerService;
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
        $result = $this->agencyManagerService->register($request, $data);
        return response()->json(['message' => 'agency manager register successfully', 'agency_manager' => $result['agency_manager'], 'imageUrl' => $result['imageUrl'], 'token' => $result['token'], 'refresh_token' => $result['refresh_token'], 'identification_image_URL' => $result['identification_image_URL']]);
    }

    public function Agency_register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'agency_name' => 'required|string',
            'agency_logo' => 'sometimes|nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'commerical_register_number' => 'required|string',
            'agency_description' => 'sometimes|string',
            'agency_email' => 'required|email',
            'agency_phone' => 'required|regex:/^09\d{8}$/',
            'tax_number' => 'sometimes|string',
            // 'agency_status',
            // 'verified_at',
            'working_hours_start' => 'sometimes|date_format:H:i',
            'working_hours_end' => 'sometimes|date_format:H:i',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $intrnalPhone = '963' . substr($request['agency_phone'], 1);
        $cached_phone = Cache::get('otp_' . $intrnalPhone);
        $cached_email = Cache::get('otp_' . $request['agency_email']);
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
            'agency_email' => $request->agency_email,
            'agency_phone' => $request->agency_phone,
        ]);
        $uniqueRequest->prepareForValidation();
        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        );
        $data = $uniqueValidator->validate();
        $agency = $this->agencyManagerService->Agency_register($request, $data);
        return response()->json(['message' => 'agency register successfully and waiting for approve', 'agency' => $agency['agency'], 'agency_logo' => $agency['agencyLogo']]);
    }

    public function agency_manager_profile()
    {
        $profile = $this->agencyManagerService->agency_manager_profile();
        if (!$profile) {
            return response()->json(['message' => 'agency manager profile not found', 404]);
        }
        return response()->json(['message' => 'agency manager profile retrieved successfully',
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
        $uniqueRequest->ignoreId = Auth::guard('agency_manager')->user()->id;
        $uniqueRequest->ignoreTable = 'agency_managers';

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
        $profile = $this->agencyManagerService->update_profile($request, $data);
        return response()->json(['message' => 'agency manager profile update', 'profile' => $profile[0], 'imageUrl' => $profile[1]]);
    }

    public function Update_agency(Request $request, Agency $agency)
    {
        $validate = Validator::make($request->all(), [
            'agency_name' => 'sometimes|string',
            'agency_logo' => 'sometimes|nullable|mimes:jpg,jpeg,png,webp|max:2048',
            'commerical_register_number' => 'sometimes|string',
            'agency_description' => 'sometimes|string',
            'agency_email' => 'sometimes|email',
            'agency_phone' => 'sometimes|regex:/^09\d{8}$/',
            'tax_number' => 'sometimes|string',
            'working_hours_start' => 'sometimes|date_format:H:i',
            'working_hours_end' => 'sometimes|date_format:H:i',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }

        if ($request->filled('agency_phone')) {
            $intrnalPhone = '963' . substr($request['agency_phone'], 1);
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
        if ($request->filled('agency_email')) {
            $cached_email = Cache::get('otp_' . $request['agency_email']);
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
        $uniqueRequest->ignoreId = $agency->id;
        $uniqueRequest->ignoreTable = 'agencies';

        $uniqueData = [];
        if ($request->has('agency_email')) {
            $uniqueData['agency_email'] = $request->agency_email;
        }
        if ($request->has('agency_phone')) {
            $uniqueData['agency_phone'] = $request->agency_phone;
        }

        $uniqueRequest->merge($uniqueData);

        $uniqueRequest->prepareForValidation();
        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        )->validate();

        $data = array_merge($uniqueValidator, $validate->validated());
        $updated = $this->agencyManagerService->update_agency($request, $data, $agency);
        return response()->json(['message' => 'agency updated successfully', 'agency' => $updated[0], 'logo' => $updated[1]]);
    }

    public function Add_agency_address(Request $request, Agency $agency)
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
        $agency_address = $this->agencyManagerService->agency_address($request, $agency);
        return response()->json(['message' => 'agency address added successfully', 'agency_address' => $agency_address]);
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

        $result = $this->agencyManagerService->subscribe_in_policy($request);

        if ($result == null) {
            return response()->json(['message' => 'invalid subscribe policy or not active'], 400);
        }
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'agency subscribed in policy successfully',
            'subscription' => $result[0],
            'payment' => $result[1],
        ]);
    }

    public function add_agency_products(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'product_type' => 'required|string|in:solar_panel,inverter,battery,accessory',
            'product_brand' => 'sometimes|string',
            'model_number' => 'sometimes|string',
            'quentity' => 'sometimes|integer|min:0',
            'price' => 'required|numeric|min:0',
            'disscount_type' => 'sometimes|string|in:percentage,amount',
            'disscount_value' => 'sometimes|numeric|min:0',
            'currency' => 'required|string|in:USD,SY',
            'manufacture_date' => 'sometimes|date',
            'product_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $data = $validate->validated();
        $result = $this->agencyManagerService->add_agency_products($request, $data);
        if (!$result) {
            return response()->json(['message' => 'invalid entity type'], 400);
        }
        return response()->json(['message' => 'product added successfully', 'product' => $result[0], 'product_image' => $result[1]]);
    }

    public function add_agency_product_battery(Request $request, Products $product_id)
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
        $result = $this->agencyManagerService->add_agency_product_battery($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this agency'], 400);
        }
        return response()->json(['message' => 'battery details added successfully', 'battery_details' => $result]);
    }

    public function add_agency_product_inverter(Request $request, Products $product_id)
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
        $result = $this->agencyManagerService->add_agency_product_inverter($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this agency'], 400);
        }
        return response()->json(['message' => 'inverter details added successfully', 'inverter_details' => $result]);
    }

    public function add_agency_product_solar_panel(Request $request, Products $product_id)
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
        $result = $this->agencyManagerService->add_agency_product_solar_panel($data, $product_id);
        if (!$result) {
            return response()->json(['message' => 'invalid product or not owned by this agency'], 400);
        }
        return response()->json(['message' => 'solar panel details added successfully', 'solar_panel_details' => $result]);
    }

    public function show_agency_products()
    {
        $products = $this->agencyManagerService->show_agency_products();
        return response()->json(['message' => 'Agency products retrieved successfully', 'products' => $products]);
    }

    public function update_agency_product(Request $request, $product_id)
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

        $result = $this->agencyManagerService->update_agency_product($request, $data, $product_id);

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

    public function delete_agency_product($product_id)
    {
        $validate = Validator::make(['product_id' => $product_id], [
            'product_id' => 'required|integer|exists:products,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->agencyManagerService->delete_agency_product($product_id);

        if (!$result) {
            return response()->json(['message' => 'Product not found or not owned by this agency'], 404);
        }

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function delete_agency_product_details($product_id)
    {
        $validate = Validator::make(['product_id' => $product_id], [
            'product_id' => 'required|integer|exists:products,id'
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 400);
        }

        $result = $this->agencyManagerService->delete_agency_product_details($product_id);

        if (!$result) {
            return response()->json(['message' => 'Product not found or not owned by this agency'], 404);
        }

        return response()->json(['message' => 'Product detail records deleted successfully']);
    }

    public function filter_agency_products(Request $request)
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
        $products = $this->agencyManagerService->filter_agency_products($filters);

        if (empty($products)) {
            return response()->json(['message' => 'No products found matching the filters', 'data' => []], 200);
        }
        return response()->json(['message' => 'Products retrieved successfully', 'data' => $products], 200);
    }

    public function filter_solar_companies(FilterSolarCompanyRequest $request)
    {
        $filters = $request->validated();
        $companies = $this->agencyManagerService->filter_solar_companies($filters);

        if ($companies->isEmpty()) {
            return response()->json(['message' => 'No companies found matching the filters', 'data' => []], 200);
        }

        return response()->json(['message' => 'Companies retrieved successfully', 'data' => $companies], 200);
    }

    public function create_custom_discount(Request $request, Solar_company $solar_company_id)
    {
        $validate = Validator::make($request->all(), [
            'product_id' => 'nullable|integer|exists:products,id',
            'discount_amount' => 'required|numeric|min:0',
            'disscount_type' => 'required|string|in:percentage,fixed',
            'currency' => 'nullable|string|in:USD,SY',
            'disscount_active' => 'nullable|boolean',
            'quentity_condition' => 'nullable|integer|min:0',
            'public' => 'nullable|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $data = $validate->validated();
        $discount = $this->agencyManagerService->create_custom_discount($data, $solar_company_id);

        if (!$discount) {
            return response()->json(['message' => 'Failed to create custom discount'], 400);
        }

        return response()->json(['message' => 'Custom discount created successfully', 'data' => $discount], 201);
    }

    public function show_custom_discounts($solar_company_id)
    {
        $discounts = $this->agencyManagerService->show_custom_discounts($solar_company_id);

        return response()->json(['message' => 'Custom discounts retrieved successfully', 'data' => $discounts], 200);
    }

    public function update_custom_discount(Request $request, $discount_id)
    {
        $validate = Validator::make($request->all(), [
            'discount_amount' => 'sometimes|numeric|min:0',
            'disscount_type' => 'sometimes|string|in:percentage,fixed',
            'currency' => 'sometimes|string|in:USD,SY',
            'disscount_active' => 'sometimes|boolean',
            'quentity_condition' => 'sometimes|integer|min:0',
            'public' => 'sometimes|nullable|boolean',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        try {
            $data = $validate->validated();
            $discount = $this->agencyManagerService->update_custom_discount($discount_id, $data);

            return response()->json(['message' => 'Custom discount updated successfully', 'data' => $discount], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Discount not found or unauthorized'], 404);
        }
    }

    public function delete_custom_discount($discount_id)
    {
        try {
            $result = $this->agencyManagerService->delete_custom_discount($discount_id);

            if (!$result) {
                return response()->json(['message' => 'Failed to delete custom discount'], 400);
            }

            return response()->json(['message' => 'Custom discount deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Discount not found or unauthorized'], 404);
        }
    }

    public function get_all_custom_discounts_grouped_by_company()
    {
        $groupedDiscounts = $this->agencyManagerService->get_all_custom_discounts_grouped_by_company();

        return response()->json(['message' => 'All custom discounts grouped by company retrieved successfully', 'custom_discounts' => $groupedDiscounts], 200);
    }

    public function get_purchase_requests_from_companies()
    {
        $requests = $this->agencyManagerService->get_purchase_requests_from_companies();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => 'No purchase requests found from companies',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Purchase requests retrieved successfully',
            'data' => $requests,
        ], 200);
    }

    public function create_purchase_invoice(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'order_list_id' => 'required|exists:order_lists,id',
            'due_date' => 'required|date|after:today',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->agencyManagerService->create_purchase_invoice($request);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'purchase invoice created successfully',
            'invoice' => $result,
        ], 201);
    }
    public function deliviry_rules(Request $request){
        $validate=Validator::make($request->all(),[
            'rule_name'=>'sometimes|string',
            'governorate_id'=>'sometimes|exists:governorates,id',
            'area_id'=>'sometimes|exists:areas,id',
            'delivery_fee'=>'sometimes|numeric|min:0',
            'price_per_km'=>'sometimes|numeric|min:0',
            'max_weight_kg'=>'sometimes|integer|min:0',
            'price_per_extra_kg'=>'sometimes|numeric|min:0',
            'currency'=>'sometimes|string|in:USD,SY',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        $request=$validate->validated();
        $rule=$this->agencyManagerService->delivery_rules($request);
        return response()->json(['message' => 'Delivery rule created successfully', 'rule' => $rule], 201);
    }
    public function assign_delivery_task(Request $request){
        $validate=Validator::make($request->all(),[
            'order_list_id'=>'required|exists:order_lists,id',
            'driver_id'=>'required|exists:company_agency_employees,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }
        // $request=$validate->validated();
        $task=$this->agencyManagerService->assign_delivery_task($request);
        return response()->json(['message' => 'Delivery task assigned successfully', 'task' => $task], 201);

    }

}
