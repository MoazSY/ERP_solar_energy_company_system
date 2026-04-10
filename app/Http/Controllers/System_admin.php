<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Areas;
use App\Models\Governorates;
use App\Services\SystemAdminService;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use App\Models\Subscribe_polices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class System_admin extends Controller
{
    protected $SystemAdminService;

    public function __construct(SystemAdminService $SystemAdminService)
    {
        $this->SystemAdminService = $SystemAdminService;
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
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
        ]);

        $uniqueRequest->prepareForValidation();

        $uniqueValidator = Validator::make(
            $uniqueRequest->all(),
            $uniqueRequest->rules()
        );
        $data = $uniqueValidator->validate();
        $result = $this->SystemAdminService->register($request, $data);
        cache()->forget('otp_' . $intrnalPhone);
        cache()->forget('otp_' . $request['email']);
        return response()->json(['message' => 'admin register successfully', 'admin' => $result['admin'], 'imageUrl' => $result['imageUrl'], 'token' => $result['token'], 'refresh_token' => $result['refresh_token']]);
    }

    public function Admin_profile()
    {
        $profile = $this->SystemAdminService->Admin_profile();
        if (!$profile) {
            return response()->json(['message' => 'admin profile not found', 404]);
        }
        return response()->json(['message' => 'admin profile retrieved successfully',
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
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'about_him' => 'sometimes|string',
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
        $uniqueRequest->ignoreId = Auth::guard('admin')->user()->id;
        if ($request->has('email')) {
            $uniqueRequest->merge([
                'email' => $request->email
            ]);
        }
        $uniqueRequest->ignoreTable = 'system_admins';
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
        $profile = $this->SystemAdminService->update_profile($request, $data);
        return response()->json(['message' => 'admin profile update', 'profile' => $profile[0], 'imageUrl' => $profile[1]]);
    }

    public function Add_governorates(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $result = $this->SystemAdminService->add_governorates($request);
        return response()->json(['message' => 'governorate add successfully', 'governorate' => $result]);
    }

    public function Add_area(Request $request, Governorates $governorates)
    {
        $validate = Validator::make($request->all(), [
            'areas' => 'required|array',
            'areas.*.name' => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        try {
            $area = $this->SystemAdminService->add_area($request, $governorates);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
        return response()->json(['message' => 'areas added to governorate successfully', 'areas' => $area]);
    }

    public function add_neighborhoods(Request $request, Areas $area)
    {
        $validate = Validator::make($request->all(), [
            'neighborhoods' => 'required|array',
            'neighborhoods.*.name' => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        try {
            $neighborhoods = $this->SystemAdminService->add_neighborhoods($request, $area);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
        return response()->json(['message' => 'neighborhoods added to area successfully', 'neighborhoods' => $neighborhoods]);
    }

    public function get_governorates()
    {
        $governorates = $this->SystemAdminService->get_governorates();
        return response()->json(['message' => 'all governorates', 'governorates' => $governorates]);
    }

    public function get_areas(Governorates $governorates)
    {
        $areas = $this->SystemAdminService->get_areas($governorates);
        return response()->json(['message' => 'all areas', 'areas' => $governorates]);
    }

    public function get_neighborhoods(Areas $area)
    {
        $neighborhoods = $this->SystemAdminService->get_neighborhoods($area);
        return response()->json(['message' => 'all neighborhoods', 'neighborhoods' => $neighborhoods]);
    }

    public function get_UnActive_company()
    {
        $company = $this->SystemAdminService->UnActive_company();
        return response()->json(['message' => 'all un active company', 'company' => $company]);
    }

    public function get_UnActive_agency()
    {
        $agency = $this->SystemAdminService->UnActive_agency();
        return response()->json(['message' => 'all un active agency', 'agency' => $agency]);
    }

    public function proccess_company_register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'entity_type' => 'required|in:solar_company,agency',
            'entity_id' => 'required|integer',
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'sometimes|string',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $proccess = $this->SystemAdminService->proccess_company_register($request);
        return response()->json(['message' => 'the result of register proccess', 'result' => $proccess]);
    }

    public function subscriptions_policy(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'sometimes|string',
            'apply_to' => 'required|in:company,agency',
            'subscription_fee' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,SY',
            'duration_value' => 'required|integer|min:0',
            'duration_type' => 'required|in:day,month,year',
            'is_active' => 'sometimes|boolean',
            'is_trial_granted' => 'sometimes|boolean',
            // 'trial_duration_value' => 'sometimes|integer|min:0|required_if:is_trial_granted,true',
            // 'trial_duration_type' => 'sometimes|in:day,month,year|required_if:is_trial_granted,true',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $policy = $this->SystemAdminService->subscriptions_policy($request);
        return response()->json(['message' => 'subscription policy created successfully', 'policy' => $policy]);
    }

    public function update_subscriptions_policy(Request $request, Subscribe_polices $subscribe_polices)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'apply_to' => 'sometimes|in:company,agency',
            'subscription_fee' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|in:USD,SY',
            'duration_value' => 'sometimes|integer|min:0',
            'duration_type' => 'sometimes|in:day,month,year',
            'is_active' => 'sometimes|boolean',
            'is_trial_granted' => 'sometimes|boolean',
            // 'trial_duration_value' => 'sometimes|integer|min:0|required_if:is_trial_granted,true',
            // 'trial_duration_type' => 'sometimes|in:day,month,year|required_if:is_trial_granted,true',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $data=$validate->validated();
        $policy = $this->SystemAdminService->update_subscriptions_policy($data, $subscribe_polices);
        return response()->json(['message' => 'subscription policy updated successfully', 'policy' => $policy]);
    }

    public function show_all_company_registerd()
    {
        $registerd_companies = $this->SystemAdminService->show_all_company_registerd();
        return response()->json(['message' => 'all registerd companies', 'registerd_companies' => $registerd_companies]);
    }
    public function show_all_agency_registerd()
    {
        $registerd_agencies = $this->SystemAdminService->show_all_agency_registerd();
        return response()->json(['message' => 'all registerd agencies', 'registerd_agencies' => $registerd_agencies]);
    }

    public function custom_subscribe_policy(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'entity_type' => 'required|in:solar_company,agency',
            'entity_id' => 'required|integer',
            'subscribe_policy_id' => 'required|integer|exists:subscribe_polices,id',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $custom_policy = $this->SystemAdminService->custom_subscribe_policy($request);
        return response()->json(['message' => 'custom subscribe policy created successfully', 'custom_policy' => $custom_policy['custom_subscribe'], 'entity' => $custom_policy['entity']]);
    }

    public function show_subscribtions_policies()
    {
        $policies = $this->SystemAdminService->show_subscribtions_policies();
        return response()->json(['message' => 'all subscription policies for this admin', 'policies' => $policies]);
    }

    public function show_custom_subscribtions_policies()
    {
        $custom_policies = $this->SystemAdminService->show_custom_subscribtions_policies();
        return response()->json(['message' => 'all custom subscription policies for this admin', 'custom_policies' => $custom_policies]);
    }

    public function show_subscribers_of_policy(Subscribe_polices $policy)
    {
        $subscribers = $this->SystemAdminService->show_subscribers_of_policy($policy);
        return response()->json(['message' => 'all subscribers of this policy', 'subscribers' => $subscribers]);
    }

    public function show_subscribtions_policies_for_entity(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'entity_type' => 'required|in:solar_company,agency',
            'entity_id' => 'required|integer',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $policies = $this->SystemAdminService->show_subscribtions_policies_for_entity($request);
        return response()->json(['message' => 'subscription policies for this entity', 'policies' => $policies]);
    }
}
