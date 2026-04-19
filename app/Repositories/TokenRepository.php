<?php
namespace App\Repositories;

use App\Models\Agency_manager;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Refresh_token;
use App\Models\Solar_company_manager;
use App\Models\System_admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class TokenRepository implements TokenRepositoryInterface{

      public function Add_expierd_token($plainTextToken)
    {
        [$id, $token] = explode('|', $plainTextToken);
        PersonalAccessToken::find($id)->update([
            'expires_at' => now()->addHours(100)
        ]);
    }
    public function Refresh_token($refresh_token)
    {
        $refreshToken = DB::table('refresh_tokens')
            ->where('refresh_token', $refresh_token)
            ->where('expires_at', '>', now())
            ->first();
        return $refreshToken;
    }
    public function get_refresh_token_user($refresh_token)
    {
        $refresh_token = Refresh_token::where('refresh_token', $refresh_token)->first();
        $user = $refresh_token->user_table;
        return $user;
    }

    public function Refresh_token_status($planTextToken){
        $token = PersonalAccessToken::findToken($planTextToken);
        $user = $token->tokenable;
        return $user->refreshTokens()->latest()->first();

    }
    public function Add_refresh_token($plainTextToken)
    {
        $refreshToken = Str::random(64);
        $token = PersonalAccessToken::findToken($plainTextToken);
        $user = $token->tokenable;
        if($user instanceof \App\Models\System_admin){
            $user = System_admin::findOrFail($user->id);
            $refresh_token = $user->refreshTokens()->create([
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addDay(7),
            ]);
        }elseif($user instanceof \App\Models\Solar_company_manager){
            $user = Solar_company_manager::findOrFail($user->id);
            $refresh_token = $user->refreshTokens()->create([
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addDay(7),
            ]);
        }
            elseif($user instanceof \App\Models\Agency_manager){
            $user = Agency_manager::findOrFail($user->id);
            $refresh_token = $user->refreshTokens()->create([
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addDay(7),
            ]);
        }
        elseif($user instanceof \App\Models\Customer){
            $user=Customer::findOrFail($user->id);
            $refresh_token=$user->refreshTokens()->create([
                'refresh_token'=>$refreshToken,
                'expires_at'=>now()->addDay(7),
            ]);
        }
        elseif($user instanceof \App\Models\Employee){
        $user=Employee::findOrFail($user->id);
        $refresh_token=$user->refreshTokens()->create([
            'refresh_token'=>$refreshToken,
            'expires_at'=>now()->addDay(7),
        ]);
        }

        // DB::table('refresh_tokens')->insert([
        //     'user_id' => $user->id,
        //     'refresh_token' => $refreshToken,
        //     'expires_at' => now()->addDay(7),
        // ]);

        return $refreshToken;
    }
}
