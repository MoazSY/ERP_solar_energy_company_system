<?php
namespace App\Repositories;

interface SolarCompanyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data);
    public function Company_register($request, $data, $company_manager, $company_logo);
    public function company_address($request, $solarCompany);
    public function company_manager_profile($manager_id);
    public function show_custom_subscriptions($user);
    public function subscribe_in_policy($request, $company, $paymentData = null);
    public function show_all_agency();
    public function filter_agency($filters);
    public function show_agency_products($agency_id);
    public function show_company_products($company);
    public function request_purchase_invoice_agency($agency_id, $request, $company, $paymentData = null, $paymentMethod = null, $paidAmount = null);
    public function create_invoice(array $invoiceData);
    public function get_purchase_requests_from_agencies($company);
    public function delivery_rules($request, $company);
    public function show_delivery_rules($company);
    public function update_delivery_rule($company, $rule_id, $data);
    public function delete_delivery_rule($company, $rule_id);
    public function recieve_orderList($request, $orderList, $company);
    public function extract_orderlist_request($request, $company, $invoice);
    public function assign_delivery_task($request, $company, $orderList);
    public function assign_delivery_task_for_invoice($request, $company, $invoice);
    public function assign_installation_task($request, $company, $invoice, $primaryTechnician, $assistantNames = []);
    public function show_delivery_task($company);
    public function show_delivery_tasks($company);
    public function filter_delivery_tasks($company, $filters);
    public function paid_to_employee($request, $task, $company, $amount, $paymentResponse = null);
    public function show_company_offers($company);
    public function show_subscribers_in_offer($offer_id, $company);
    public function show_all_subscriptions($company);
    public function update_company_offer($offer_id, $company, $data);
    public function delete_company_offer($offer_id, $company);
    public function show_customer_requests($company);
    public function show_technical_inspection_requests($company);
    public function show_mantainance_requests($company);
    public function show_public_customer_requests();
    public function show_conflict_agency_invoice($company);
    public function proccess_technical_inspection_request($request, $inspection_request, $company);
    public function filter_invoices($company, array $filters);
    public function filter_inner_sales($company, array $filters);
    public function create_warranty($company, array $data);
    public function show_ready_output_requests($company);
    public function filter_warranty($company, array $filters);
    public function update_invoice($company, $invoice_id, array $data);
    public function delete_invoice($company, $invoice_id);
    public function add_project_to_company_protofolio($company, array $data);
    public function proccess_mantainance_request($request, $mantainance_request, $company);
    public function delete_assign_task($company, $task_id);
    public function filter_installation_tasks($company, array $filters);
}
