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
        if ($product->product_type != 'battery') {
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
        if ($product->product_type != 'inverter') {
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
        if ($product->product_type != 'solar_panel') {
            return null;
        }
        $solar_panel = $product->solarPanals()->create([
            'capacity_kw' => $request['capacity_kw'],
            'basbar_number' => $request['basbar_number'],
            'is_half_cell' => $request['is_half_cell'],
            'is_bifacial' => $request['is_bifacial'],
            'warranty_years' => $request['warranty_years'],
            'weight_kg' => $request['weight_kg'],
            'length_m' => $request['length_m'],
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
        } else {
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
        if ($data['update_technical_details'] == true && $product->product_type == 'inverter') {
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

    public function filter_agency_products($filters)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return [];
        }

        $query = $agency->products();

        // فلترة البيانات الأساسية للمنتج
        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['product_name'])) {
            $query->where('product_name', 'like', '%' . $filters['product_name'] . '%');
        }

        if (isset($filters['product_brand'])) {
            $query->where('product_brand', 'like', '%' . $filters['product_brand'] . '%');
        }

        if (isset($filters['model_number'])) {
            $query->where('model_number', 'like', '%' . $filters['model_number'] . '%');
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['quentity_min'])) {
            $query->where('quentity', '>=', $filters['quentity_min']);
        }

        if (isset($filters['quentity_max'])) {
            $query->where('quentity', '<=', $filters['quentity_max']);
        }

        // فلترة تفاصيل البطارية
        if (($filters['product_type'] ?? null) === 'battery') {
            if (isset($filters['battery_type']) ||
                    isset($filters['capacity_kwh']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['cycle_life_min']) ||
                    isset($filters['cycle_life_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['Amperage_Ah']) ||
                    isset($filters['celles_type']) ||
                    isset($filters['celles_name'])) {
                $query->whereHas('batteries', function ($batteryQuery) use ($filters) {
                    if (isset($filters['battery_type'])) {
                        $batteryQuery->where('battery_type', $filters['battery_type']);
                    }
                    if (isset($filters['capacity_kwh'])) {
                        $batteryQuery->where('capacity_kwh', $filters['capacity_kwh']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $batteryQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['cycle_life_min'])) {
                        $batteryQuery->where('cycle_life', '>=', $filters['cycle_life_min']);
                    }
                    if (isset($filters['cycle_life_max'])) {
                        $batteryQuery->where('cycle_life', '<=', $filters['cycle_life_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $batteryQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $batteryQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $batteryQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $batteryQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['Amperage_Ah'])) {
                        $batteryQuery->where('Amperage_Ah', $filters['Amperage_Ah']);
                    }
                    if (isset($filters['celles_type'])) {
                        $batteryQuery->where('celles_type', $filters['celles_type']);
                    }
                    if (isset($filters['celles_name'])) {
                        $batteryQuery->where('celles_name', 'like', '%' . $filters['celles_name'] . '%');
                    }
                });
            }
        }

        // فلترة تفاصيل المحول (Inverter)
        if (($filters['product_type'] ?? null) === 'inverter') {
            if (isset($filters['grid_type']) ||
                    isset($filters['voltage_v']) ||
                    isset($filters['grid_capacity_kw_min']) ||
                    isset($filters['grid_capacity_kw_max']) ||
                    isset($filters['solar_capacity_kw_min']) ||
                    isset($filters['solar_capacity_kw_max']) ||
                    isset($filters['inverter_open']) ||
                    isset($filters['voltage_open_min']) ||
                    isset($filters['voltage_open_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max'])) {
                $query->whereHas('inverters', function ($inverterQuery) use ($filters) {
                    if (isset($filters['grid_type'])) {
                        $inverterQuery->where('grid_type', $filters['grid_type']);
                    }
                    if (isset($filters['voltage_v'])) {
                        $inverterQuery->where('voltage_v', $filters['voltage_v']);
                    }
                    if (isset($filters['grid_capacity_kw_min'])) {
                        $inverterQuery->where('grid_capacity_kw', '>=', $filters['grid_capacity_kw_min']);
                    }
                    if (isset($filters['grid_capacity_kw_max'])) {
                        $inverterQuery->where('grid_capacity_kw', '<=', $filters['grid_capacity_kw_max']);
                    }
                    if (isset($filters['solar_capacity_kw_min'])) {
                        $inverterQuery->where('solar_capacity_kw', '>=', $filters['solar_capacity_kw_min']);
                    }
                    if (isset($filters['solar_capacity_kw_max'])) {
                        $inverterQuery->where('solar_capacity_kw', '<=', $filters['solar_capacity_kw_max']);
                    }
                    if (isset($filters['inverter_open'])) {
                        $inverterQuery->where('inverter_open', $filters['inverter_open']);
                    }
                    if (isset($filters['voltage_open_min'])) {
                        $inverterQuery->where('voltage_open', '>=', $filters['voltage_open_min']);
                    }
                    if (isset($filters['voltage_open_max'])) {
                        $inverterQuery->where('voltage_open', '<=', $filters['voltage_open_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $inverterQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $inverterQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $inverterQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $inverterQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                });
            }
        }

        // فلترة تفاصيل الألواح الشمسية
        if (($filters['product_type'] ?? null) === 'solar_panel') {
            if (isset($filters['capacity_kw']) ||
                    isset($filters['basbar_number_min']) ||
                    isset($filters['basbar_number_max']) ||
                    isset($filters['is_half_cell']) ||
                    isset($filters['is_bifacial']) ||
                    isset($filters['warranty_years_min']) ||
                    isset($filters['warranty_years_max']) ||
                    isset($filters['weight_kg_min']) ||
                    isset($filters['weight_kg_max']) ||
                    isset($filters['length_m_min']) ||
                    isset($filters['length_m_max']) ||
                    isset($filters['width_m_min']) ||
                    isset($filters['width_m_max'])) {
                $query->whereHas('solarPanals', function ($panelQuery) use ($filters) {
                    if (isset($filters['capacity_kw'])) {
                        $panelQuery->where('capacity_kw', $filters['capacity_kw']);
                    }
                    if (isset($filters['basbar_number_min'])) {
                        $panelQuery->where('basbar_number', '>=', $filters['basbar_number_min']);
                    }
                    if (isset($filters['basbar_number_max'])) {
                        $panelQuery->where('basbar_number', '<=', $filters['basbar_number_max']);
                    }
                    if (isset($filters['is_half_cell'])) {
                        $panelQuery->where('is_half_cell', $filters['is_half_cell']);
                    }
                    if (isset($filters['is_bifacial'])) {
                        $panelQuery->where('is_bifacial', $filters['is_bifacial']);
                    }
                    if (isset($filters['warranty_years_min'])) {
                        $panelQuery->where('warranty_years', '>=', $filters['warranty_years_min']);
                    }
                    if (isset($filters['warranty_years_max'])) {
                        $panelQuery->where('warranty_years', '<=', $filters['warranty_years_max']);
                    }
                    if (isset($filters['weight_kg_min'])) {
                        $panelQuery->where('weight_kg', '>=', $filters['weight_kg_min']);
                    }
                    if (isset($filters['weight_kg_max'])) {
                        $panelQuery->where('weight_kg', '<=', $filters['weight_kg_max']);
                    }
                    if (isset($filters['length_m_min'])) {
                        $panelQuery->where('length_m', '>=', $filters['length_m_min']);
                    }
                    if (isset($filters['length_m_max'])) {
                        $panelQuery->where('length_m', '<=', $filters['length_m_max']);
                    }
                    if (isset($filters['width_m_min'])) {
                        $panelQuery->where('width_m', '>=', $filters['width_m_min']);
                    }
                    if (isset($filters['width_m_max'])) {
                        $panelQuery->where('width_m', '<=', $filters['width_m_max']);
                    }
                });
            }
        }

        return $query->with(['batteries', 'inverters', 'solarPanals'])->get();
    }
}
