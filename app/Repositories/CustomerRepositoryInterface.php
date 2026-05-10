<?php
namespace App\Repositories;

interface CustomerRepositoryInterface
{
    public function Create($request, $image_path, $data);
    public function customer_profile($customer_id);
    public function findCustomerById($customer_id);
    public function updateCustomer($customer, array $data);
    public function show_company_offers($company_id, $customer_id);
    public function show_my_specific_offers($customer_id);
    public function findOfferById($offer_id);
    public function upsert_offer_subscription($offer_id, $customer_id, array $data);
    public function find_offer_subscription($offer_id, $customer_id);
    public function update_offer_subscription($subscription, array $data);
    public function show_subscribe_offers($customer_id);
    public function create_request_solar_system(array $data);
    public function create_customer_electrical_device_characteristic(array $data);
    public function find_customer_electrical_device_characteristic($request_solar_system_id);
    public function update_customer_electrical_device_characteristic($characteristic, array $data);
    public function find_all_electrical_devices();
    public function find_request_solar_system($customer_id, $request_id);
    public function update_request_solar_system($requestSolarSystem, array $data);
    public function delete_request_solar_system($requestSolarSystem);
    public function show_customer_solar_system_requests($customer_id);
    public function create_maintenance_request(array $data);
    public function find_maintenance_request($customer_id, $request_id);
    public function update_maintenance_request($maintenanceRequest, array $data);
    public function show_customer_maintenance_requests($customer_id);
    public function show_customer_solar_systems($customer_id);
    public function show_customer_product_orders($customer_id);
    public function create_customer_order_list(array $data);
    public function add_order_list_item($orderList, array $itemData);
    public function update_order_list_totals($orderList, $subTotal, $discount, $total);
    public function refresh_order_list($orderList);
    public function show_invoices_details($customer_id);
    public function find_customer_invoice($customer_id, $invoice_id);
    public function create_payment(array $data);
    public function update_invoice_payment_status($invoice, $status);
    public function find_project_task($task_id);
    public function update_project_task($task, array $data);
    public function upsert_customer_rate_feedback($customer_id, $task_id, array $data);
    public function show_installations_services_status($customer_id);
    public function first_admin_id();
    public function create_report(array $data);
    public function show_company_gallary($company_id);
    public function find_products_by_ids(array $ids);
    public function calculateDeliveryCost($customer_id, $company_id);
    public function create_technical_inspection_request(array $data);
    public function find_technical_inspection_request($customer_id, $request_id);
    public function show_customer_technical_inspections($customer_id);
}
