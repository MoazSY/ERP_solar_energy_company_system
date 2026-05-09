<?php
namespace App\Repositories;

use App\Models\Company_protofolio;
use App\Models\Customer;
use App\Models\Customer_rate_feedback;
use App\Models\Metainence_request;
use App\Models\Offers;
use App\Models\Order_list;
use App\Models\Payment;
use App\Models\Products;
use App\Models\Project_task;
use App\Models\Project_warranties;
use App\Models\Purchase_invoice;
use App\Models\Report;
use App\Models\Request_solar_system;
use App\Models\Subscribe_offer;
use App\Models\System_admin;
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
        return Subscribe_offer::where('customer_id', $customer_id)->where('subscription_status', 'accepted')
            ->latest('id')
            ->get();
    }

    public function create_request_solar_system(array $data)
    {
        return Request_solar_system::create($data);
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
}
