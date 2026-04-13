<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Agency;
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
            'agency_name' => 'required|string',
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
            're_subscribed' => 'sometimes|boolean'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $request->validated($request);
        $result = $this->agencyManagerService->subscribe_in_policy($request);
        if (!$result) {
            return response()->json(['message' => 'invalid entity type'], 400);
        }
        return response()->json(['message' => 'agency subscribed in policy successfully', 'subscription' => $result[0], 'payment' => $result[1], 'payment_transaction' => 'fake']);
    }
    public function add_agency_products(Request $request){
        $validate=Validator::make($request->all(),[
        'product_name'=>'required|string',
        'product_type'=>'required|string|in:solar_panel,inverter,battery,accessory',
        'product_brand'=>'sometimes|string',
        'model_number'=>'sometimes|string',
        'quantity'=>'sometimes|integer|min:0',
        'price'=>'required|numeric|min:0',
        'discount_type'=>'sometimes|string|in:percentage,amount',
        'disscount_value'=>'sometimes|numeric|min:0',
        'currency'=>'required|string|in:USD,SY',
        'manufacture_date'=>'sometimes|date',
        'product_image'=>'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if($validate->fails()){
            return response()->json(['message'=>$validate->errors()]);      
    }
    $request->validated($request);
    $result=$this->agencyManagerService->add_agency_products($request);
    if(!$result){
        return response()->json(['message'=>'invalid entity type'],400);   
    }
    return response()->json(['message'=>'product added successfully','product'=>$result[0],'product_image'=>$result[1]]);
}

}
