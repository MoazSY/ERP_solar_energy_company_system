<?php

namespace App\Http\Middleware;

use App\Models\Solar_company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class Check_company_active
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

        // Check if user has any active companies
        $activeCompany = Solar_company::where('solar_company_manager_id', $user->id)
            ->where('company_status', 'active')
            ->exists();

        if (!$activeCompany) {
            return response()->json(['message' => 'No active company found for this manager'], 403);
        }

        return $next($request);
    }
}
