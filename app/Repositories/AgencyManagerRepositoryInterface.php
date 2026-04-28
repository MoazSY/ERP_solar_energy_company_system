<?php
namespace App\Repositories;

interface AgencyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data);
    public function Agency_register($request, $data, $agency_manager, $agency_logo);
    public function agency_address($request, $agency);
    public function agency_manager_profile($manager_id);
    public function show_custom_subscriptions($user);
    public function subscribe_in_policy($request, $agency, $paymentData = null);
    public function add_agency_products($request, $agency);
    public function show_agency_products($manager);
    public function update_agency_product($request, $data, $product_id);
    public function delete_agency_product($product_id);
    public function delete_agency_product_details($product_id);
    public function add_agency_product_battery($request, $product_id);
    public function add_agency_product_inverter($request, $product_id);
    public function add_agency_product_solar_panel($request, $product_id);
    public function filter_agency_products($filters);
    public function filter_solar_companies($filters);
    public function create_custom_discount($data, $solar_company_id);
    public function show_custom_discounts($solar_company_id);
    public function update_custom_discount($discount_id, $data);
    public function delete_custom_discount($discount_id);
    public function get_all_custom_discounts_grouped_by_company();
    public function get_purchase_requests_from_companies($manager);
    public function create_purchase_invoice($request, $agency, $orderList);
    public function delivery_rules($request, $agency);
    public function show_delivery_rules($agency);
    public function update_delivery_rule($agency, $rule_id, $data);
    public function delete_delivery_rule($agency, $rule_id);
    public function assign_delivery_task($request, $agency, $orderList);
    public function show_delivery_tasks($agency);
    public function filter_delivery_tasks($agency, $filters);
    public function paid_to_driver($request,$task,$agency,$paymentResponse=null);
}
