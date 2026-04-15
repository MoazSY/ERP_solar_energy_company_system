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
    public function show_agency_products($manager);
    public function update_agency_product($request, $data, $product_id);
    public function delete_agency_product($product_id);
    public function delete_agency_product_details($product_id);
    public function add_agency_product_battery($request, $product_id);
    public function add_agency_product_inverter($request, $product_id);
    public function add_agency_product_solar_panel($request, $product_id);
}
