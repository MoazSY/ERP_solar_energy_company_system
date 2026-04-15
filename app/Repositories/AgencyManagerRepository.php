<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Agency_manager;
use App\Models\Products;
use App\Models\Subscribe_polices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

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
        if ($subscribe_policy->apply_to != 'agency' || $subscribe_policy->is_active != true) {
            return null;
        }
        $payment = $agency->paymentsMade()->create([
            'amount' => $subscribe_policy->subscription_fee,
            'currency' => $subscribe_policy->currency,
            'payment_object_type_name' => 'subscribe_policy',
            'target_table_type' => 'App\Models\System_admin',
            'target_table_id' => 1,
            'payment_object_table_type' => 'App\Models\Subscribe_polices',
            'payment_object_table_id' => $subscribe_policy->id,
            'paid_at' => Carbon::now(),
            're_subscribed' => $request->re_subscribed,
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
        return [$subscribe, $payment];
    }

    public function add_agency_products($request, $agency)
    {
        $product = $agency->products()->create([
            'product_name' => $request['product_name'],
            'product_type' => $request['product_type'],
            'product_brand' => $request['product_brand'],
            'model_number' => $request['model_number'],
            'quentity' => $request['quentity'],
            'price' => $request['price'],
            'disscount_type' => $request['disscount_type'],
            'disscount_value' => $request['disscount_value'],
            'currency' => $request['currency'],
            'manufacture_date' => $request['manufacture_date'],
            'product_image' => $request['product_image'],
        ]);
        return $product;
    }

    public function add_agency_product_battery($request, $product_id)
    {

        $product = Products::findOrFail($product_id->id);
        if($product->product_type != 'battery'){
            return null;
        }
        $battery = $product->batteries()->create([
            'battery_type' => $request['battery_type'],
            'capacity_kwh' => $request['capacity_kwh'],
            'voltage_v' => $request['voltage_v'],
            'cycle_life' => $request['cycle_life'],
            'warranty_years' => $request['warranty_years'],
            'weight_kg' => $request['weight_kg'],
            'Amperage_Ah' => $request['Amperage_Ah'],
            'celles_type' => $request['celles_type'],
            'celles_name' => $request['celles_name'],
        ]);
        return $battery;
    }

    public function add_agency_product_inverter($request, $product_id)
    {
        $product = Products::findOrFail($product_id->id);
        if($product->product_type != 'inverter'){
            return null;
        }
        $inverter = $product->inverters()->create([
            'grid_type' => $request['grid_type'],
            'voltage_v' => $request['voltage_v'],
            'grid_capacity_kw' => $request['grid_capacity_kw'],
            'solar_capacity_kw' => $request['solar_capacity_kw'],
            'inverter_open' => $request['inverter_open'],
            'voltage_open' => $request['voltage_open'],
            'weight_kg' => $request['weight_kg'],
            'warranty_years' => $request['warranty_years'],
        ]);
        return $inverter;
    }

    public function add_agency_product_solar_panel($request, $product_id)
    {
        $product = Products::findOrFail($product_id->id);
        if($product->product_type != 'solar_panel'){
            return null;
        }
        $solar_panel = $product->solarPanals()->create([
            'capacity_kw' => $request['capacity_kw'],
            'basbar_number' => $request['basbar_number'],
            'is_half_cell' => $request['is_half_cell'],
            'is_bifacial' => $request['is_bifacial'],
            'warranty_years' => $request['warranty_years'],
            'weight_kg' => $request['weight_kg'],
            'length_m' => $request['length_m']  ,
            'width_m' => $request['width_m'],
        ]);
        return $solar_panel;
    }

    public function show_agency_products($manager)
    {
        $agency = $manager->agencies()->first();
        if (!$agency) {
            return null;
        }
        return $agency->products()->get();
    }

    public function update_agency_product($request, $data, $product_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return null;
        }

        $product = $agency->products()->find($product_id);

        if (!$product) {
            return null;
        }

        // Handle image upload if provided
        if ($request->hasFile('product_image')) {
            $imagePath = $request->file('product_image')->store('products', 'public');
            $data['product_image'] = $imagePath;
            $product_image_URL = asset('storage/' . $data['product_image']);
        }else{
            $product_image_URL = asset('storage/' . $product->product_image);
        }

        $product->update([
            'product_name' => $data['product_name'] ?? $product->product_name,
            'product_type' => $data['product_type'] ?? $product->product_type,
            'product_brand' => $data['product_brand'] ?? $product->product_brand,
            'model_number' => $data['model_number'] ?? $product->model_number,
            'quentity' => $data['quentity'] ?? $product->quentity,
            'price' => $data['price'] ?? $product->price,
            'disscount_type' => $data['disscount_type'] ?? $product->disscount_type,
            'disscount_value' => $data['disscount_value'] ?? $product->disscount_value,
            'currency' => $data['currency'] ?? $product->currency,
            'manufacture_date' => $data['manufacture_date'] ?? $product->manufacture_date,
        ]);

        $product->save();
        $product->refresh();  // Refresh the model to get the latest data

        if ($data['update_technical_details'] == true && $product->product_type == 'battery') {
            $battery = $product->batteries;
            $battery->update([
                'battery_type' => $data['battery_type'] ?? $battery->battery_type,
                'capacity_kwh' => $data['capacity_kwh'] ?? $battery->capacity_kwh,
                'voltage_v' => $data['voltage_v'] ?? $battery->voltage_v,
                'cycle_life' => $data['cycle_life'] ?? $battery->cycle_life,
                'warranty_years' => $data['warranty_years'] ?? $battery->warranty_years,
                'weight_kg' => $data['weight_kg'] ?? $battery->weight_kg,
                'Amperage_Ah' => $data['Amperage_Ah'] ?? $battery->Amperage_Ah,
                'celles_type' => $data['celles_type'] ?? $battery->celles_type,
                'celles_name' => $data['celles_name'] ?? $battery->celles_name,
            ]);
            $battery->save();
            $battery->refresh();
        }
        if ($data['update_technical_details'] == true && $product->product_type == 'inverter' ) {
            $inverter = $product->inverters;
            $inverter->update([
                'grid_type' => $data['grid_type'] ?? $inverter->grid_type,
                'voltage_v' => $data['voltage_v'] ?? $inverter->voltage_v,
                'grid_capacity_kw' => $data['grid_capacity_kw'] ?? $inverter->grid_capacity_kw,
                'solar_capacity_kw' => $data['solar_capacity_kw'] ?? $inverter->solar_capacity_kw,
                'inverter_open' => $data['inverter_open'] ?? $inverter->inverter_open,
                'voltage_open' => $data['voltage_open'] ?? $inverter->voltage_open,
                'weight_kg' => $data['weight_kg'] ?? $inverter->weight_kg,
                'warranty_years' => $data['warranty_years'] ?? $inverter->warranty_years,
            ]);
            $inverter->save();
            $inverter->refresh();
        }
        if ($data['update_technical_details'] == true && $product->product_type == 'solar_panel') {
            $solar_panel = $product->solarPanals;
            $solar_panel->update([
                'panel_type' => $data['panel_type'] ?? $solar_panel->panel_type,
                'capacity_kw' => $data['capacity_kw'] ?? $solar_panel->capacity_kw,
                'voltage_v' => $data['voltage_v'] ?? $solar_panel->voltage_v,
                'warranty_years' => $data['warranty_years'] ?? $solar_panel->warranty_years,
                'weight_kg' => $data['weight_kg'] ?? $solar_panel->weight_kg,
            ]);
            $solar_panel->save();
            $solar_panel->refresh();
        }

        // if ($data['product_image'] != null) {
        //     $product_image_URL = asset('storage/' . $data['product_image']);
        // }
        return [$product, $product_image_URL];
    }

    public function delete_agency_product($product_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return false;
        }

        $product = $agency->products()->find($product_id);

        if (!$product) {
            return false;
        }

        if ($product->product_type === 'battery') {
            $product->batteries()->delete();
        } elseif ($product->product_type === 'inverter') {
            $product->inverters()->delete();
        } elseif ($product->product_type === 'solar_panel') {
            $product->solarPanals()->delete();
        }

        $product->delete();
        return true;
    }

    public function delete_agency_product_details($product_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return false;
        }

        $product = $agency->products()->find($product_id);

        if (!$product) {
            return false;
        }

        if ($product->product_type === 'battery') {
            $product->batteries()->delete();
        } elseif ($product->product_type === 'inverter') {
            $product->inverters()->delete();
        } elseif ($product->product_type === 'solar_panel') {
            $product->solarPanals()->delete();
        }

        return true;
    }
}
