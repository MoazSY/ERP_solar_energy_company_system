<?php
namespace App\Repositories;

use App\Models\Company_protofolio;
use App\Models\Customer;
use App\Models\Customer_electrical_device_characteristic;
use App\Models\Customer_rate_feedback;
use App\Models\Metainence_request;
use App\Models\Offers;
use App\Models\Order_list;
use App\Models\Payment;
use App\Models\Payment_transactions;
use App\Models\Products;
use App\Models\Project_task;
use App\Models\Project_warranties;
use App\Models\Purchase_invoice;
use App\Models\Report;
use App\Models\Request_solar_system;
use App\Models\Solar_company;
use App\Models\Subscribe_offer;
use App\Models\System_admin;
use App\Models\Technical_inspection_request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function Create($request, $image_path, $data)
    {
        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $data['email'] ?? $request->email ?? null,
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'] ?? $request->phoneNumber ?? null,
            'account_number' => $request->account_number ?? null,
            'syriatel_cash_phone' => $request->syriatel_cash_phone ?? null,
            'image' => $image_path,
            'about_him' => $request->about_him ?? null,
        ]);

        return $customer;
    }

    public function customer_profile($customer_id)
    {
        return Customer::findOrFail($customer_id);
    }

    public function findCustomerById($customer_id)
    {
        return Customer::findOrFail($customer_id);
    }

    public function updateCustomer($customer, array $data)
    {
        $customer->update($data);
        $customer->refresh();

        return $customer;
    }
    public function add_customer_address($request,$customer){
        $address=$customer->addresses()->create([
                        'governorate_id' => $request->governorate_id,
            'area_id' => $request->area_id,
            'neighborhood_id' => $request->neighborhood_id,
            'address_description' => $request->address_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return $address;
    }

    public function show_company_offers($company_id, $customer_id)
    {
        return Offers::where('company_id', $company_id)
            ->where('offer_available', true)
            ->where(function ($q) use ($customer_id) {
                $q->where('public_private', 'public');
                if ($customer_id) {
                    $q->orWhere('customer_id', $customer_id);
                }
            })
            ->where(function ($q) {
                $q
                    ->whereNull('offer_expired_date')
                    ->orWhereDate('offer_expired_date', '>=', now()->toDateString());
            })
            ->with(['Items', 'Items.product'])
            ->latest('id')
            ->get();
    }

    public function show_my_specific_offers($customer_id)
    {
        return Offers::where('customer_id', $customer_id)
            ->latest('id')
            ->get();
    }

    public function findOfferById($offer_id)
    {
        return Offers::find($offer_id);
    }

    public function upsert_offer_subscription($offer_id, $customer_id, array $data)
    {
        return Subscribe_offer::updateOrCreate(
            [
                'offer_id' => $offer_id,
                'customer_id' => $customer_id,
            ],
            $data
        );
    }

    public function find_offer_subscription($offer_id, $customer_id)
    {
        return Subscribe_offer::where('customer_id', $customer_id)
            ->where('offer_id', $offer_id)
            ->first();
    }

    public function update_offer_subscription($subscription, array $data)
    {
        $subscription->update($data);
        return $subscription->refresh();
    }

    public function show_subscribe_offers($customer_id)
    {
        return Subscribe_offer::where('customer_id', $customer_id)
            ->where('subscription_status', 'accepted')
            ->latest('id')
            ->get();
    }

    public function create_request_solar_system(array $data)
    {
        return Request_solar_system::create($data);
    }

    public function create_customer_electrical_device_characteristic(array $data)
    {
        return Customer_electrical_device_characteristic::create($data);
    }

    public function find_customer_electrical_device_characteristic($request_solar_system_id)
    {
        return Customer_electrical_device_characteristic::where('request_solar_system_id', $request_solar_system_id)
            ->first();
    }

    public function update_customer_electrical_device_characteristic($characteristic, array $data)
    {
        $characteristic->update($data);
        $characteristic->save();

        return $characteristic->refresh();
    }

    public function find_all_electrical_devices()
    {
        return \App\Models\Electrical_device::select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function find_request_solar_system($customer_id, $request_id)
    {
        return Request_solar_system::where('customer_id', $customer_id)->find($request_id);
    }

    public function update_request_solar_system($requestSolarSystem, array $data)
    {
        $requestSolarSystem->update($data);
        return $requestSolarSystem->refresh();
    }

    public function delete_request_solar_system($requestSolarSystem)
    {
        $elecricalDeviceCharacteristic = Customer_electrical_device_characteristic::where('request_solar_system_id', $requestSolarSystem->id)->first();
        if ($elecricalDeviceCharacteristic) {
            $elecricalDeviceCharacteristic->delete();
        }
        return $requestSolarSystem->delete();
    }

    public function show_customer_solar_system_requests($customer_id)
    {
        return Request_solar_system::where('customer_id', $customer_id)
            ->with('company')
            ->latest('id')
            ->get();
    }

    public function create_maintenance_request(array $data)
    {
        return Metainence_request::create($data);
    }

    public function find_maintenance_request($customer_id, $request_id)
    {
        return Metainence_request::where('customer_id', $customer_id)->find($request_id);
    }

    public function update_maintenance_request($maintenanceRequest, array $data)
    {
        $maintenanceRequest->update($data);
        return $maintenanceRequest->refresh();
    }

    public function show_customer_maintenance_requests($customer_id)
    {
        return Metainence_request::where('customer_id', $customer_id)
            ->with('company')
            ->latest('id')
            ->get();
    }

    public function show_customer_solar_systems($customer_id)
    {
        return Project_warranties::where('customer_id', $customer_id)
            ->with(['invoice.orderList.Items.product', 'company', 'componentWarranties'])
            ->latest('id')
            ->get();
    }

    public function show_customer_product_orders($customer_id)
    {
        return Order_list::where('request_entity_type', Customer::class)
            ->where('request_entity_id', $customer_id)
            ->with(['Items.product', 'orderable_entity'])
            ->latest('id')
            ->get();
    }

    public function create_customer_order_list(array $data)
    {
        return Order_list::create($data);
    }

    public function add_order_list_item($orderList, array $itemData)
    {
        return $orderList->Items()->create($itemData);
    }

    public function update_order_list_totals($orderList, $subTotal, $discount, $total)
    {
        $orderList->sub_total_amount = $subTotal;
        $orderList->total_discount_amount = $discount;
        $orderList->total_amount = $total;
        $orderList->save();

        return $orderList;
    }

    public function refresh_order_list($orderList)
    {
        return $orderList->fresh(['Items.product', 'orderable_entity']);
    }

    public function request_purchase_invoice_company($customer, $request, $company, $paymentData = null, $paymentMethod = null, $paidAmount = null)
    {
        return DB::transaction(function () use ($customer, $request, $company, $paymentData, $paymentMethod, $paidAmount) {
            $products = $request->products;
            $quantities = collect($products)->pluck('quantity', 'id')->toArray();
            $productIds = collect($products)->pluck('id')->toArray();

            $orderList = Order_list::create([
                'request_entity_type' => Customer::class,
                'request_entity_id' => $customer->id,
                'orderable_entity_type' => Solar_company::class,
                'orderable_entity_id' => $company->id,
                'customer_first_name' => $customer->first_name,
                'customer_last_name' => $customer->last_name,
                'status' => 'pending',
                'with_delivery' => $request->with_delivery ?? false,
                'request_datetime' => now(),
            ]);

            foreach ($productIds as $productId) {
                $product = Products::find($productId);
                if (!$product) {
                    continue;
                }

                $orderList->Items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantities[$productId],
                    'item_name_snapshot' => $product->product_name ?? null,
                    'unit_price' => $product->price ?? null,
                    'unit_discount_amount' => $product->disscount_value ?? null,
                    'discount_type' => $product->disscount_type ?? null,
                    'currency' => $product->currency ?? null,
                    'discount_type' => $product->disscount_type ?? null,
                ]);
            }

            $orderList->sub_total_amount = $orderList->Items->sum(function ($item) {
                return $item->unit_price * $item->quantity;
            });

            $orderList->total_discount_amount = $orderList->Items->sum(function ($item) {
                if ($item->discount_type === 'percentage') {
                    return ($item->unit_discount_amount / 100) * $item->unit_price * $item->quantity;
                }

                return $item->unit_discount_amount * $item->quantity;
            });

            $orderList->total_amount = max($orderList->sub_total_amount - $orderList->total_discount_amount, 0);
            $orderList->save();

            $transaction = null;

            if ($paymentData ) {
                $payment = $customer->paymentsMade()->create([
                    'amount' => $paidAmount ?? $orderList->total_amount,
                    'currency' => 'SY',
                    'payment_object_type_name' => 'invoice',
                    'target_table_type' => 'App\Models\Solar_company',
                    'target_table_id' => $company->id,
                    'payment_object_table_type' => 'App\Models\Order_list',
                    'payment_object_table_id' => $orderList->id,
                    'paid_at' => Carbon::now(),
                    'status' => $paymentData ? ($request->payment_method == 'cash' ? 'pending' : 'paid') : 'pending',

                ]);

                $transaction = Payment_transactions::create([
                    'payment_id' => $payment->id,
                    'gateway' => $paymentMethod,
                    'external_id' => $paymentData['data']['transaction_no'] ?? $paymentData['data']['billcode'] ?? null,
                    'payment_url' => $paymentData['data']['payment_url'] ?? null,
                    'status' => $payment->status,
                    'response' => $paymentData,
                ]);
            }

            return [$orderList, $orderList->Items, $transaction];
        });
    }

    public function show_invoices_details($customer_id)
    {
        return Purchase_invoice::where('buyer_entity_type', Customer::class)
            ->where('buyer_entity_id', $customer_id)
            ->with(['orderList.Items.product', 'payments', 'seller_entity', 'object_entity'])
            ->latest('id')
            ->get();
    }

    public function find_customer_invoice($customer_id, $invoice_id)
    {
        return Purchase_invoice::where('buyer_entity_type', Customer::class)
            ->where('buyer_entity_id', $customer_id)
            ->find($invoice_id);
    }

    public function create_payment(array $data)
    {
        return Payment::create($data);
    }

    public function update_invoice_payment_status($invoice, $status)
    {
        $invoice->payment_status = $status;
        $invoice->save();

        return $invoice;
    }

    public function find_project_task($task_id)
    {
        return Project_task::find($task_id);
    }

    public function update_project_task($task, array $data)
    {
        $task->update($data);
        return $task->refresh();
    }

    public function upsert_customer_rate_feedback($customer_id, $task_id, array $data)
    {
        return Customer_rate_feedback::updateOrCreate(
            [
                'customer_id' => $customer_id,
                'task_id' => $task_id,
            ],
            $data
        );
    }

    public function show_installations_services_status($customer_id)
    {
        return [
            'project_warranties' => Project_warranties::where('customer_id', $customer_id)
                ->with(['invoice', 'company', 'componentWarranties'])
                ->latest('id')
                ->get(),
            'maintenance_requests' => Metainence_request::where('customer_id', $customer_id)
                ->with('company')
                ->latest('id')
                ->get(),
            'solar_system_requests' => Request_solar_system::where('customer_id', $customer_id)
                ->with('company')
                ->latest('id')
                ->get(),
        ];
    }

    public function first_admin_id()
    {
        return System_admin::query()->value('id');
    }

    public function create_report(array $data)
    {
        return Report::create($data);
    }

    public function show_company_gallary($company_id)
    {
        return Company_protofolio::where('company_id', $company_id)
            ->with(['projectTask.customerRateFeedbacks', 'company'])
            ->latest('id')
            ->get();
    }

    public function find_products_by_ids(array $ids)
    {
        return Products::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function calculateDeliveryCost($customer_id, $company_id)
    {
        // Fallback method if OSRM calculation fails
        // Returns base delivery fee only
        $customer = Customer::find($customer_id);
        $company = \App\Models\Solar_company::find($company_id);

        if (!$customer || !$company) {
            return 0;
        }

        $customerAddress = \App\Models\Address::where('entity_type_type', Customer::class)
            ->where('entity_type_id', $customer_id)
            ->first();

        if (!$customerAddress) {
            return 0;
        }

        $deliveryRule = \App\Models\Delivery_rules::where('entity_type_type', \App\Models\Solar_company::class)
            ->where('entity_type_id', $company_id)
            ->where('governorate_id', $customerAddress->governorate_id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        return (float) ($deliveryRule->delivery_fee ?? 0);
    }

    public function create_technical_inspection_request(array $data)
    {
        return Technical_inspection_request::create($data);
    }

    public function find_technical_inspection_request($customer_id, $request_id)
    {
        return Technical_inspection_request::where('customer_id', $customer_id)
            ->where('id', $request_id)
            ->first();
    }

    public function show_customer_technical_inspections($customer_id)
    {
        return Technical_inspection_request::where('customer_id', $customer_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function filter_company_products($company_id, $filters)
    {
        $company = Solar_company::find($company_id);

        if (!$company) {
            return [];
        }

        $query = $company->products();

        // فلترة البيانات الأساسية للمنتج
        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['product_name'])) {
            $query->where('product_name', 'like', '%' . $filters['product_name'] . '%');
        }

        if (isset($filters['product_brand'])) {
            $query->where('product_brand', 'like', '%' . $filters['product_brand'] . '%');
        }

        if (isset($filters['model_number'])) {
            $query->where('model_number', 'like', '%' . $filters['model_number'] . '%');
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['quentity_min'])) {
            $query->where('quentity', '>=', $filters['quentity_min']);
        }

        if (isset($filters['quentity_max'])) {
            $query->where('quentity', '<=', $filters['quentity_max']);
        }

        // فلترة تفاصيل البطارية
        if (($filters['product_type'] ?? null) === 'battery') {
            if (isset($filters['battery_type']) ||
                    isset($filters['capacity_kwh']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['cycle_life_min']) ||
                    isset($filters['cycle_life_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['Amperage_Ah']) ||
                    isset($filters['celles_type']) ||
                    isset($filters['celles_name'])) {
                $query->whereHas('batteries', function ($batteryQuery) use ($filters) {
                    if (isset($filters['battery_type'])) {
                        $batteryQuery->where('battery_type', $filters['battery_type']);
                    }
                    if (isset($filters['capacity_kwh'])) {
                        $batteryQuery->where('capacity_kwh', $filters['capacity_kwh']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $batteryQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['cycle_life_min'])) {
                        $batteryQuery->where('cycle_life', '>=', $filters['cycle_life_min']);
                    }
                    if (isset($filters['cycle_life_max'])) {
                        $batteryQuery->where('cycle_life', '<=', $filters['cycle_life_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $batteryQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $batteryQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $batteryQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $batteryQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['Amperage_Ah'])) {
                        $batteryQuery->where('Amperage_Ah', $filters['Amperage_Ah']);
                    }
                    if (isset($filters['celles_type'])) {
                        $batteryQuery->where('celles_type', $filters['celles_type']);
                    }
                    if (isset($filters['celles_name'])) {
                        $batteryQuery->where('celles_name', 'like', '%' . $filters['celles_name'] . '%');
                    }
                });
            }
        }

        // فلترة تفاصيل المحول (Inverter)
        if (($filters['product_type'] ?? null) === 'inverter') {
            if (isset($filters['grid_type']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['grid_capacity_kw_min']) ||
                    isset($filters['grid_capacity_kw_max']) ||
                    isset($filters['solar_capacity_kw_min']) ||
                    isset($filters['solar_capacity_kw_max']) ||
                    isset($filters['inverter_open']) ||
                    isset($filters['voltage_open_min']) ||
                    isset($filters['voltage_open_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max'])) {
                $query->whereHas('inverters', function ($inverterQuery) use ($filters) {
                    if (isset($filters['grid_type'])) {
                        $inverterQuery->where('grid_type', $filters['grid_type']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $inverterQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['grid_capacity_kw_min'])) {
                        $inverterQuery->where('grid_capacity_kw', '>=', $filters['grid_capacity_kw_min']);
                    }
                    if (isset($filters['grid_capacity_kw_max'])) {
                        $inverterQuery->where('grid_capacity_kw', '<=', $filters['grid_capacity_kw_max']);
                    }
                    if (isset($filters['solar_capacity_kw_min'])) {
                        $inverterQuery->where('solar_capacity_kw', '>=', $filters['solar_capacity_kw_min']);
                    }
                    if (isset($filters['solar_capacity_kw_max'])) {
                        $inverterQuery->where('solar_capacity_kw', '<=', $filters['solar_capacity_kw_max']);
                    }
                    if (isset($filters['inverter_open'])) {
                        $inverterQuery->where('inverter_open', $filters['inverter_open']);
                    }
                    if (isset($filters['voltage_open_min'])) {
                        $inverterQuery->where('voltage_open', '>=', $filters['voltage_open_min']);
                    }
                    if (isset($filters['voltage_open_max'])) {
                        $inverterQuery->where('voltage_open', '<=', $filters['voltage_open_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $inverterQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $inverterQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $inverterQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $inverterQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                });
            }
        }

        // فلترة تفاصيل الألواح الشمسية
        if (($filters['product_type'] ?? null) === 'solar_panel') {
            if (isset($filters['capacity_kw']) ||
                    isset($filters['basbar_number_min']) ||
                    isset($filters['basbar_number_max']) ||
                    isset($filters['is_half_cell']) ||
                    isset($filters['is_bifacial']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['length_m_min']) ||
                    isset($filters['length_m_max']) ||
                    isset($filters['width_m_min']) ||
                    isset($filters['width_m_max'])) {
                $query->whereHas('solarPanals', function ($panelQuery) use ($filters) {
                    if (isset($filters['capacity_kw'])) {
                        $panelQuery->where('capacity_kw', $filters['capacity_kw']);
                    }
                    if (isset($filters['basbar_number_min'])) {
                        $panelQuery->where('basbar_number', '>=', $filters['basbar_number_min']);
                    }
                    if (isset($filters['basbar_number_max'])) {
                        $panelQuery->where('basbar_number', '<=', $filters['basbar_number_max']);
                    }
                    if (isset($filters['is_half_cell'])) {
                        $panelQuery->where('is_half_cell', $filters['is_half_cell']);
                    }
                    if (isset($filters['is_bifacial'])) {
                        $panelQuery->where('is_bifacial', $filters['is_bifacial']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $panelQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $panelQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $panelQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $panelQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['length_m_min'])) {
                        $panelQuery->where('length_m', '>=', $filters['length_m_min']);
                    }
                    if (isset($filters['length_m_max'])) {
                        $panelQuery->where('length_m', '<=', $filters['length_m_max']);
                    }
                    if (isset($filters['width_m_min'])) {
                        $panelQuery->where('width_m', '>=', $filters['width_m_min']);
                    }
                    if (isset($filters['width_m_max'])) {
                        $panelQuery->where('width_m', '<=', $filters['width_m_max']);
                    }
                });
            }
        }

        return $query->with(['batteries', 'inverters', 'solarPanals'])->get();
    }
}
