<?php
namespace App\Repositories;
interface SystemAdminRepositoryInterface{
    public function Create($request,$imagepath,$data);    // data in a phone , email  unique
    public function Admin_profile($admin_id);
    public function add_governorates($request);
    public function add_area($request,$governorate);
    public function add_neighborhoods($request,$area);
    public function get_governorates();
    public function get_areas($governorate);
    public function get_neighborhoods($area);
    public function unActive_company();
    public function proccess_company_register($request,$admin,$entity);
}
