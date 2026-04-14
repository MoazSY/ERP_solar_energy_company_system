<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class Check_agency_active
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

        if (!Auth::guard('agency_manager')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('agency_manager')->user();

        // Check if user has any active agencies
        $activeAgency = Agency::where('agency_manager_id', $user->id)
            ->where('agency_status', 'active')
            ->exists();

        if (!$activeAgency) {
            return response()->json(['message' => 'No active agency found for this manager'], 403);
        }

        return $next($request);
    }
}
