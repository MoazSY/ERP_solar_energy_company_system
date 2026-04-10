<?php
namespace App\Repositories;

use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use Illuminate\Support\Facades\Hash;

class SolarCompanyManagerRepository implements SolarCompanyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data)
    {
        $solar_Company_manager = Solar_company_manager::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'],
            'account_number' => $request->account_number,
            'image' => $image_path,
            'identification_image' => $identification_image_path,
            'about_him' => $request->about_him,
            'Activate_Account' => false
        ]);
        return $solar_Company_manager;
    }

    public function company_manager_profile($manager_id)
    {
        $solar_manager = Solar_company_manager::findOrFail($manager_id);
        $solar_company = $solar_manager->solarCompanies;
        return [$solar_manager, $solar_company];
    }

    public function Company_register($request, $data, $Company_manager, $company_logo)
    {
        $Company_manager->solarCompanies()->create([
            'solar_company_manager_id' => $Company_manager->id,
            'company_name' => $request->company_name,
            'company_logo' => $request->company_logo,
            'commerical_register_number' => $request->commerical_register_number,
            'company_description' => $request->company_description,
            'company_email' => $data['company_email'],
            'company_phone' => $data['company_phone'],
            'tax_number' => $request->tax_number,
            // 'company_status',
            // 'verified_at',
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
        ]);
        return $Company_manager;
    }

    public function company_address($request, $solarCompany)
    {
        $solarCompany = Solar_company::findOrFail($solarCompany->id);
        $company_address = $solarCompany->addresses()->create([
            'governorate_id' => $request->governorate_id,
            'area_id' => $request->area_id,
            'neighborhood_id' => $request->neighborhood_id,
            'address_description' => $request->address_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return $company_address;
    }

    public function subscribe_in_policy($request, $company)
    {
        // هنا يجب ضمان الدفع اولا لكن حاليا لا يوجد دفع 
        $subscribe = $company->companyAgencySubscribes()->create([
            'subscribe_policy_id' => $request->subscribe_policy_id,
            'is_active' => true,
        ]);
        $custom_subscribe = $company
            ->customSubscribes()
            ->where('subscribe_policy_id', $request->subscribe_policy_id)
            ->where('is_active', true)
            ->first();
        if ($custom_subscribe) {
            $custom_subscribe->entity_subscribe = true;
            $custom_subscribe->save();
        }
        return $subscribe;
    }
}
