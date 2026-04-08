<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

use Illuminate\Support\Facades\Auth;
class Check_employee
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
                        $token=PersonalAccessToken::findToken($request->bearerToken());
            if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Token has expired'], 401);
        }
            if(!Auth::guard('employee')->check()){
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        return $next($request);
    }
}
