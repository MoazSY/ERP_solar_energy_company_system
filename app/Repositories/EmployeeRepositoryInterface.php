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
}
