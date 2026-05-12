<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class OsrmService
{
    protected string $baseUrl;

    public function __construct()
    {
        // يمكن تغييرها لاحقًا إلى سيرفرك الخاص
        $this->baseUrl = rtrim(config('services.osrm.base_url', 'https://router.project-osrm.org'), '/');
    }

    /**
     * حساب المسافة والزمن بين نقطتين
     */
    public function getDrivingDistance(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng
    ): array {
        $url = "{$this->baseUrl}/route/v1/driving/{$originLng},{$originLat};{$destinationLng},{$destinationLat}";

        $response = Http::timeout(10)->get($url, [
            'overview' => 'false',
        ]);

        if (!$response->successful()) {
            throw new Exception('OSRM request failed');
        }

        $data = $response->json();

        if (($data['code'] ?? null) !== 'Ok') {
            throw new Exception($data['message'] ?? 'OSRM returned non-success code');
        }

        if (!isset($data['routes'][0])) {
            throw new Exception('No route found');
        }

        return [
            'distance_km' => round($data['routes'][0]['distance'] / 1000, 2),
            'duration_minutes' => round($data['routes'][0]['duration'] / 60, 2),
        ];
    }

    /**
     * Return only the distance in kilometers between two points using OSRM.
     */
    public function distanceKmBetween(float $originLat, float $originLng, float $destinationLat, float $destinationLng): ?float
    {
        try {
            $data = $this->getDrivingDistance($originLat, $originLng, $destinationLat, $destinationLng);
            return isset($data['distance_km']) ? (float) $data['distance_km'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function calculate_delivery_fee_for_order_list($agency, $orderList): array
    {
        $company = $orderList->request_entity;

        $agencyAddress = $agency->addresses()->latest('id')->first();
        $companyAddress = $company?->addresses()->latest('id')->first();

        if (!$agencyAddress || !$companyAddress) {
            return ['error' => 'Agency/company address is missing for delivery calculation'];
        }

        if (
            $agencyAddress->latitude === null ||
            $agencyAddress->longitude === null ||
            $companyAddress->latitude === null ||
            $companyAddress->longitude === null
        ) {
            return ['error' => 'Agency/company coordinates are required for delivery calculation'];
        }
        // هنا يجب حسب سياسة الجهة الطالبة المسعرة
        $ruleQuery = $agency
            ->deliveryRules()
            ->where('is_active', true)
            ->where('governorate_id', $companyAddress->governorate_id)
            ->where(function ($query) use ($companyAddress) {
                if ($companyAddress->area_id) {
                    $query
                        ->where('area_id', $companyAddress->area_id)
                        ->orWhereNull('area_id');
                } else {
                    $query->whereNull('area_id');
                }
            });

        if ($companyAddress->area_id) {
            $ruleQuery->orderByRaw('CASE WHEN area_id = ? THEN 0 ELSE 1 END', [$companyAddress->area_id]);
        }

        $rule = $ruleQuery->latest('id')->first();

        if (!$rule) {
            return ['error' => 'No active delivery pricing rule found for company location'];
        }

        try {
            $distanceData = $this->getDrivingDistance(
                (float) $agencyAddress->latitude,
                (float) $agencyAddress->longitude,
                (float) $companyAddress->latitude,
                (float) $companyAddress->longitude
            );
        } catch (\Throwable $exception) {
            return ['error' => 'Failed to calculate driving distance using OSRM'];
        }

        $weightKg = $orderList
            ->Items()
            ->with(['product.inverters', 'product.batteries', 'product.solarPanals'])
            ->get()
            ->sum(function ($item) {
                $unitWeight = $item->product?->inverters?->weight_kg
                    ?? $item->product?->batteries?->weight_kg
                    ?? $item->product?->solarPanals?->weight_kg
                    ?? 0;

                return (float) $unitWeight * (int) ($item->quantity ?? 1);
            });

        $baseFee = (float) ($rule->delivery_fee ?? 0);
        $distanceFee = ((float) $distanceData['distance_km']) * (float) ($rule->price_per_km ?? 0);

        $maxWeight = (float) ($rule->max_weight_kg ?? 0);
        $extraWeight = max($weightKg - $maxWeight, 0);
        $extraWeightFee = $extraWeight * (float) ($rule->price_per_extra_kg ?? 0);

        $deliveryFee = $baseFee + $distanceFee + $extraWeightFee;

        if (strtoupper((string) $rule->currency) === 'USD') {
            $deliveryFee *= 1.35;
        }
        // في الداتا بيز اريد العملة القديمة كما هي بينما عند الدفع احول فقط
        return [
            'rule_id' => $rule->id,
            'distance_km' => $distanceData['distance_km'],
            'duration_minutes' => $distanceData['duration_minutes'],
            'weight_kg' => round($weightKg, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'currency' => 'SY',
        ];
    }

    public function calculateDeliveryFeeForPurchase($agency, $company, $products, $productsMap): array
    {
        $agencyAddress = $agency->addresses()->latest('id')->first();
        $companyAddress = $company->addresses()->latest('id')->first();

        if (!$agencyAddress || !$companyAddress) {
            return ['error' => 'Agency/company address is missing for delivery calculation'];
        }

        if (
            $agencyAddress->latitude === null ||
            $agencyAddress->longitude === null ||
            $companyAddress->latitude === null ||
            $companyAddress->longitude === null
        ) {
            return ['error' => 'Agency/company coordinates are required for delivery calculation'];
        }

        $ruleQuery = $agency
            ->deliveryRules()
            ->where('is_active', true)
            ->where('governorate_id', $companyAddress->governorate_id)
            ->where(function ($query) use ($companyAddress) {
                if ($companyAddress->area_id) {
                    $query
                        ->where('area_id', $companyAddress->area_id)
                        ->orWhereNull('area_id');
                } else {
                    $query->whereNull('area_id');
                }
            });

        if ($companyAddress->area_id) {
            $ruleQuery->orderByRaw('CASE WHEN area_id = ? THEN 0 ELSE 1 END', [$companyAddress->area_id]);
        }

        $rule = $ruleQuery->latest('id')->first();

        if (!$rule) {
            return ['error' => 'No active delivery pricing rule found for company location'];
        }

        try {
            $distanceData = $this->getDrivingDistance(
                (float) $agencyAddress->latitude,
                (float) $agencyAddress->longitude,
                (float) $companyAddress->latitude,
                (float) $companyAddress->longitude
            );
        } catch (\Throwable $exception) {
            return ['error' => 'Failed to calculate driving distance using OSRM'];
        }

        $weightKg = collect($products)->sum(function ($item) use ($productsMap) {
            $product = $productsMap->get($item['id']);
            if (!$product) {
                return 0;
            }

            $unitWeight = $product->inverters?->weight_kg
                ?? $product->batteries?->weight_kg
                ?? $product->solarPanals?->weight_kg
                ?? 0;

            return (float) $unitWeight * (int) ($item['quantity'] ?? 1);
        });

        $baseFee = (float) ($rule->delivery_fee ?? 0);
        $distanceFee = ((float) $distanceData['distance_km']) * (float) ($rule->price_per_km ?? 0);

        $maxWeight = (float) ($rule->max_weight_kg ?? 0);
        $extraWeight = max($weightKg - $maxWeight, 0);
        $extraWeightFee = $extraWeight * (float) ($rule->price_per_extra_kg ?? 0);

        $deliveryFee = $baseFee + $distanceFee + $extraWeightFee;

        if (strtoupper((string) $rule->currency) === 'USD') {
            $deliveryFee *= 1.35;
        } else {
            $deliveryFee /= 100;  // convert from old SYP to new SYP
        }

        return [
            'rule_id' => $rule->id,
            'distance_km' => $distanceData['distance_km'],
            'duration_minutes' => $distanceData['duration_minutes'],
            'weight_kg' => round($weightKg, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'currency' => 'SY',
        ];
    }

    /**
     * Calculate delivery fee when company ships to a customer (company -> customer)
     * Mirrors calculateDeliveryFeeForPurchase but uses company delivery rules and customer address.
     */
    public function calculateDeliveryFeeForCompanyToCustomer($company, $customer, $products, $productsMap): array
    {
        $companyAddress = $company->addresses()->latest('id')->first();

        // Customer address via Address model (customer may not define addresses relation)
        $customerAddress = \App\Models\Address::where('entity_type_type', \App\Models\Customer::class)
            ->where('entity_type_id', $customer->id)
            ->latest('id')
            ->first();

        if (!$companyAddress || !$customerAddress) {
            return ['error' => 'Company or customer address missing for delivery calculation'];
        }

        if (
            $companyAddress->latitude === null ||
            $companyAddress->longitude === null ||
            $customerAddress->latitude === null ||
            $customerAddress->longitude === null
        ) {
            return ['error' => 'Coordinates required for delivery calculation'];
        }

        $ruleQuery = $company
            ->deliveryRules()
            ->where('is_active', true)
            ->where('governorate_id', $customerAddress->governorate_id)
            ->where(function ($query) use ($customerAddress) {
                if ($customerAddress->area_id) {
                    $query
                        ->where('area_id', $customerAddress->area_id)
                        ->orWhereNull('area_id');
                } else {
                    $query->whereNull('area_id');
                }
            });

        if ($customerAddress->area_id) {
            $ruleQuery->orderByRaw('CASE WHEN area_id = ? THEN 0 ELSE 1 END', [$customerAddress->area_id]);
        }

        $rule = $ruleQuery->latest('id')->first();

        if (!$rule) {
            return ['error' => 'No active delivery pricing rule found for customer location'];
        }

        try {
            $distanceData = $this->getDrivingDistance(
                (float) $companyAddress->latitude,
                (float) $companyAddress->longitude,
                (float) $customerAddress->latitude,
                (float) $customerAddress->longitude
            );
        } catch (\Throwable $exception) {
            return ['error' => 'Failed to calculate driving distance using OSRM'];
        }

        $weightKg = collect($products)->sum(function ($item) use ($productsMap) {
            $product = $productsMap->get($item['id']);
            if (!$product) {
                return 0;
            }

            $unitWeight = $product->inverters?->weight_kg
                ?? $product->batteries?->weight_kg
                ?? $product->solarPanals?->weight_kg
                ?? 0;

            return (float) $unitWeight * (int) ($item['quantity'] ?? 1);
        });

        $baseFee = (float) ($rule->delivery_fee ?? 0);
        $distanceFee = ((float) $distanceData['distance_km']) * (float) ($rule->price_per_km ?? 0);

        $maxWeight = (float) ($rule->max_weight_kg ?? 0);
        $extraWeight = max($weightKg - $maxWeight, 0);
        $extraWeightFee = $extraWeight * (float) ($rule->price_per_extra_kg ?? 0);

        $deliveryFee = $baseFee + $distanceFee + $extraWeightFee;

        if (strtoupper((string) $rule->currency) === 'USD') {
            $deliveryFee *= 1.35;
        }

        return [
            'rule_id' => $rule->id,
            'distance_km' => $distanceData['distance_km'],
            'duration_minutes' => $distanceData['duration_minutes'],
            'weight_kg' => round($weightKg, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'currency' => 'SY',
        ];
    }
}
