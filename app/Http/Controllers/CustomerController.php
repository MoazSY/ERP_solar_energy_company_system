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
}
