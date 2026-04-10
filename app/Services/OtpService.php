<?php
namespace App\Services;

use App\Models\Agency_manager;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Solar_company_manager;
use App\Models\System_admin;
use App\Notifications\SendOtpNotification;
use App\Repositories\TokenRepositoryInterface;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
// use Laravel\Sanctum\PersonalAccessToken;

class OtpService
{
    protected $tokenRepositoryInterface;

    public function __construct(TokenRepositoryInterface $tokenRepositoryInterface)
    {
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
    }

    public function SendOtpMessage($request, $otp_type)
    {
        $otp = rand(100000, 999999);
        $forRegister = $request->input('forRegister');
        // تحديد المستخدم بناءً على forRegister
        $user = null;
        if ($forRegister == false) {
            // البحث عن المستخدم في جميع الأنواع
            $userTypes = [
                'System_admin' => System_admin::class,
                'Solar_company_manager' => Solar_company_manager::class,
                'Agency_manager' => Agency_manager::class,
                'Customer' => Customer::class,
                'Employee' => Employee::class
            ];

            if ($otp_type == 'WhatsApp') {
                $local_phone = $request->input('phoneNumber');
                $column_value = '963' . substr($local_phone, 1);
                $column_type = 'phoneNumber';
            } elseif ($otp_type == 'Email') {
                $column_value = $request->input('email');
                $column_type = 'email';
            }

            foreach ($userTypes as $typeName => $modelClass) {
                $foundUser = $modelClass::where($column_type, '=', $column_value)->first();
                if ($foundUser) {
                    $user = $typeName;
                    break;
                }
            }
        }
        // إذا forRegister=true أو لم يتم العثور على المستخدم، user يبقى null

        $array = ['otp' => $otp, 'user' => $user, 'status' => 'pending','forRegister'=>$forRegister];
        if ($otp_type == 'WhatsApp') {
            $local_phone = $request->input('phoneNumber');
            if (Cache::has('otp_lock_' . $local_phone)) {
                return [
                    'status' => false,
                    'message' => 'Please wait before requesting another OTP'
                ];
            }
            cache()->put('otp_lock_' . $local_phone, true, now()->addMinute(1));

            $url = env('WHATSAPP_API_URL') . '/' . env('WHATSAPP_INSTANCE_ID') . '/messages/chat';
            $intrnalPhone = '963' . substr($local_phone, 1);
            cache()->put('otp_' . $intrnalPhone, $array, now()->addMinute(10));
            $response = Http::asForm()->post($url, [
                'token' => env('WHATSAPP_TOKEN'),
                'to' => $intrnalPhone,
                'body' => "Your verification code is: $otp"
            ]);
            return $response;
        } elseif ($otp_type == 'Email') {
            $email = $request->input('email');

            if (Cache::has('otp_lock_' . $email)) {
                return [
                    'status' => false,
                    'message' => 'Please wait before requesting another OTP'
                ];
            }
            cache()->put('otp_' . $email, $array, now()->addMinute(10));
            cache()->put('otp_lock_' . $email, true, now()->addMinute(1));
            Notification::route('mail', $email)
                ->notify(new SendOtpNotification($otp));
            return [
                'status' => true,
                'message' => 'OTP sent successfully'
            ];
        }
    }

    public function VerifyOtp($request, $otp_type)
    {
        if ($otp_type == 'WhatsApp') {
            $column_type = 'phoneNumber';
            $localPhone = $request->input('phoneNumber');
            $internalPhone = '963' . substr($localPhone, 1);
            $column_value = $internalPhone;
            $cachedOtp = cache('otp_' . $internalPhone);
        } elseif ($otp_type == 'Email') {
            $column_type = 'email';
            $email = $request->input('email');
            $column_value = $email;
            $cachedOtp = cache('otp_' . $email);
        }
        // $forRegister = $request->input('forRegister');
        $forRegister = $cachedOtp['forRegister']; // الحصول على forRegister من الكاش
        $otp = $cachedOtp['otp'];
        if (!$otp || $otp != $request->otp) {
            return ['message' => 'code otp unvaliad or expiered', 'verify' => false];
        }
        if ($forRegister == false && $otp == $request->otp) {
            if ($cachedOtp['user'] == 'System_admin') {
                $admin = System_admin::where($column_type, '=', $column_value)->first();
                if ($admin) {
                    $user = $admin;
                    $userStatus = 'user_register';
                } else {
                    $userStatus = 'user_not_register';
                }
            } elseif ($cachedOtp['user'] == 'Solar_company_manager') {
                $solar_company_manager = Solar_company_manager::where($column_type, '=', $column_value)->first();
                if ($solar_company_manager) {
                    $user = $solar_company_manager;
                    $userStatus = 'user_register';
                } else {
                    $userStatus = 'user_not_register';
                }
            } elseif ($cachedOtp['user'] == 'Agency_manager') {
                $agency_manager = Agency_manager::where($column_type, '=', $column_value)->first();
                if ($agency_manager) {
                    $user = $agency_manager;
                    $userStatus = 'user_register';
                } else
                    $userStatus = 'user_not_register';
            } elseif ($cachedOtp['user'] == 'Customer') {
                $customer = Customer::where($column_type, '=', $column_value)->first();
                if ($customer) {
                    $user = $customer;
                    $userStatus = 'user_register';
                } else
                    $userStatus = 'user_not_register';
            } elseif ($cachedOtp['user'] == 'Employee') {
                $employee = Employee::where($column_type, '=', $column_value)->first();
                if ($employee) {
                    $user = $employee;
                    $userStatus = 'user_register';
                } else
                    $userStatus = 'user_not_register';
            } else {
                return null;
            }
            if ($userStatus == 'user_not_register') {
                return ['message' => 'code verify successfully but user not register in system', 'verify' => true, 'user' => null, 'user_status' => $userStatus];  // هناالمستخدم غير مسجل بالنظام لكن يتحقق من الكود لكي يسجل دخول وهو غير مسجل في النظام
            }

            cache()->forget('otp_' . $column_value);
            return ['message' => 'code verify successfully login successfully', 'verify' => true, 'user' => $user, 'user_status' => $userStatus];  // هنا المستخدم مسجل في النظام ويتحقق من الكود من اجل ان يسجل دخوله
        } elseif ($forRegister && $otp == $request->otp) {
            $cachedOtp['status'] = 'verified';
            if ($otp_type == 'WhatsApp') {
                Cache::put('otp_' . $internalPhone, $cachedOtp, now()->addMinutes(10));
            } else {
                Cache::put('otp_' . $email, $cachedOtp, now()->addMinutes(10));
            }
            return ['message' => 'code verify successfully ready to register', 'verify' => true, 'user' => null, 'user_status' => null];  // هنا المستخدم غير مسجل في النظام ويتحقق من الكود من اجل ان يسجل
        }
    }

    public function refresh_token($request)
    {
        $refresh_token = $request->refresh_token;
        $refresh = $this->tokenRepositoryInterface->Refresh_token($refresh_token);
        if (!$refresh) {
            return null;
        }
        $user = $this->tokenRepositoryInterface->get_refresh_token_user($refresh_token);

        return ['message' => 'token refresh successfully', 'user' => $user];
    }

    public function login($request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $userType = [
            \App\Models\System_admin::class,
            \App\Models\Solar_company_manager::class,
            \App\Models\Agency_manager::class,
            \App\Models\Employee::class,
            \App\Models\Customer::class
        ];
        foreach ($userType as $type) {
            $user = $type::where('email', '=', $email)->first();
            if ($user && Hash::check($password, $user->password)) {
                $token = $user->createToken('authToken')->plainTextToken;
                $this->tokenRepositoryInterface->Add_expierd_token($token);
                $latest_refresh_token = $this->tokenRepositoryInterface->Refresh_token_status($token);
                $refresh_token = $this->tokenRepositoryInterface->Refresh_token($latest_refresh_token);
                if (!$refresh_token) {
                    $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);
                }
                return ['token' => $token, 'refresh_token' => $refresh_token, 'user_type' => class_basename($type), 'user' => $user, 'status' => true];
            }
        }
        return ['status' => false];
    }

    public function logout($request)
    {
        $accessToken = $request->user()?->currentAccessToken();
        if ($accessToken) {
            $user = $accessToken->tokenable;
            $user->refreshTokens()->delete();
            $request->user()->tokens()->delete();
            return ['status' => true, 'user' => $user];
        }
        return ['status' => false];
    }
}
