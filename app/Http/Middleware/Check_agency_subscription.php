<?php

namespace App\Http\Middleware;

use App\Models\Agency_manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class Check_agency_subscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('agency_manager')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $agencyManager = Auth::guard('agency_manager')->user();
        $agencyManager = Agency_manager::findOrFail($agencyManager->id);
        $agency = $agencyManager->agencies()->first();

        if (!$agency) {
            return response()->json(['message' => 'Agency not found for logged in manager'], 403);
        }

        $subscriptionQuery = $agency->companyAgencySubscribes()->where('is_active', true);

        if ($request->filled('subscribe_policy_id')) {
            $subscriptionQuery->where('subscribe_policy_id', $request->subscribe_policy_id);
        }

        if (!$subscriptionQuery->exists()) {
            return response()->json(['message' => 'Agency subscription is not active'], 403);
        }

        return $next($request);
    }
}
