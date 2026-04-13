<?php
namespace App\Repositories;

interface AgencyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data);
    public function Agency_register($request, $data, $agency_manager, $agency_logo);
    public function agency_address($request, $agency);
    public function agency_manager_profile($manager_id);
    public function subscribe_in_policy($request, $agency);
    public function add_agency_products($request, $agency);
}
