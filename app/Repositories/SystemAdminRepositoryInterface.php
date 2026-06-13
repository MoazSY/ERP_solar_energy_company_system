<?php
namespace App\Repositories;

interface SystemAdminRepositoryInterface
{
    public function Create($request, $imagepath, $data);  // data in a phone , email  unique
    public function Admin_profile($admin_id);
    public function add_governorates($request);
    public function add_area($request, $governorate);
    public function add_neighborhoods($request, $area);
    public function get_governorates();
    public function get_areas($governorate);
    public function get_neighborhoods($area);
    public function unActive_company();
    public function unActive_agency();
    public function show_all_company_registerd();
    public function show_all_agency_registerd();
    public function proccess_company_register($request, $admin, $entity);
    public function subscriptions_policy($request, $admin);
    public function update_subscriptions_policy($request, $admin, $policy);
    public function commision_policy($request, $admin);
    public function update_commision_policy($request, $admin, $policy);
    public function delete_commision_policy($policy);
    public function show_commision_policies($admin);
    public function filter_reports(array $filters);
    public function proccess_report(array $data, $admin, $report);
    public function custom_subscribe_policy($request, $company);
}
