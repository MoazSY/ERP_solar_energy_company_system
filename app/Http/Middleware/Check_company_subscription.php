<?php

namespace App\Http\Middleware;

use App\Models\Solar_company_manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class Check_company_subscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('company_manager')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $companyManager = Auth::guard('company_manager')->user();
        $companyManager=Solar_company_manager::findOrFail($companyManager->id);
        $company = $companyManager->solarCompanies()->first();

        if (!$company) {
            return response()->json(['message' => 'Company not found for logged in manager'], 403);
        }

        $subscriptionQuery = $company->companyAgencySubscribes()->where('is_active', true);

        if ($request->filled('subscribe_policy_id')) {
            $subscriptionQuery->where('subscribe_policy_id', $request->subscribe_policy_id);
        }

        if (!$subscriptionQuery->exists()) {
            return response()->json(['message' => 'Company subscription is not active'], 403);
        }

        return $next($request);
    }
}
