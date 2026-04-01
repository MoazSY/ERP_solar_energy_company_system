<?php

namespace App\Http\Controllers;

use App\Repositories\TokenRepositoryInterface;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
       protected $otp_code_services,$tokenRepositoryInterface;
public function __construct(OtpService $otp_code_services,TokenRepositoryInterface $tokenRepositoryInterface)
{
$this->otp_code_services=$otp_code_services;
$this->tokenRepositoryInterface=$tokenRepositoryInterface;
}
    public function sendOtp(Request $request,string $otp_type)
    {
        if($otp_type=="WhatsApp"){
        $validator = Validator::make(
            $request->all(),
            [
                'phoneNumber' => 'required|regex:/^09\d{8}$/',
                'user' => 'required|string'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()]);
        }

        $response = $this->otp_code_services->SendOtpMessage($request,$otp_type);
        if ($response->successful()) {
        return response()->json(['message' => 'the code is sent']);
        } else {
        return response()->json(['message' => 'the code is failed to send', 'error' => $response->json()], 500);
        }
        }
        elseif($otp_type=="Email"){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'user' => 'required|string'
        ]);
            if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()]);
        }

        $response = $this->otp_code_services->SendOtpMessage($request,$otp_type);
         return response()->json(['message'=>$response['message'], $response['status'] ? 200 : 429]);


        }
    }
    public function verifyOtp(Request $request,string $otp_type)
    {
        if($otp_type=="WhatsApp"){
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|regex:/^09\d{8}$/',
            'otp' => 'required|digits:6',
            'forRegister'=>'required|boolean'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()]);
        }
    }elseif($otp_type=="Email"){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'forRegister'=>'required|boolean'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()]);
        }
    }

        $response = $this->otp_code_services->VerifyOtp($request,$otp_type);
        if($response==null){
            return response()->json(['message'=>'user type dont send']);
        }
        if ($response['verify'] == false) {
            return response()->json(["message" => $response['message']]);
        } else{
        $user=$response["user"];
        if($user==null&& ($response['user_status']==null || $response['user_status']=='user_not_register')){
            return response()->json(["message" => $response['message']]);
        }

        $token = $user->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $latest_refresh_token=$this->tokenRepositoryInterface->Refresh_token_status($token);
        $refresh_token=$this->tokenRepositoryInterface->Refresh_token($latest_refresh_token);
        if($refresh_token){
        return response()->json(["message" => $response['message'],"token"=>$token,"refresh_token"=>$refresh_token,'user_status'=>$response['user_status']]);
        }
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);
        return response()->json(["message" => $response['message'],"token"=>$token,"refresh_token"=>$refresh_token,'user_status'=>$response['user_status']]);

        }
    }
    public function Refresh_token(Request $request)
    {
        $validate = Validator::make($request->all(), ['refresh_token' => 'required|string']);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()]);
        }
        $refresh_token=$this->otp_code_services->refresh_token($request);
        if($refresh_token==null){
            response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }
        $user=$refresh_token["user"];
        $plainTextToken = $user->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($plainTextToken);
        return response()->json(['message'=>$refresh_token["message"],"token"=>$plainTextToken,"refresh_token"=>$request->refresh_token]);
    }
    public function login(Request $request){
        $validator=Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required|alpha_num|min:8'
        ]);
        if($validator->fails()){
            return response()->json(['message'=>$validator->errors()]);
        }
        $result=$this->otp_code_services->login($request);
        if($result['status']==true){
        return response()->json(['message'=>'login successfully','user_type'=>$result['user_type'],'user'=>$result['user'],'token'=>$result['token'],'refresh_token'=>$result['refresh_token']]);
        }
        return response()->json(['message'=>'invalid input data']);
    }
    public function logout(Request $request){
        $logout=$this->otp_code_services->logout($request);
        if($logout['status']==true){
        return response()->json(['message'=>'logout successfully','user'=>$logout['user']]);
        }
        return response()->json(['message'=>'logout faild token unavailable']);
    }
}
