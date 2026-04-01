<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Areas;
use App\Models\Governorates;
use App\Services\SystemAdminService;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class System_admin extends Controller
{
    protected $SystemAdminService;
    public function __construct(SystemAdminService $SystemAdminService)
    {
        $this->SystemAdminService=$SystemAdminService;
    }
    public function Register(Request $request){
    $validate=Validator::make($request->all(),[
        'first_name'=>'required|string',
        'last_name'=>'required|string',
        'date_of_birth' => 'required|date',
        'email' => 'required|email',
        'password'=>'required|alpha_num|min:8',
        'phoneNumber' => 'required|regex:/^09\d{8}$/',
        'account_number'=>'sometimes|string',
        'image'=>'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'about_him'=>'sometimes|string',
    ]);
    if($validate->fails()){
        return response()->json(['message'=>$validate->errors()]);
    }
    $intrnalPhone='963'.substr($request['phoneNumber'],1);
            $cached_phone = Cache::get('otp_' . $intrnalPhone);
            $cached_email=Cache::get('otp_'.$request['email']);
        if (!$cached_phone|| !$cached_email) {
            return response()->json([
                'message' => 'OTP expired or not verified'
            ], 400);
        }
        if($cached_phone['status'] !== 'verified' || $cached_email['status'] !== 'verified') {
            return response()->json([
                'message' => 'OTP not verified',
                'phone'=>$cached_phone['status'],
                'email'=>$cached_email['status']
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
        $result=$this->SystemAdminService->register($request,$data);
        cache()->forget('otp_' . $intrnalPhone);
        cache()->forget('otp_' . $request['email']);    
        return response()->json(['message'=>'admin register successfully','admin'=>$result['admin'],'imageUrl'=>$result['imageUrl'],'token'=>$result['token'],'refresh_token'=>$result['refresh_token']]);
    }
    public function Admin_profile(){
        $profile=$this->SystemAdminService->Admin_profile();
        if(!$profile){
        return response()->json(['message'=>'admin profile not found',404]);
       }
       return response()->json(['message'=>'admin profile retrieved successfully',
       'profile'=>$profile]);
    }
    public function update_profile(Request $request){
        $validate=Validator::make($request->all(),[
        'first_name'=>'sometimes|string',
        'last_name'=>'sometimes|string',
        'date_of_birth' => 'sometimes|date',
        'email' => 'sometimes|email',
        'password'=>'sometimes|alpha_num|min:8',
        'phoneNumber' => 'sometimes|regex:/^09\d{8}$/',
        'account_number'=>'sometimes|string',
        'image'=>'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'about_him'=>'sometimes|string',
        ]);
        if($validate->fails()){
            return response()->json(['message'=>$validate->errors()]);
        }

     $uniqueRequest = app(StoreUserRequest::class);
             $uniqueRequest->ignoreId = $request->user()->id;
    $uniqueRequest->ignoreTable = 'system_admins';
    $uniqueRequest->merge([
        'email' => $request->email,
        'phoneNumber' => $request->phoneNumber,
    ]);

    $uniqueRequest->prepareForValidation();

    $uniqueValidator = Validator::make(
        $uniqueRequest->all(),
        $uniqueRequest->rules()
    )->validate();

    $data=array_merge($uniqueValidator,$validate->validated());
    $profile=$this->SystemAdminService->update_profile($request,$data);
    return response()->json(['message'=>'admin profile update','profile'=>$profile[0],'imageUrl'=>$profile[1]]);
    }
    public function Add_governorates(Request $request){
        $validate=Validator::make($request->all(),[
            'name'=>'required|string'
        ]);
        if($validate->fails()){
            return response()->json(['message'=>$validate->errors()]);
        }
        $result=$this->SystemAdminService->add_governorates($request);
        return response()->json(['message'=>'governorate add successfully','governorate'=>$result]);
    }
    public function Add_area(Request $request,Governorates $governorates){
     $validate=Validator::make($request->all(),[
       'area'=>'required|array',
        'area.*.name'=>'required|string'
     ]);
     if($validate->fails()){
        return response()->json(['message'=>$validate->errors()]);
     }
     $area=$this->SystemAdminService->add_area($request,$governorates);
     return response()->json(['message'=>'areas added to governorate successfully','areas'=>$area]);
    }
    public function add_neighborhoods(Request $request,Areas $area){
        $validate=Validator::make($request->all(),[
            'neighborhoods'=>'required|array',
            'neighborhoods.*.name'=>'required|string'
        ]);
        if($validate->fails()){
            return response()->json(['message'=>$validate->errors()]);
        }
        $neighborhoods=$this->SystemAdminService->add_neighborhoods($request,$area);
        return response()->json(['message'=>'neighborhoods added to area successfully','neighborhoods'=>$neighborhoods]);

    }
    public function get_governorates(){
        $governorates=$this->SystemAdminService->get_governorates();
        return response()->json(['message'=>'all governorates','governorates'=>$governorates]);
    }
    public function get_areas(Governorates $governorates){
        $areas=$this->SystemAdminService->get_areas($governorates);
        return response()->json(['message'=>'all areas','areas'=>$governorates]);
    }
    public function get_neighborhoods(Areas $area){
        $neighborhoods=$this->SystemAdminService->get_neighborhoods($area);
        return response()->json(['message'=>'all neighborhoods','neighborhoods'=>$neighborhoods]);

    }
    public function get_UnActive_company(){
        $company=$this->SystemAdminService->UnActive_company();
        return response()->json(['message'=>'all un active company','company'=>$company]);
    }
    public function proccess_company_register(Request $request){
        $validate=Validator::make($request->all(),[
        'entity_type'=>'required|in:solar_company,agency',
        'entity_id'=>'required|integer',
        'status'=>'required|in:pending,approved,rejected',
        'rejection_reason'=>'sometimes|string',
        ]);
        if($validate->fails()){
            return response()->json(['message'=>$validate->errors()]);
        }
        $proccess=$this->SystemAdminService->proccess_company_register($request);
        return response()->json(['message'=>'the result of register proccess','result'=>$proccess]);
    }
}
