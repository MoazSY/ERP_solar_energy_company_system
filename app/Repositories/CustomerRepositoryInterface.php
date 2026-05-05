<?php
namespace App\Repositories;

interface CustomerRepositoryInterface
{
    public function Create($request, $image_path, $data);
    public function customer_profile($customer_id);
}
