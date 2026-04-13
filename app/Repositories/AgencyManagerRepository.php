<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Agency_manager;
use App\Models\Subscribe_polices;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AgencyManagerRepository implements AgencyManagerRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data)
    {
        $agency_manager = Agency_manager::create([
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
        return $agency_manager;
    }

    public function agency_manager_profile($manager_id)
    {
        $agency_manager = Agency_manager::findOrFail($manager_id);
        $agency = $agency_manager->agencies;
        return [$agency_manager, $agency];
    }

    public function Agency_register($request, $data, $agency_manager, $agency_logo)
    {
        $agency_manager->agencies()->create([
            'agency_manager_id' => $agency_manager->id,
            'agency_name' => $request->agency_name,
            'agency_logo' => $agency_logo,
            'commerical_register_number' => $request->commerical_register_number,
            'agency_description' => $request->agency_description,
            'agency_email' => $data['agency_email'],
            'agency_phone' => $data['agency_phone'],
            'tax_number' => $request->tax_number,
            // 'agency_status',
            // 'verified_at',
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
        ]);
        return $agency_manager;
    }

    public function agency_address($request, $agency)
    {
        $agency = Agency::findOrFail($agency->id);
        $agency_address = $agency->addresses()->create([
            'governorate_id' => $request->governorate_id,
            'area_id' => $request->area_id,
            'neighborhood_id' => $request->neighborhood_id,
            'address_description' => $request->address_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return $agency_address;
    }

    public function subscribe_in_policy($request, $agency)
    {
        $subscribe_policy = Subscribe_polices::findOrFail($request->subscribe_policy_id);
        $payment = $agency->paymentsMade()->create([
            'amount' => $subscribe_policy->price,
            'currency' => $subscribe_policy->currency,
            'payment_object_type_name' => 'subscribe_policy',
            'target_table_type'=>'App\Models\System_admin',
            'target_table_id'=>1,
            'payment_object_table_type'=>'App\Models\Subscribe_polices',
            'payment_object_table_id'=>$subscribe_policy->id,
            'paid_at'=>Carbon::now(),
            're_subscribed'=>$request->re_subscribed,
        ]);

        // هنا يجب ضمان الدفع اولا لكن حاليا لا يوجد دفع
        $subscribe = $agency->companyAgencySubscribes()->create([
            'subscribe_policy_id' => $request->subscribe_policy_id,
            'is_active' => true,
        ]);
        $custom_subscribe = $agency
            ->customSubscribes()
            ->where('subscribe_policy_id', $request->subscribe_policy_id)
            ->where('is_active', true)
            ->first();
        if ($custom_subscribe) {
            $custom_subscribe->entity_subscribe = true;
            $custom_subscribe->save();
        }
        return [$subscribe,$payment];
    }
    public function add_agency_products($request, $agency)
    {
        $product = $agency->products()->create([
            'product_name' => $request->product_name,
            'product_type' => $request->product_type,
            'product_brand' => $request->product_brand,
            'model_number' => $request->model_number,
            'quentity' => $request->quentity,
            'price' => $request->price,
            'disscount_type' => $request->disscount_type,
            'disscount_value' => $request->disscount_value,
            'currency' => $request->currency,
            'manufacture_date' => $request->manufacture_date,
            'product_image' => $request->product_image,
        ]);
        return $product;
}
}
