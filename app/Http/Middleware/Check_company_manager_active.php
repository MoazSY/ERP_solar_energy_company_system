<?php

namespace App\Http\Middleware;

// use App\Models\Solar_company_manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class Check_company_manager_active
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Token has expired'], 401);
        }

        if (!Auth::guard('company_manager')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('company_manager')->user();
        if (!$user || $user->Activate_Account != true) {
            return response()->json(['message' => 'Company manager account is not active'], 403);
        }

        return $next($request);
    }
}
