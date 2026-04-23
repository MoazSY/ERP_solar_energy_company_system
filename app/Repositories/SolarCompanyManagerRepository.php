<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Order_list;
use App\Models\Payment_transactions;
use App\Models\Products;
use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use App\Models\Subscribe_polices;
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
            'company_logo' => $request->company_logo,
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
    $company=$user->solarCompanies()->first();
    $custom_subscribtions = $company?->customSubscribes()->with('subscribePolicy')->get();
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

        return $query->with(['addresses.governorate', 'addresses.area', 'addresses.neighborhood', 'products'])->get();
    }

    public function show_agency_products($agency_id)
    {
        $agency = Agency::findOrFail($agency_id);
        $products = $agency->products()->with(['inverters', 'batteries', 'solarPanals'])->get();
        return $products;
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
                'request_datetime'=>now()
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

            $total_amount_sy = $order_list->total_amount * 12500;
            $transaction = null;
            if ($paymentData && isset($paymentData['data'])) {
                $payment = $company->paymentsMade()->create([
                    'amount' => $paidAmount ?? $order_list->total_amount,
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

            return [$order_list, $order_list->Items, $total_amount_sy, $transaction];
        });
    }

    public function get_purchase_requests_from_agencies($company)
    {
        $orders = Order_list::query()
            ->where('request_entity_type', Solar_company::class)
            ->where('request_entity_id', $company->id)
            ->with([
                'request_entity',
                'orderableEntityType',
                'Items.product.inverters',
                'Items.product.batteries',
                'Items.product.solarPanals',
                'purchaseInvoices',
            ])
            ->latest('id')
            ->get();

        return $orders->map(function ($order) {
            $latestInvoice = $order->purchaseInvoices->sortByDesc('id')->first();

            $order->invoice_due_date = $latestInvoice?->due_date;
            $order->invoice_delivery_fee = $latestInvoice?->delivery_fee;

            return $order;
        });
    }
}
