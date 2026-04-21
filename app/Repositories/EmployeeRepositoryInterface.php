<?php
namespace App\Repositories;

interface EmployeeRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data);
    public function employee_profile($employee_id);
    public function request_employment_order($request, $employee);
    public function process_employment_order($request, $entity, $entityTypeClass);
    public function show_employee_employment_orders($employee);
    public function show_entity_employment_orders($entity);
}
