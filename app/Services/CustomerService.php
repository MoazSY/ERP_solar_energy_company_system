<?php

namespace App\Services;

use App\Models\Company_protofolio;
use App\Models\Customer;
use App\Models\Customer_electrical_device_characteristic;
use App\Models\Metainence_request;
use App\Models\Offers;
use App\Models\Products;
use App\Models\Project_task;
use App\Models\Purchase_invoice;
use App\Models\Report;
use App\Models\Request_solar_system;
use App\Models\Solar_company;
use App\Models\Subscribe_offer;
use App\Models\Technical_inspection_request;
use App\Repositories\CustomerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    protected $customerRepositoryInterface;
    protected $tokenRepositoryInterface;
    protected $osrmService;

    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        TokenRepositoryInterface $tokenRepositoryInterface,
        OsrmService $osrmService
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
        $this->osrmService = $osrmService;
    }

    public function register($request, $data)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $imagepath = $request->file('image')->storeAs('Customer/images', $image, 'public');
            $customer = $this->customerRepositoryInterface->Create($request, $imagepath, $data);
            $imageUrl = asset('storage/' . $imagepath);
        } else {
            $customer = $this->customerRepositoryInterface->Create($request, null, $data);
            $imageUrl = null;
        }

        $token = $customer->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);

        return [
            'customer' => $customer,
            'token' => $token,
            'refresh_token' => $refresh_token,
            'imageUrl' => $imageUrl,
        ];
    }

    public function customer_profile()
    {
        $customer = Auth::guard('customer')->user();
        $profile = $this->customerRepositoryInterface->customer_profile($customer->id);
        $imageUrl = $profile->image ? asset('storage/' . $profile->image) : null;

        return ['customer' => $profile, 'imageUrl' => $imageUrl];
    }

    public function update_profile($request, $data)
    {
        $customer_id = Auth::guard('customer')->user()->id;
        $customer = $this->customerRepositoryInterface->findCustomerById($customer_id);

        if ($request->hasFile('image')) {
            $originalName = $request->file('image')->getClientOriginalName();
            $path = $request->file('image')->storeAs('Customer/images', $originalName, 'public');
            $data['image'] = $path;
            $imageUrl = asset('storage/' . $path);
        } else {
            $imageUrl = $customer->image ? asset('storage/' . $customer->image) : null;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer = $this->customerRepositoryInterface->updateCustomer($customer, $data);

        return [$customer, $imageUrl];
    }

    public function show_company_offers($company_id)
    {
        $customer = $this->currentCustomer();
        $offers = $this->customerRepositoryInterface->show_company_offers($company_id, $customer?->id);

        $result = $offers->map(function ($offer) {
            $panarImagesUrl = [];
            if (is_array($offer->panar_image) || $offer->panar_image instanceof \ArrayAccess) {
                $panarImagesUrl = array_map(function ($path) {
                    return asset('storage/' . $path);
                }, (array) $offer->panar_image);
            }
            $videoUrl = $offer->video ? asset('storage/' . $offer->video) : null;

            return [
                'offer' => $offer,
                'panarImages' => $panarImagesUrl,
                'video' => $videoUrl,
            ];
        });

        return $result;
    }

    public function get_all_electrical_devices()
    {
        return $this->customerRepositoryInterface->find_all_electrical_devices();
    }

    private function currentCustomer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }

    private function offerToArray(Offers $offer): array
    {
        $panarImagesUrl = [];
        $panarImages = $offer->panar_image ?? [];
        if (is_array($panarImages)) {
            $panarImagesUrl = array_map(function ($path) {
                return $this->storageUrl($path);
            }, $panarImages);
        }

        return [
            'offer' => $offer->loadMissing(['Items', 'Items.product']),
            'panarImages' => $panarImagesUrl,
            'video' => $this->storageUrl($offer->video),
        ];
    }

    private function subscriptionToArray(Subscribe_offer $subscription): array
    {
        $subscription->loadMissing(['offer.Items.product', 'customer']);

        return [
            'subscription' => $subscription,
            'offer' => $subscription->offer ? $this->offerToArray($subscription->offer) : null,
        ];
    }

    private function requestSolarSystemToArray(Request_solar_system $requestSolarSystem): array
    {
        $powerSummary = $this->electricalDevicePowerSummary($requestSolarSystem);

        return [
            'request' => $requestSolarSystem,
            'surface_image' => $this->storageUrl($requestSolarSystem->surface_image),
            'electrical_devices' => $requestSolarSystem->electricalDeviceCharacteristics->map(function (Customer_electrical_device_characteristic $characteristic) {
                return [
                    'id' => $characteristic->id,
                    'electrical_device' => $characteristic->electricalDevice,
                    'capacity' => $characteristic->capacity,
                    'unit' => $characteristic->unit,
                    'usage_time' => $characteristic->usage_time,
                    'notes' => $characteristic->notes,
                ];
            }),
            'power_summary' => $powerSummary,
        ];
    }

    private function electricalDevicePowerSummary(Request_solar_system $requestSolarSystem): array
    {
        $devices = $requestSolarSystem->electricalDeviceCharacteristics ?? collect();

        $totalCapacity = (float) $devices->sum(function (Customer_electrical_device_characteristic $characteristic) {
            return (float) ($characteristic->capacity ?? 0);
        });

        return [
            'total_capacity' => $totalCapacity,
            'total_capacity_kw' => round($totalCapacity / 1000, 3),
            'dayly_capacity' => (float) $devices
                ->where('usage_time', 'dayly')
                ->sum(fn(Customer_electrical_device_characteristic $characteristic) => (float) ($characteristic->capacity ?? 0)),
            'nightly_capacity' => (float) $devices
                ->where('usage_time', 'nightly')
                ->sum(fn(Customer_electrical_device_characteristic $characteristic) => (float) ($characteristic->capacity ?? 0)),
        ];
    }

    private function maintenanceRequestToArray(Metainence_request $maintenanceRequest): array
    {
        $maintenanceRequest->loadMissing(['company']);

        return [
            'request' => $maintenanceRequest,
            'image_state' => $this->storageUrl($maintenanceRequest->image_state),
        ];
    }

    private function technicalInspectionToArray(Technical_inspection_request $inspection): array
    {
        $inspection->loadMissing(['company']);

        return [
            'inspection' => $inspection,
            'image_state' => $this->storageUrl($inspection->image_state),
        ];
    }

    private function requestHasInvoice(string $entityType, int $entityId): bool
    {
        return \App\Models\Purchase_invoice::where('object_entity_type', $entityType)
            ->where('object_entity_id', $entityId)
            ->exists();
    }

    private function invoiceToArray(Purchase_invoice $invoice): array
    {
        $invoice->loadMissing(['orderList.Items.product', 'payments', 'seller_entity', 'buyer_entity', 'object_entity']);

        return ['invoice' => $invoice];
    }

    private function portfolioToArray(Company_protofolio $portfolio): array
    {
        $projectImages = [];
        if (is_array($portfolio->project_images)) {
            $projectImages = array_map(function ($path) {
                return $this->storageUrl($path);
            }, $portfolio->project_images);
        }

        $projectVideos = [];
        if (is_array($portfolio->project_videos)) {
            $projectVideos = array_map(function ($path) {
                return $this->storageUrl($path);
            }, $portfolio->project_videos);
        }

        return [
            'portfolio' => $portfolio->loadMissing(['projectTask.customerRateFeedbacks', 'company']),
            'project_cover_image' => $this->storageUrl($portfolio->project_cover_image),
            'project_images' => $projectImages,
            'project_videos' => $projectVideos,
        ];
    }

    private function reportToArray(Report $report): array
    {
        return ['report' => $report->loadMissing(['company', 'customer', 'admin'])];
    }

    private function adminIdForCustomerReports(): ?int
    {
        return $this->customerRepositoryInterface->first_admin_id();
    }

    public function show_my_specific_offers()
    {
        $customer = $this->currentCustomer();
        $offers = $this
            ->customerRepositoryInterface
            ->show_my_specific_offers($customer->id)
            ->map(function (Offers $offer) {
                return $this->offerToArray($offer);
            });

        return $offers;
    }

    public function subscribe_offer($request, $offer_id)
    {
        $customer = $this->currentCustomer();
        $offer = $this->customerRepositoryInterface->findOfferById($offer_id);

        if (!$offer) {
            return ['error' => 'offer not found'];
        }

        if (!$offer->offer_available) {
            return ['error' => 'offer is not available'];
        }

        if ($offer->public_private === 'private' && (int) $offer->customer_id !== (int) $customer->id) {
            return ['error' => 'offer is not assigned to this customer'];
        }

        $baseAmount = (float) ($offer->average_total_amount ?: max((float) $offer->subtotal_amount - (float) $offer->discount_amount, 0));

        // Calculate delivery cost using OSRM service (company -> customer)
        $company = Solar_company::find($offer->company_id);
        if (!$company) {
            return ['error' => 'company not found'];
        }

        // Extract products and productsMap from the offer items
        $products = collect($offer->Items)->map(function ($item) {
            return [
                'id' => $item->product_id,
                'quantity' => $item->quantity ?? 1,
            ];
        })->values();

        $productsMap = collect($offer->Items)
            ->filter(function ($item) {
                return $item->product !== null;
            })
            ->mapWithKeys(function ($item) {
                return [$item->product_id => $item->product];
            });

        $deliveryPricing = $this->osrmService->calculateDeliveryFeeForCompanyToCustomer(
            $company,
            $customer,
            $products,
            $productsMap
        );

        $deliveryAmount = 0;
        if (isset($deliveryPricing['error'])) {
            // If OSRM fails, fallback to repository method (base fee only)
            $deliveryAmount = $this->customerRepositoryInterface->calculateDeliveryCost(
                $customer->id,
                $offer->company_id
            );
        } else {
            $deliveryAmount = (float) ($deliveryPricing['delivery_fee'] ?? 0);
        }

        $installationAmount = $request->boolean('with_installation', true)
            ? (float) ($offer->average_installation_cost ?? 0) + (float) ($offer->average_metal_installation_cost ?? 0)
            : 0;
        $additionalCostAmount = (float) $request->input('additional_cost_amount', 0);
        $additionalEntitlementAmount = (float) $request->input('additional_entitlement_amount', 0);
        $finalAmount = max($baseAmount + $deliveryAmount + $installationAmount + $additionalCostAmount - $additionalEntitlementAmount, 0);

        $subscription = $this->customerRepositoryInterface->upsert_offer_subscription($offer->id, $customer->id, [
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'customer_phone' => $customer->phoneNumber,
            'system_sn' => $request->input('system_sn') ?: null,
            'with_installation' => $request->boolean('with_installation', true),
            'subscription_status' => 'accepted',
            'subscription_date' => now(),
            'total_amount' => $baseAmount,
            'additional_cost_amount' => $additionalCostAmount,
            'additional_entitlement_amount' => $additionalEntitlementAmount,
            'final_amount' => $finalAmount,
        ]);

        return $this->subscriptionToArray($subscription->fresh());
    }

    public function unsubscribe_offer($request, $offer_id)
    {
        $customer = $this->currentCustomer();
        $subscription = $this->customerRepositoryInterface->find_offer_subscription($offer_id, $customer->id);

        if (!$subscription) {
            return ['error' => 'subscription not found'];
        }

        $subscription->subscription_status = 'rejected';
        $subscription = $this->customerRepositoryInterface->update_offer_subscription($subscription, ['subscription_status' => 'rejected']);

        return $this->subscriptionToArray($subscription);
    }

    public function show_subscribe_offers()
    {
        $customer = $this->currentCustomer();

        return $this
            ->customerRepositoryInterface
            ->show_subscribe_offers($customer->id)
            ->map(function (Subscribe_offer $subscription) {
                return $this->subscriptionToArray($subscription);
            });
    }

    public function request_solar_system($request)
    {
        /*
         * اذا تم ارسال معرف الشركة يتم البحث عن الطلبية بمعرف الشركة وتعديلها
         *          اذا لم يتم ايجادها فيتم انشاء واحدة جديدة
         *          اذا لم يتم ارسال معرف الشركة  يتم البحث عن طلبية فيها ال  company_id  لها  null ولها اجهزة مضافة
         *         يتم البحث عن طلبية فيها ال  company_id  لها  null ولها اجهزة مضافة
         * اذا وجدت يتم تحديثها بالبيانات الجديدة و ربطها بالشركة المختارة
         * اذا لا يوجد شركة null  يتم اضافة طلب جديد
         */
        $customer = $this->currentCustomer();
        $payload = $request->only([
            'company_id',
            'requested_capacity_kw',
            'dayly_consumption_kwh',
            'nightly_consumption_kwh',
            'system_type',
            'invertar_type',
            'inverter_brand',
            'battery_type',
            'battery_brand',
            'solar_panel_type',
            'solar_panel_brand',
            'inverter_capacity_kw',
            'solar_panel_capacity_kw',
            'solar_panel_number',
            'battery_capacity_kwh',
            'battery_number',
            'inverter_voltage_v',
            'battery_voltage_v',
            'expected_budget',
            'metal_base_type',
            'front_base_height_m',
            'back_base_height_m',
            'additional_details',
        ]);

        $payload['customer_id'] = $customer->id;

        if ($request->hasFile('surface_image')) {
            $surfaceImage = $request->file('surface_image')->getClientOriginalName();
            $payload['surface_image'] = $request->file('surface_image')->storeAs('Customer/request_solar_systems', $surfaceImage, 'public');
        }

        if ($request->has('additional_details')) {
            $payload['additional_details'] = $request->input('additional_details');
        }
        if ($request->has('company_id')) {
            $requestSolarSystem = $customer->requestSolarSystems()->where('company_id', $request->company_id)->first();
            if ($requestSolarSystem) {
                $payload['company_id'] = $request->input('company_id');
                $requestSolarSystem->update($payload);
                $requestSolarSystem->save();
                $requestSolarSystem->refresh();
            } else
                $requestSolarSystem = $this->customerRepositoryInterface->create_request_solar_system($payload);
        } else {
            $requestSolarSystem = $customer
                ->requestSolarSystems()
                ->whereNull('company_id')
                ->has('electricalDeviceCharacteristics')
                ->latest()
                ->first();
            if ($requestSolarSystem) {
                $payload['company_id'] = $request->input('company_id');
                $requestSolarSystem->update($payload);
                $requestSolarSystem->save();
                $requestSolarSystem->refresh();
            } else {
                return ['error' => 'no existing request found, and company_id is required to create a new request'];
            }
        }
        return $this->requestSolarSystemToArray($requestSolarSystem);
    }

    public function add_electrical_devices_to_request_solar_system($request)
    {
        /*
         * اذا تم ادخال ال  request id
         * يتم البحث عن الطلبية اذا وجدت يتم تحديث الاجهزة الكهربائية لها
         * اذا لم توجد يتم اضافة الاجهزة الكهربائية لها
         * اذا لم يتم ادخال  request id  يتم انشاء واحدة جديدة واضافة الاجهزة الكهربائية لها
         */
        $customer = $this->currentCustomer();
        $requestId = $request->input('request_id');
        if ($requestId) {
            $requestSolarSystem = $this->customerRepositoryInterface->find_request_solar_system($customer->id, $requestId);
        } else {
            $requestSolarSystem = $this
                ->customerRepositoryInterface
                ->show_customer_solar_system_requests($customer->id)
                ->first();
        }
        if (!$requestSolarSystem) {
            // return ['error' => 'solar system request not found'];
            $requestSolarSystem = Request_solar_system::create([
                'customer_id' => $customer->id,
            ]);
        }

        $electricalDevices = collect($request->input('electrical_devices', []));

        if ($electricalDevices->isEmpty()) {
            return ['error' => 'electrical devices are required'];
        }

        $electricalDevices->each(function (array $device) use ($customer, $requestSolarSystem) {
            $characteristic = $this->customerRepositoryInterface->find_customer_electrical_device_characteristic(
                $requestSolarSystem->id
            );

            $data = [
                'customer_id' => $customer->id,
                'electrical_device_id' => $device['electrical_device_id'],
                'request_solar_system_id' => $requestSolarSystem->id ?? null,
                'capacity' => $device['capacity'],
                'unit' => $device['unit'] ?? 'W',
                'usage_time' => $device['usage_time'] ?? 'dayly',
                'notes' => $device['notes'] ?? null,
            ];

            if ($characteristic) {
                $this->customerRepositoryInterface->update_customer_electrical_device_characteristic($characteristic, $data);
                return;
            }

            $this->customerRepositoryInterface->create_customer_electrical_device_characteristic($data);
        });

        return $this->requestSolarSystemToArray($requestSolarSystem);
    }

    public function request_technical_inspection($request)
    {
        $customer = $this->currentCustomer();
        $payload = $request->only([
            'company_id',
            // 'priority',
            'issue_description',
            // 'inspection_price',
            // 'expected_date',
            // 'payment_method',
            // 'currency',
            'customer_address'
        ]);

        $payload['customer_id'] = $customer->id;
        $payload['customer_name'] = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $payload['customer_phone'] = $customer->phoneNumber;

        // Get customer address from addresses table
        if ($request->has('customer_address') && !empty($request->input('customer_address'))) {
            $payload['customer_address'] = $request->input('customer_address');
        } else {
            $customerAddress = \App\Models\Address::where('entity_type_type', Customer::class)
                ->where('entity_type_id', $customer->id)
                ->first();
            $payload['customer_address'] = $customerAddress ? $customerAddress->address : null;
        }
        $payload['inspection_status'] = 'pending';

        $technicalInspection = $this->customerRepositoryInterface->create_technical_inspection_request($payload);

        return $this->technicalInspectionToArray($technicalInspection->fresh());
    }

    public function show_my_requests()
    {
        $customer = $this->currentCustomer();

        return [
            'solar_system_requests' => $this
                ->customerRepositoryInterface
                ->show_customer_solar_system_requests($customer->id)
                ->map(function (Request_solar_system $requestSolarSystem) {
                    return array_merge($this->requestSolarSystemToArray($requestSolarSystem), [
                        'invoice_created' => $this->requestHasInvoice(Request_solar_system::class, $requestSolarSystem->id),
                    ]);
                }),
            'maintenance_requests' => $this
                ->customerRepositoryInterface
                ->show_customer_maintenance_requests($customer->id)
                ->map(function (Metainence_request $maintenanceRequest) {
                    return array_merge($this->maintenanceRequestToArray($maintenanceRequest), [
                        'invoice_created' => $this->requestHasInvoice(Metainence_request::class, $maintenanceRequest->id),
                    ]);
                }),
            'technical_inspection_requests' => $this
                ->customerRepositoryInterface
                ->show_customer_technical_inspections($customer->id)
                ->map(function (Technical_inspection_request $inspectionRequest) {
                    return array_merge($this->technicalInspectionToArray($inspectionRequest), [
                        'invoice_created' => $this->requestHasInvoice(Technical_inspection_request::class, $inspectionRequest->id),
                    ]);
                }),
            'product_orders' => $this->customerRepositoryInterface->show_customer_product_orders($customer->id),
        ];
    }

    public function show_my_solar_systems()
    {
        $customer = $this->currentCustomer();

        return $this->customerRepositoryInterface->show_customer_solar_systems($customer->id);
    }

    public function filter_requests($request)
    {
        $customer = $this->currentCustomer();
        $kind = $request->input('request_kind', 'all');

        $solarQuery = $this->customerRepositoryInterface->show_customer_solar_system_requests($customer->id)->toQuery();
        if ($request->filled('company_id')) {
            $solarQuery->where('company_id', $request->company_id);
        }
        if ($request->filled('system_type')) {
            $solarQuery->where('system_type', $request->system_type);
        }
        if ($request->filled('date_from')) {
            $solarQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $solarQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $maintenanceQuery = $this->customerRepositoryInterface->show_customer_maintenance_requests($customer->id)->toQuery();
        if ($request->filled('company_id')) {
            $maintenanceQuery->where('company_id', $request->company_id);
        }
        if ($request->filled('metainence_type')) {
            $maintenanceQuery->where('metainence_type', $request->metainence_type);
        }
        if ($request->filled('issue_category')) {
            $maintenanceQuery->where('issue_category', $request->issue_category);
        }
        if ($request->filled('metainence_status')) {
            $maintenanceQuery->where('metainence_status', $request->metainence_status);
        }
        if ($request->filled('date_from')) {
            $maintenanceQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $maintenanceQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $result = [];
        if ($kind === 'all' || $kind === 'solar') {
            $result['solar_system_requests'] = $solarQuery->latest('id')->get()->map(function (Request_solar_system $requestSolarSystem) {
                return $this->requestSolarSystemToArray($requestSolarSystem);
            });
        }
        if ($kind === 'all' || $kind === 'maintenance') {
            $result['maintenance_requests'] = $maintenanceQuery->latest('id')->get()->map(function (Metainence_request $maintenanceRequest) {
                return $this->maintenanceRequestToArray($maintenanceRequest);
            });
        }
        if ($kind === 'all' || $kind === 'orders') {
            $result['product_orders'] = $this->customerRepositoryInterface->show_customer_product_orders($customer->id);
        }

        return $result;
    }

    public function cancel_solar_system_request($request, $request_id)
    {
        $customer = $this->currentCustomer();
        $solarRequest = $this->customerRepositoryInterface->find_request_solar_system($customer->id, $request_id);

        if (!$solarRequest) {
            return ['error' => 'solar system request not found'];
        }
        $hasInvoice = $this->requestHasInvoice(Request_solar_system::class, $solarRequest->id);
        if ($hasInvoice) {
            return ['error' => 'cannot cancel request with existing invoice'];
        }
        $deletedRequest = $this->requestSolarSystemToArray($solarRequest);
        $this->customerRepositoryInterface->delete_request_solar_system($solarRequest);

        return $deletedRequest;
    }

    public function update_solar_system_request($request, $request_id)
    {
        $customer = $this->currentCustomer();
        $solarRequest = $this->customerRepositoryInterface->find_request_solar_system($customer->id, $request_id);

        if (!$solarRequest) {
            return ['error' => 'solar system request not found'];
        }

        $payload = $request->only([
            'company_id',
            'requested_capacity_kw',
            'dayly_consumption_kwh',
            'nightly_consumption_kwh',
            'system_type',
            'invertar_type',
            'inverter_brand',
            'battery_type',
            'battery_brand',
            'solar_panel_type',
            'solar_panel_brand',
            'inverter_capacity_kw',
            'solar_panel_capacity_kw',
            'solar_panel_number',
            'battery_capacity_kwh',
            'battery_number',
            'inverter_voltage_v',
            'battery_voltage_v',
            'expected_budget',
            'metal_base_type',
            'front_base_height_m',
            'back_base_height_m',
        ]);

        if ($request->hasFile('surface_image')) {
            $surfaceImage = $request->file('surface_image')->getClientOriginalName();
            $payload['surface_image'] = $request->file('surface_image')->storeAs('Customer/request_solar_systems', $surfaceImage, 'public');
        }

        $solarRequest = $this->customerRepositoryInterface->update_request_solar_system($solarRequest, $payload);

        return $this->requestSolarSystemToArray($solarRequest->fresh());
    }

    public function show_invoices_details()
    {
        $customer = $this->currentCustomer();

        return $this
            ->customerRepositoryInterface
            ->show_invoices_details($customer->id)
            ->map(function (Purchase_invoice $invoice) {
                return $this->invoiceToArray($invoice);
            });
    }

    public function approve_pay_invoice($request, $invoice_id)
    {
        $customer = $this->currentCustomer();
        $invoice = $this->customerRepositoryInterface->find_customer_invoice($customer->id, $invoice_id);

        if (!$invoice) {
            return ['error' => 'invoice not found'];
        }

        $amount = (float) $request->input('amount', $invoice->total_amount);
        if ($amount <= 0) {
            return ['error' => 'payment amount must be greater than zero'];
        }

        $isFullyPaid = $amount >= (float) $invoice->total_amount;
        $payment = $this->customerRepositoryInterface->create_payment([
            'payable_type' => Customer::class,
            'payable_id' => $customer->id,
            'target_table_type' => $invoice->seller_entity_type,
            'target_table_id' => $invoice->seller_entity_id,
            'payment_object_table_type' => Purchase_invoice::class,
            'payment_object_table_id' => $invoice->id,
            'payment_object_type_name' => 'invoice',
            'amount' => $amount,
            'currency' => $invoice->currency,
            'paid_at' => now(),
            'status' => $isFullyPaid ? 'paid' : 'processing',
            're_subscribed' => false,
        ]);

        $invoice = $this->customerRepositoryInterface->update_invoice_payment_status($invoice, $isFullyPaid ? 'paid' : 'partially_paid');

        return [
            'invoice' => $invoice->fresh(['orderList.Items.product', 'payments']),
            'payment' => $payment,
        ];
    }

    public function recieve_invoice($request, $invoice_id)
    {
        $customer = $this->currentCustomer();
        $invoice = $this->customerRepositoryInterface->find_customer_invoice($customer->id, $invoice_id);

        if (!$invoice) {
            return ['error' => 'invoice not found'];
        }

        return $this->invoiceToArray($invoice->fresh(['orderList.Items.product', 'payments']));
    }

    public function show_installations_services_status()
    {
        $customer = $this->currentCustomer();

        return $this->customerRepositoryInterface->show_installations_services_status($customer->id);
    }

    public function pay_for_additional_consumables($request, $installation_id)
    {
        $customer = $this->currentCustomer();
        $task = $this->customerRepositoryInterface->find_project_task($installation_id);

        if (!$task) {
            return ['error' => 'installation task not found'];
        }

        $amount = (float) $request->input('amount', $task->client_additional_cost_amount ?: $task->client_additional_entitlement_amount);
        if ($amount <= 0) {
            return ['error' => 'payment amount must be greater than zero'];
        }

        $payment = $this->customerRepositoryInterface->create_payment([
            'payable_type' => Customer::class,
            'payable_id' => $customer->id,
            'target_table_type' => Solar_company::class,
            'target_table_id' => $task->company_id,
            'payment_object_table_type' => Project_task::class,
            'payment_object_table_id' => $task->id,
            'payment_object_type_name' => 'service',
            'amount' => $amount,
            'currency' => $request->input('currency', 'SY'),
            'paid_at' => now(),
            'status' => $request->input('payment_status', 'paid'),
            're_subscribed' => false,
        ]);

        $taskData = ['payment_received' => true];
        if (empty($task->client_additional_cost_amount)) {
            $taskData['client_additional_cost_amount'] = $amount;
        }
        $task = $this->customerRepositoryInterface->update_project_task($task, $taskData);

        return ['payment' => $payment, 'task' => $task->fresh()];
    }

    public function technical_employee_rating($request, $installation_id)
    {
        $customer = $this->currentCustomer();
        $task = $this->customerRepositoryInterface->find_project_task($installation_id);

        if (!$task) {
            return ['error' => 'installation task not found'];
        }

        $rating = $this->customerRepositoryInterface->upsert_customer_rate_feedback($customer->id, $task->id, [
            'rate' => (float) $request->input('rate', 0),
            'feedback' => $request->input('feedback'),
        ]);

        return $rating->fresh();
    }

    public function task_feedsback($request, $task_id)
    {
        return $this->technical_employee_rating($request, $task_id);
    }

    public function company_feedsback($request, $company_id)
    {
        $customer = $this->currentCustomer();
        $adminId = $this->adminIdForCustomerReports();

        if (!$adminId) {
            return ['error' => 'system admin is not configured'];
        }

        $report = $this->customerRepositoryInterface->create_report([
            'customer_id' => $customer->id,
            'company_id' => $company_id,
            'admin_id' => $adminId,
            'report_type' => $request->input('report_type', 'Service_Complaint'),
            'report_subject' => $request->input('subject', 'Company feedback'),
            'report_content' => $request->input('feedback', $request->input('report_content')),
        ]);

        return $this->reportToArray($report);
    }

    public function company_rating($request, $company_id)
    {
        $rating = $request->input('rate', $request->input('rating', 0));
        $feedback = $request->input('feedback', $request->input('comment'));
        $request->merge([
            'subject' => 'Company rating: ' . $rating . '/5',
            'feedback' => $feedback,
        ]);

        return $this->company_feedsback($request, $company_id);
    }

    public function show_company_gallary($company_id)
    {
        return $this
            ->customerRepositoryInterface
            ->show_company_gallary($company_id)
            ->map(function (Company_protofolio $portfolio) {
                return $this->portfolioToArray($portfolio);
            });
    }

    public function request_products_order($request, $company_id)
    {
        $customer = $this->currentCustomer();
        $products = collect($request->input('products', []));

        if ($products->isEmpty()) {
            return ['error' => 'products are required'];
        }

        $productIds = $products->pluck('id')->filter()->values()->all();
        $productsMap = $this->customerRepositoryInterface->find_products_by_ids($productIds);

        if ($productsMap->isEmpty()) {
            return ['error' => 'products not found'];
        }

        $orderList = $this->customerRepositoryInterface->create_customer_order_list([
            'request_entity_type' => Customer::class,
            'request_entity_id' => $customer->id,
            'orderable_entity_type' => Solar_company::class,
            'orderable_entity_id' => $company_id,
            'customer_first_name' => $customer->first_name,
            'customer_last_name' => $customer->last_name,
            'status' => 'pending',
            'with_delivery' => $request->boolean('with_delivery', false),
            'request_datetime' => now(),
        ]);

        foreach ($products as $item) {
            $product = $productsMap->get($item['id']);
            if (!$product) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($product->price ?? 0);
            if (($product->currency ?? 'SY') === 'USD') {
                $unitPrice *= 1.35;
            } else {
                $unitPrice /= 100;
            }

            $lineSubtotal = $unitPrice * $quantity;
            $discountType = $product->disscount_type ?? null;
            $discountValue = (float) ($product->disscount_value ?? 0);
            $lineDiscount = $discountType === 'percentage'
                ? ($discountValue / 100) * $lineSubtotal
                : $discountValue * $quantity;

            $this->customerRepositoryInterface->add_order_list_item($orderList, [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'item_name_snapshot' => $product->product_name ?? null,
                'unit_price' => $unitPrice,
                'total_price' => max($lineSubtotal - $lineDiscount, 0),
                'unit_discount_amount' => $discountValue,
                'total_discount_amount' => $lineDiscount,
                'discount_type' => $discountType,
                'currency' => $product->currency ?? 'SY',
            ]);
        }

        $subTotal = $orderList->Items()->get()->sum(function ($item) {
            return (float) $item->unit_price * (int) $item->quantity;
        });
        $discount = $orderList->Items()->get()->sum('total_discount_amount');
        $total = max($subTotal - $discount, 0);
        $this->customerRepositoryInterface->update_order_list_totals($orderList, $subTotal, $discount, $total);

        return $this->customerRepositoryInterface->refresh_order_list($orderList);
    }

    public function show_requested_products_orders()
    {
        $customer = $this->currentCustomer();

        return $this->customerRepositoryInterface->show_customer_product_orders($customer->id);
    }

    public function filter_company_products($company_id, $filters)
    {
        return $this->customerRepositoryInterface->filter_company_products($company_id, $filters)->map(function (Products $product) {
            $imageUrl = $product->product_image ? asset('storage/' . $product->product_image) : null;
            $productData = [
                'product' => $product,
                'product_image' => $imageUrl,
            ];

            if ($product->product_type === 'battery') {
                $productData['technical_details'] = $product->batteries;
            } elseif ($product->product_type === 'inverter') {
                $productData['technical_details'] = $product->inverters;
            } elseif ($product->product_type === 'solar_panel' ) {
                $productData['technical_details'] = $product->solarPanals;
            }

            return $productData;
        });
    }

    public function request_maintenance_service($request)
    {
        $customer = $this->currentCustomer();
        $payload = $request->only([
            'company_id',
            'metainence_type',
            'issue_category',
            'priority',
            'issue_description',
            'system_sn',
            'warranty_number',
            'estimated_cost',
            'problem_name',
            'problem_cause',
            'payment_method',
            'currency',
        ]);

        $payload['customer_id'] = $customer->id;
        $payload['customer_name'] = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $payload['customer_phone'] = $customer->phoneNumber;
        $payload['metainence_type'] = $payload['metainence_type'] ?? 'preventive';
        $payload['issue_category'] = $payload['issue_category'] ?? 'other';
        $payload['priority'] = $payload['priority'] ?? 'medium';
        $payload['manager_approval'] = false;
        $payload['metainence_status'] = 'pending';
        $payload['is_paid'] = false;
        $payload['payment_method'] = $payload['payment_method'] ?? 'cash';
        $payload['currency'] = $payload['currency'] ?? 'SY';

        if ($request->hasFile('image_state')) {
            $imageState = $request->file('image_state')->getClientOriginalName();
            $payload['image_state'] = $request->file('image_state')->storeAs('Customer/metainence_requests', $imageState, 'public');
        }

        $maintenanceRequest = $this->customerRepositoryInterface->create_maintenance_request($payload);

        return $this->maintenanceRequestToArray($maintenanceRequest->fresh());
    }

    public function show_my_maintenance_requests()
    {
        $customer = $this->currentCustomer();

        return $this
            ->customerRepositoryInterface
            ->show_customer_maintenance_requests($customer->id)
            ->map(function (Metainence_request $maintenanceRequest) {
                return $this->maintenanceRequestToArray($maintenanceRequest);
            });
    }

    public function cancel_maintenance_request($request, $request_id)
    {
        $customer = $this->currentCustomer();
        $maintenanceRequest = $this->customerRepositoryInterface->find_maintenance_request($customer->id, $request_id);

        if (!$maintenanceRequest) {
            return ['error' => 'maintenance request not found'];
        }

        if ($maintenanceRequest->metainence_status !== 'pending') {
            return ['error' => 'only pending maintenance requests can be cancelled'];
        }

        $maintenanceRequest = $this->customerRepositoryInterface->update_maintenance_request($maintenanceRequest, ['metainence_status' => 'cancelled']);

        return $this->maintenanceRequestToArray($maintenanceRequest->fresh());
    }

    public function update_maintenance_request($request, $request_id)
    {
        $customer = $this->currentCustomer();
        $maintenanceRequest = $this->customerRepositoryInterface->find_maintenance_request($customer->id, $request_id);

        if (!$maintenanceRequest) {
            return ['error' => 'maintenance request not found'];
        }

        if ($maintenanceRequest->metainence_status !== 'pending') {
            return ['error' => 'only pending maintenance requests can be updated'];
        }

        $payload = $request->only([
            'company_id',
            'metainence_type',
            'issue_category',
            'priority',
            'issue_description',
            'system_sn',
            'warranty_number',
            'estimated_cost',
            'problem_name',
            'problem_cause',
            'payment_method',
            'currency',
        ]);

        if ($request->hasFile('image_state')) {
            $imageState = $request->file('image_state')->getClientOriginalName();
            $payload['image_state'] = $request->file('image_state')->storeAs('Customer/metainence_requests', $imageState, 'public');
        }

        $maintenanceRequest = $this->customerRepositoryInterface->update_maintenance_request($maintenanceRequest, $payload);

        return $this->maintenanceRequestToArray($maintenanceRequest->fresh());
    }

    public function recieve_maintenance_service($request, $request_id)
    {
        $customer = $this->currentCustomer();
        $maintenanceRequest = $this->customerRepositoryInterface->find_maintenance_request($customer->id, $request_id);

        if (!$maintenanceRequest) {
            return ['error' => 'maintenance request not found'];
        }

        $maintenanceRequest = $this->customerRepositoryInterface->update_maintenance_request($maintenanceRequest, ['metainence_status' => 'completed']);

        return $this->maintenanceRequestToArray($maintenanceRequest->fresh());
    }

    public function simulation_solar_system_finacial_savings($request)
    {
        $systemCost = (float) $request->input('system_cost', 0);
        $currentMonthlyCost = (float) $request->input('current_monthly_cost', 0);
        $monthlyGeneration = (float) $request->input('monthly_generation_kwh', 0);
        $valuePerKwh = (float) $request->input('value_per_kwh', 0);
        $monthlySavings = (float) $request->input('monthly_savings', 0);

        if ($monthlySavings <= 0 && $monthlyGeneration > 0 && $valuePerKwh > 0) {
            $monthlySavings = max($currentMonthlyCost - ($monthlyGeneration * $valuePerKwh), 0);
        }

        $paybackMonths = $monthlySavings > 0 ? round($systemCost / $monthlySavings, 2) : null;
        $yearlySavings = $monthlySavings * 12;

        return [
            'system_cost' => $systemCost,
            'current_monthly_cost' => $currentMonthlyCost,
            'monthly_generation_kwh' => $monthlyGeneration,
            'value_per_kwh' => $valuePerKwh,
            'monthly_savings' => $monthlySavings,
            'yearly_savings' => $yearlySavings,
            'payback_months' => $paybackMonths,
        ];
    }

    public function company_report($request, $company_id)
    {
        $customer = $this->currentCustomer();
        $adminId = $this->adminIdForCustomerReports();

        if (!$adminId) {
            return ['error' => 'system admin is not configured'];
        }

        $report = $this->customerRepositoryInterface->create_report([
            'customer_id' => $customer->id,
            'company_id' => $company_id,
            'admin_id' => $adminId,
            'report_type' => $request->input('report_type', 'Service_Complaint'),
            'report_subject' => $request->input('report_subject', 'Company report'),
            'report_content' => $request->input('report_content', $request->input('message')),
        ]);

        return $this->reportToArray($report);
    }
}
