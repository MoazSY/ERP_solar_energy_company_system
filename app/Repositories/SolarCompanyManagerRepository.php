<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Company_agency_employee;
use App\Models\Deliveries;
use App\Models\Employee;
use App\Models\Order_list;
use App\Models\Payment_transactions;
use App\Models\Products;
use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use App\Models\Subscribe_polices;
use App\Services\OsrmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class SolarCompanyManagerRepository implements SolarCompanyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data)
    {
        $solar_Company_manager = Solar_company_manager::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'],
            'account_number' => $request->account_number,
            'syriatel_cash_phone' => $request->syriatel_cash_phone,
            'image' => $image_path,
            'identification_image' => $identification_image_path,
            'about_him' => $request->about_him,
            'Activate_Account' => false
        ]);
        return $solar_Company_manager;
    }

    public function company_manager_profile($manager_id)
    {
        $solar_manager = Solar_company_manager::findOrFail($manager_id);
        $solar_company = $solar_manager->solarCompanies;
        return [$solar_manager, $solar_company];
    }

    public function Company_register($request, $data, $Company_manager, $company_logo)
    {
        $company = $Company_manager->solarCompanies()->create([
            'solar_company_manager_id' => $Company_manager->id,
            'company_name' => $request->company_name,
            'company_logo' => $company_logo,
            'commerical_register_number' => $request->commerical_register_number,
            'company_description' => $request->company_description,
            'company_email' => $data['company_email'],
            'company_phone' => $data['company_phone'],
            'tax_number' => $request->tax_number,
            // 'company_status',
            // 'verified_at',
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
        ]);
        return $company;
    }

    public function company_address($request, $solarCompany)
    {
        $solarCompany = Solar_company::findOrFail($solarCompany->id);
        $company_address = $solarCompany->addresses()->create([
            'governorate_id' => $request->governorate_id,
            'area_id' => $request->area_id,
            'neighborhood_id' => $request->neighborhood_id,
            'address_description' => $request->address_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return $company_address;
    }

    public function show_custom_subscriptions($user)
    {
        $company = $user->solarCompanies()->first();
        $custom_subscribtions = $company?->customSubscribes()->with('subscribePolicy.admin')->get();
        return $custom_subscribtions;
    }

    public function subscribe_in_policy($request, $company, $paymentData = null)
    {
        $subscribe_policy = Subscribe_polices::findOrFail($request->subscribe_policy_id);
        if ($subscribe_policy->apply_to != 'company' || $subscribe_policy->is_active != true) {
            return null;
        }

        return DB::transaction(function () use ($company, $request, $paymentData, $subscribe_policy) {
            $payment = $company->paymentsMade()->create([
                'amount' => $subscribe_policy->subscription_fee,
                'currency' => $subscribe_policy->currency,
                'payment_object_type_name' => 'subscribe_policy',
                'target_table_type' => 'App\Models\System_admin',
                'target_table_id' => 1,
                'payment_object_table_type' => 'App\Models\Subscribe_polices',
                'payment_object_table_id' => $subscribe_policy->id,
                'paid_at' => Carbon::now(),
                're_subscribed' => $request->re_subscribed,
                'status' => $paymentData ? 'paid' : 'pending',
            ]);

            if ($paymentData && isset($paymentData['data'])) {
                Payment_transactions::create([
                    'payment_id' => $payment->id,
                    'gateway' => $request->payment_method,
                    'external_id' => $paymentData['data']['transaction_no'] ?? $paymentData['data']['billcode'] ?? null,
                    'payment_url' => $paymentData['data']['payment_url'] ?? null,
                    'status' => 'paid',
                    'response' => $paymentData,
                ]);
            }

            $subscribe = $company->companyAgencySubscribes()->create([
                'subscribe_policy_id' => $request->subscribe_policy_id,
                'is_active' => true,
            ]);
            $custom_subscribe = $company
                ->customSubscribes()
                ->where('subscribe_policy_id', $request->subscribe_policy_id)
                ->where('is_active', true)
                ->first();
            if ($custom_subscribe) {
                $custom_subscribe->entity_subscribe = true;
                $custom_subscribe->save();
            }

            return [$subscribe, $payment];
        });
    }

    public function show_all_agency()
    {
        $agencies = Agency::all();
        return $agencies;
    }

    public function filter_agency($filters)
    {
        $query = Agency::query();

        // فلتر اسم الوكالة
        if (isset($filters['agency_name'])) {
            $query->where('agency_name', 'like', '%' . $filters['agency_name'] . '%');
        }

        // فلتر الموقع عبر العناوين
        if (isset($filters['governorate_id']) || isset($filters['area_id']) || isset($filters['neighborhood_id'])) {
            $query->whereHas('addresses', function ($addressQuery) use ($filters) {
                if (isset($filters['governorate_id'])) {
                    $addressQuery->where('governorate_id', $filters['governorate_id']);
                }
                if (isset($filters['area_id'])) {
                    $addressQuery->where('area_id', $filters['area_id']);
                }
                if (isset($filters['neighborhood_id'])) {
                    $addressQuery->where('neighborhood_id', $filters['neighborhood_id']);
                }
            });
        }
        // فلتر المنتجات
        if (isset($filters['product_type']) ||
                isset($filters['product_brand']) ||
                isset($filters['product_name']) ||
                isset($filters['model_number']) ||
                isset($filters['currency']) ||
                isset($filters['product_price_min']) ||
                isset($filters['product_price_max']) ||
                isset($filters['disscount_type']) ||
                isset($filters['disscount_value_min']) ||
                isset($filters['disscount_value_max'])) {
            $query->whereHas('products', function ($productQuery) use ($filters) {
                if (isset($filters['product_type'])) {
                    $productQuery->where('product_type', $filters['product_type']);
                }
                if (isset($filters['product_brand'])) {
                    $productQuery->where('product_brand', 'like', '%' . $filters['product_brand'] . '%');
                }
                if (isset($filters['product_name'])) {
                    $productQuery->where('product_name', 'like', '%' . $filters['product_name'] . '%');
                }
                if (isset($filters['model_number'])) {
                    $productQuery->where('model_number', 'like', '%' . $filters['model_number'] . '%');
                }
                if (isset($filters['currency'])) {
                    $productQuery->where('currency', $filters['currency']);
                }
                if (isset($filters['product_price_min'])) {
                    $productQuery->where('price', '>=', $filters['product_price_min']);
                }
                if (isset($filters['product_price_max'])) {
                    $productQuery->where('price', '<=', $filters['product_price_max']);
                }
                if (isset($filters['disscount_type'])) {
                    $productQuery->where('disscount_type', $filters['disscount_type']);
                }
                if (isset($filters['disscount_value_min'])) {
                    $productQuery->where('disscount_value', '>=', $filters['disscount_value_min']);
                }
                if (isset($filters['disscount_value_max'])) {
                    $productQuery->where('disscount_value', '<=', $filters['disscount_value_max']);
                }
            });
        }

        return $query->with(['addresses.governorate', 'addresses.area', 'addresses.neighborhood', 'products', 'agencyManager'])->get();
    }

    public function show_agency_products($agency_id)
    {
        $agency = Agency::findOrFail($agency_id);
        $products = $agency->products()->with(['inverters', 'batteries', 'solarPanals'])->get();
        return $products;
    }

    public function show_company_products($company)
    {
        return $company->products()->with(['inverters', 'batteries', 'solarPanals'])->latest('id')->get();
    }

    public function request_purchase_invoice_agency($agency_id, $request, $company, $paymentData = null, $paymentMethod = null, $paidAmount = null)
    {
        $agency = Agency::findOrFail($agency_id);

        return DB::transaction(function () use ($agency, $request, $company, $paymentData, $paymentMethod, $paidAmount) {
            $products = $request->products;
            $quantities = collect($products)->pluck('quantity', 'id')->toArray();
            $productIds = collect($products)->pluck('id')->toArray();

            $order_list = $company->Order_list()->create([
                'orderable_entity_type' => 'App\Models\Agency',
                'orderable_entity_id' => $agency->id,
                'status' => 'pending',
                'with_delivery' => $request->with_delivery ?? false,
                'request_datetime' => now()
            ]);

            foreach ($productIds as $productId) {
                $order_list->Items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantities[$productId],
                    'item_name_snapshot' => Products::find($productId)->product_name ?? null,
                    'unit_price' => Products::find($productId)->price ?? null,
                    'unit_discount_amount' => Products::find($productId)->disscount_value ?? null,
                    'discount_type' => Products::find($productId)->disscount_type ?? null,
                    'currency' => Products::find($productId)->currency ?? null,
                    'discount_type' => Products::find($productId)->disscount_type ?? null,
                ]);
            }

            $order_list->sub_total_amount = $order_list->Items->sum(function ($item) {
                return $item->unit_price * $item->quantity;
            });

            $order_list->total_discount_amount = $order_list->Items->sum(function ($item) {
                if ($item->discount_type === 'percentage') {
                    return ($item->unit_discount_amount / 100) * $item->unit_price * $item->quantity;
                }

                return $item->unit_discount_amount * $item->quantity;
            });

            $order_list->total_amount = max($order_list->sub_total_amount - $order_list->total_discount_amount, 0);
            $order_list->save();

            $transaction = null;
            if ($paymentData && isset($paymentData['data'])) {
                $payment = $company->paymentsMade()->create([
                    'amount' => $order_list->total_amount,
                    'currency' => 'SY',
                    'payment_object_type_name' => 'invoice',
                    'target_table_type' => 'App\Models\Agency',
                    'target_table_id' => $agency->id,
                    'payment_object_table_type' => 'App\Models\Order_list',
                    'payment_object_table_id' => $order_list->id,
                    'paid_at' => Carbon::now(),
                    'status' => $paymentData ? 'paid' : 'pending',
                ]);

                $transaction = Payment_transactions::create([
                    'payment_id' => $payment->id,
                    'gateway' => $paymentMethod,
                    'external_id' => $paymentData['data']['transaction_no'] ?? $paymentData['data']['billcode'] ?? null,
                    'payment_url' => $paymentData['data']['payment_url'] ?? null,
                    'status' => 'paid',
                    'response' => $paymentData,
                ]);
            }

            return [$order_list, $order_list->Items, $transaction];
        });
    }

    public function get_purchase_requests_from_agencies($company)
    {
        $osrmService = app(OsrmService::class);

        $orders = Order_list::query()
            ->where('request_entity_type', Solar_company::class)
            ->where('request_entity_id', $company->id)
            ->with([
                'orderableEntityType',
                'Items.product.inverters',
                'Items.product.batteries',
                'Items.product.solarPanals',
                'purchaseInvoices',
            ])
            ->latest('id')
            ->get();

        return $orders->map(function ($order) use ($company, $osrmService) {
            $latestInvoice = $order->purchaseInvoices ?? null;

            $order->invoice_due_date = $latestInvoice?->due_date ?? null;
            // $order->invoice_delivery_fee = $latestInvoice?->delivery_fee ?? null;

            $order->transport_delivery_fee = null;
            $order->transport_distance_km = null;
            $order->transport_duration_minutes = null;

            $order->transport_error = null;
            $order_delivery = $order->deliveries()->first()->delivery_status??null;
            $order->order_delivery=$order_delivery;
            if ($order->with_delivery) {
       
                $agency = $order->orderableEntityType;

                $products = $order->Items->map(function ($item) {
                    return [
                        'id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ];
                })->values();

                $productsMap = $order
                    ->Items
                    ->filter(function ($item) {
                        return $item->product !== null;
                    })
                    ->mapWithKeys(function ($item) {
                        return [$item->product_id => $item->product];
                    });

                if ($agency instanceof Agency) {
                    $deliveryPricing = $osrmService->calculateDeliveryFeeForPurchase($agency, $company, $products, $productsMap);

                    if (isset($deliveryPricing['error'])) {
                        $order->transport_error = $deliveryPricing['error'];
                    } else {
                        $order->transport_delivery_fee = $deliveryPricing['delivery_fee'] ?? null;
                        $order->transport_distance_km = $deliveryPricing['distance_km'] ?? null;
                        $order->transport_duration_minutes = $deliveryPricing['duration_minutes'] ?? null;
                    }
                } else {
                    $order->transport_error = 'Order agency is missing for delivery calculation';
                }
            }else{
            $company=$order->request_entity;
            if($company->Assign_delivery_tasks()->exists()){
                $assignedDelivery=true;
            }    
            $order->assigned_delivery=$assignedDelivery??false;
            }
            return [
                // 'order'=>$order->getAttributes(),
                'order' => $order->load('Items.product.inverters', 'Items.product.batteries', 'Items.product.solarPanals', 'purchaseInvoices'),
            ];
        });
    }

    public function delivery_rules($request, $company)
    {
        return $company->deliveryRules()->create([
            'rule_name' => $request['rule_name'],
            'governorate_id' => $request['governorate_id'],
            'area_id' => $request['area_id'] ?? null,
            'delivery_fee' => $request['delivery_fee'],
            'price_per_km' => $request['price_per_km'],
            'max_weight_kg' => $request['max_weight_kg'],
            'price_per_extra_kg' => $request['price_per_extra_kg'],
            'currency' => $request['currency'],
            'is_active' => true,
        ]);
    }

    public function show_delivery_rules($company)
    {
        return $company
            ->deliveryRules()
            ->with(['governorate', 'area'])
            ->latest('id')
            ->get();
    }

    public function update_delivery_rule($company, $rule_id, $data)
    {
        $rule = $company->deliveryRules()->find($rule_id);

        if (!$rule) {
            return null;
        }

        $rule->update($data);
        $rule->refresh();

        return $rule->load(['governorate', 'area']);
    }

    public function delete_delivery_rule($company, $rule_id)
    {
        $rule = $company->deliveryRules()->find($rule_id);

        if (!$rule) {
            return false;
        }
        $rule->delete();
        return true;
    }

    public function assign_delivery_task($request, $company, $orderList)
    {
        $agency = $orderList->orderableEntityType;
        $address = $agency?->addresses()->latest('id')->first();

        if (!$agency instanceof Agency) {
            return ['error' => 'Agency not found for this order list'];
        }

        if (!$address) {
            return ['error' => 'Agency address is missing for delivery assignment'];
        }
        $driver = Employee::findOrFail(Company_agency_employee::findOrFail($request->driver_id)->employee_id);
        if ($driver->employee_type != 'driver') {
            return ['error' => 'The assigned employee is not a driver'];
        }
        return DB::transaction(function () use ($driver, $orderList, $agency, $address, $company) {
            $deliveryFeeResult = app(OsrmService::class)->calculate_delivery_fee_for_order_list($agency, $orderList);
            if (isset($deliveryFeeResult['error'])) {
                return ['error' => $deliveryFeeResult['error']];
            }
            $delivery_fee = $deliveryFeeResult['delivery_fee'];

            $delivery_task = $company->Assign_delivery_tasks()->create([
                'deliverable_object_type' => get_class($orderList),
                'deliverable_object_id' => $orderList->id,
                'order_list_id' => $orderList->id,
                'delivery_fee' => $delivery_fee,
                'currency' => 'SY',
                'delivery_status' => 'pending',
                'address_id' => $address->id ?? null,
                'delivery_address' => $address->address_description ?? null,
                'governorate_id' => $address->governorate_id ?? null,
                'area_id' => $address->area_id ?? null,
                'contact_name' => $agency->agency_name ?? 'agency',
                'contact_phone' => $agency->agency_phone ?? null,
                'latitude' => $address->latitude ?? null,
                'longitude' => $address->longitude ?? null,
                'driver_id' => $driver->id ?? null,
                'scheduled_delivery_datetime' => $orderList->purchaseInvoices()->first()->due_date ?? null,
                'weight_kg' => $orderList
                    ->Items()
                    ->with(['product.inverters', 'product.batteries', 'product.solarPanals'])
                    ->get()
                    ->sum(function ($item) {
                        $unitWeight = $item->product?->inverters?->weight_kg
                            ?? $item->product?->batteries?->weight_kg
                            ?? $item->product?->solarPanals?->weight_kg
                            ?? 0;

                        return $unitWeight * ($item->quantity ?? 1);
                    }),
                'driver_approved_delivery_task' => 'pending',
            ]);

            return $delivery_task;
        });
    }

    public function recieve_orderList($request, $orderList, $company)
    {
        $inventory_manager = Employee::findOrFail(company_agency_employee::findOrFail($request->inventory_manager_id)->employee_id);
        if ($inventory_manager->employee_type != 'inventory_manager') {
            return ['error' => 'The assigned employee is not an inventory manager'];
        }

        $orderList->status = 'completed';
        $orderList->recieve_datetime = now();
        $orderList->inventory_manager_id = $request->inventory_manager_id;
        $orderList->save();
        if ($orderList->with_delivery) {
            $delivery = $orderList->deliveries()->latest('id')->first();
            if ($delivery) {
                $delivery->client_recieve_delivery = true;
                $delivery->save();
            }
        }
        $company->input_output_requests()->create([
            'request_type' => 'input',
            'inventory_manager_id' => $inventory_manager->id,
            'order_id' => $orderList->id,
            'notes' => $request->notes ?? null,
        ]);
        // notify inventory to enter the products in stock and update the inventory
        $result = $orderList->load('input_output_request');
        return $result;
    }

    public function show_delivery_task($company)
    {
        return $company
            ->Assign_delivery_tasks()
            ->with(['orderList.request_entity', 'driver.employee', 'address.governorate', 'address.area'])
            ->latest('id')
            ->get();
    }

    public function show_delivery_tasks($company)
    {
        return $company
            ->Assign_delivery_tasks()
            ->with(['orderList.orderable_entity', 'driver.employee', 'address.governorate', 'address.area'])
            ->latest('id')
            ->get();
    }

    public function filter_delivery_tasks($company, $filters)
    {
        $driverPaidBaseConstraint = function ($paymentQuery) {
            $paymentQuery
                ->where('status', 'paid')
                ->where('target_table_type', Company_agency_employee::class)
                ->where('payment_object_table_type', Deliveries::class);
        };

        $driverPaidConstraint = function ($paymentQuery) use ($driverPaidBaseConstraint) {
            $driverPaidBaseConstraint($paymentQuery);
            $paymentQuery->whereColumn('payments.target_table_id', 'deliveries.driver_id');
        };

        $query = $company
            ->Assign_delivery_tasks()
            ->with([
                'orderList.request_entity',
                'driver.employee',
                'address.governorate',
                'address.area',
                'driverPayments' => $driverPaidBaseConstraint,
            ]);

        // فلترة التاريخ
        $query->when(!empty($filters['date_from']), function ($q) use ($filters) {
            $q->whereDate('scheduled_delivery_datetime', '>=', $filters['date_from']);
        });
        $query->when(!empty($filters['date_to']), function ($q) use ($filters) {
            $q->whereDate('scheduled_delivery_datetime', '<=', $filters['date_to']);
        });

        // فلترة مكتمل / غير مكتمل
        if (array_key_exists('is_completed', $filters)) {
            if ((bool) $filters['is_completed']) {
                $query->where(function ($q) {
                    $q
                        ->where('delivery_status', 'delivered')
                        ->orWhereNotNull('delivered_at');
                });
            } else {
                $query
                    ->where('delivery_status', '!=', 'delivered')
                    ->whereNull('delivered_at');
            }
        }

        // فلترة حالة الدفع للسائق
        if (!empty($filters['driver_payment_status'])) {
            if ($filters['driver_payment_status'] === 'paid') {
                $query->whereHas('driverPayments', $driverPaidConstraint);
            } elseif ($filters['driver_payment_status'] === 'unpaid') {
                $query->where(function ($q) use ($driverPaidConstraint) {
                    $q
                        ->whereNull('driver_id')
                        ->orWhereDoesntHave('driverPayments', $driverPaidConstraint);
                });
            }
        }

        return $query->latest('id')->get()->map(function ($q) {
            return [
                'delivery_task' => $q->getAttributes(),
                'order_list' => $q->orderList?->toArray(),
                'request_entity' => $q->orderList?->request_entity?->toArray(),
                'driver' => $q->driver?->toArray(),
                'address' => $q->address?->toArray(),
                'governorate' => $q->address?->governorate?->toArray(),
                'area' => $q->address?->area?->toArray(),
                'driver_payments' => $q->driverPayments->map(function ($payment) {
                    return $payment->getAttributes();
                })->values()->all(),
                'delivery_status' => $q->delivery_status,
                'is_paid_to_driver' => $q->driverPayments->isNotEmpty(),
            ];
        });
    }
}
