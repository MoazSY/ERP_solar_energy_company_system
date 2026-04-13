<?php
namespace App\Repositories;
interface SolarCompanyManagerRepositoryInterface{
    public function Create($request,$image_path,$identification_image_path,$data);
    public function Company_register($request,$data,$company_manager,$company_logo);
    public function company_address($request,$solarCompany);
    public function company_manager_profile($manager_id);
    public function subscribe_in_policy($request,$company);
    public function show_all_agency();
    public function filter_agency($filters);
}
