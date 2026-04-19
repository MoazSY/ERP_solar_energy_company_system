<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Areas;
use App\Models\Governorates;
use App\Models\Neighborhood;
use App\Models\Solar_company;
use App\Models\System_admin;
use Illuminate\Support\Facades\Hash;

class SystemAdminRepository implements SystemAdminRepositoryInterface
{
    public function Create($request, $imagepath, $data)
    {
        $admin = System_admin::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'],
            'account_number' => $request->account_number,
            'syriatel_cash_phone' => $request->syriatel_cash_phone,
            'image' => $imagepath,
            'about_him' => $request->about_him,
        ]);
        return $admin;
    }

    public function Admin_profile($admin_id)
    {
        $profile = System_admin::findOrFail($admin_id);
        return $profile;
    }

    public function add_governorates($request)
    {
        $governorates = Governorates::create([
            'name' => $request->name
        ]);
        return $governorates;
    }

    public function add_area($request, $governorate)
    {
        $governorate = Governorates::findOrFail($governorate->id);
        $governorate->areas()->createMany($request->areas);
        return $governorate->areas;
    }

    public function add_neighborhoods($request, $area)
    {
        $area = Areas::findOrFail($area->id);
        $area->neighborhoods()->createMany($request->neighborhoods);
        return $area->neighborhoods;
    }

    public function get_governorates()
    {
        $governorates = Governorates::all();
        return $governorates;
    }

    public function get_areas($governorate)
    {
        $areas = Areas::where('governorate_id', '=', $governorate->id)->get();
        return $areas;
    }

    public function get_neighborhoods($area)
    {
        $neighborhoods = Neighborhood::where('area_id', '=', $area->id)->get();
        return $neighborhoods;
    }

    public function unActive_company()
    {
        $UnActiveCompany = Solar_company::whereNot('company_status', 'active')->get();
        return $UnActiveCompany;
    }

    public function unActive_agency()
    {
        return Agency::whereNot('agency_status', 'active')->get();
    }

    public function show_all_company_registerd()
    {
        $companies = Solar_company::where('company_status', 'active')->get();
        return $companies;
    }

    public function show_all_agency_registerd()
    {
        $agencies = Agency::where('agency_status', 'active')->get();
        return $agencies;
    }

    public function proccess_company_register($request, $admin, $entity)
    {
        $proccess_result = $entity->proccess_register()->create([
            'admin_id' => $admin->id,
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason
        ]);
        if ($request->entity_type == 'solar_company') {
            if ($request->status == 'rejected') {
                $entity->company_status = 'inactive';
                $entity->save();
                $manager = $entity->solarCompanyManager;
                $manager->Activate_Account = false;
                $manager->save();
            } elseif ($request->status == 'approved') {
                $entity->company_status = 'active';
                $entity->save();
                $manager = $entity->solarCompanyManager;
                $manager->Activate_Account = true;
                $manager->save();
            }
        } else {
            if ($request->status == 'rejected') {
                $entity->agency_status = 'inactive';
                $entity->save();
                $manager = $entity->agencyManager;
                $manager->Activate_Account = false;
                $manager->save();
            } elseif ($request->status == 'approved') {
                $entity->agency_status = 'active';
                $entity->save();
                $manager = $entity->agencyManager;
                $manager->Activate_Account = true;
                $manager->save();
            }
        }
        return $proccess_result;
    }

    public function subscriptions_policy($request, $admin)
    {
        $subscription_policy = $admin->subscribePolices()->create([
            'name' => $request->name,
            'description' => $request->description,
            'apply_to' => $request->apply_to,
            'subscription_fee' => $request->subscription_fee,
            'currency' => $request->currency,
            'duration_value' => $request->duration_value,
            'duration_type' => $request->duration_type,
            'is_active' => $request->is_active,
            'is_trial_granted' => $request->is_trial_granted,
            // 'trial_duration_value' => $request->trial_duration_value,
            // 'trial_duration_type' => $request->trial_duration_type,
        ]);
        return $subscription_policy;
    }

    public function update_subscriptions_policy($request, $admin, $policy)
    {
        $policy->update($request);
        $policy->fresh();
        $policy->save();

        return $policy;
    }

    public function custom_subscribe_policy($request, $company)
    {
        $custom_subscribe = $company->customSubscribes()->create([
            'subscribe_policy_id' => $request->subscribe_policy_id,
            'is_active' => true
        ]);
        return ['custom_subscribe' => $custom_subscribe, 'entity' => $company];
    }
}
