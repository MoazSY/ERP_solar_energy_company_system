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
    public function request_purchase_invoice_agency($agency_id, $request, $company, $paymentData = null, $paymentMethod = null, $paidAmount = null);
    public function get_purchase_requests_from_agencies($company);
}
