<?php
namespace App\Repositories;

interface EmployeeRepositoryInterface
{
    // public function Create($request, $image_path, $identification_image_path, $data);
    public function employee_profile($employee_id);
    // public function process_employment_order($request, $entity, $entityTypeClass);
    // public function show_employee_employment_orders($employee_id, $status = null);
    // public function show_entity_employment_orders($entityTypeClass, $entity_id, $status = null);
    public function create_internal_employee_request($request, $entity, $entityTypeClass, $data);
    public function register_employee_company_agency($request, $entity, $entityTypeClass);
    public function search_employees($filter);
    public function show_entity_employees($entity, $entityTypeClass);
    public function show_delivery_tasks($employee);
    public function proccess_delivery_task($request, $employee);
    public function show_orderList_for_inventory_manager($employee);
    public function show_output_orderList_for_inventory_manager($employee);
    public function proccess_input_output_order_request($data, $inputOutputRequest, $company, $employee);
    public function create_conflict_invoice(array $data, $invoice_id, $company, $employee);
    public function insert_product_to_stock($data, $company);
    public function add_inventory_product_battery($request, $product_id);
    public function add_inventory_product_inverter($request, $product_id);
    public function add_inventory_product_solar_panel($request, $product_id);
    public function update_inventory_product($request, $data, $product_id);
    public function delete_inventory_product($product_id);
    public function delete_inventory_product_details($product_id);
    public function filter_inventory_products($filters);
    public function show_inventory_products($inventory_manager);
    public function filter_installation_tasks($employee, array $filters);
    public function filter_delivery_tasks($employee, array $filters);
    public function proccess_installation_task($employee, $task_id, array $data);
    public function recieve_cash_from_customer($employee, $task_id);
    public function start_installation_task($employee, $task_id);
    public function define_solar_system_for_customer($employee, $task_id, array $data);
    public function installation_task_complete($employee, $task_id, array $data);
    public function filter_profits_from_installation_tasks($employee, array $filters);
    public function filter_profits_from_delivery_tasks($employee, array $filters);
    public function show_product_nearing_out_of_stock($company, int $threshold);
    public function define_system_attachments($employee, $task, array $items);
    public function register_consumable_material($employee, int $task_id, array $consumables);
    public function update_consumable_material($employee, int $task_id, array $consumables);
    public function extract_system_attachments($employee, int $task_id);
    public function filter_system_attachments(array $filters, $employee);
}
