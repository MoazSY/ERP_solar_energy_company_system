<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Agency_manager;
use App\Models\Order_list;
use App\Models\Payment_transactions;
use App\Models\Products;
use App\Models\Solar_company;
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
            'syriatel_cash_phone' => $request->syriatel_cash_phone,
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
    public function show_custom_subscriptions($user)
    {
    $agency=$user->agencies()->first();
    $custom_subscribtions = $agency?->customSubscribes()->with('subscribePolicy')->get();
    return $custom_subscribtions;
    }
    public function subscribe_in_policy($request, $agency, $paymentData = null)
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
            'status' => $paymentData ? 'paid' : 'pending',
        ]);

        if ($paymentData && isset($paymentData['data'])) {
            Payment_transactions::create([
                'payment_id' => $payment->id,
                'gateway' => $request->payment_method,
                'external_id' => $paymentData['data']['transaction_no'] ?? $paymentData['data']['billcode'] ?? null,
                'payment_url' => $paymentData['data']['payment_url'] ?? null,
                'status' => 'paid',
                'response' => $paymentData,
            ]);
        }

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
            'product_image' => $data['product_image'] ?? $product->product_image,
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

    public function filter_solar_companies($filters)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return collect();
        }

        $query = Solar_company::query();

        if (isset($filters['company_name'])) {
            $query->where('company_name', 'like', '%' . $filters['company_name'] . '%');
        }
        if (isset($filters['company_email'])) {
            $query->where('company_email', 'like', '%' . $filters['company_email'] . '%');
        }
        if (isset($filters['company_phone'])) {
            $query->where('company_phone', 'like', '%' . $filters['company_phone'] . '%');
        }
        if (isset($filters['company_status'])) {
            $query->where('company_status', $filters['company_status']);
        }
        if (isset($filters['governorate_id']) || isset($filters['area_id']) || isset($filters['neighborhood_id'])) {
            $query->whereHas('addresses', function ($addressQuery) use ($filters) {
                if (isset($filters['governorate_id'])) {
                    $addressQuery->where('governorate_id', $filters['governorate_id']);
                }
                if (isset($filters['area_id'])) {
                    $addressQuery->where('area_id', $filters['area_id']);
                }
                if (isset($filters['neighborhood_id'])) {
                    $addressQuery->where('neighborhood_id', $filters['neighborhood_id']);
                }
            });
        }
        if (isset($filters['product_type']) || isset($filters['product_brand']) || isset($filters['product_name']) || isset($filters['model_number']) || isset($filters['currency']) || isset($filters['product_price_min']) || isset($filters['product_price_max']) || isset($filters['disscount_type']) || isset($filters['disscount_value_min']) || isset($filters['disscount_value_max'])) {
            $query->whereHas('products', function ($productQuery) use ($filters) {
                if (isset($filters['product_type'])) {
                    $productQuery->where('product_type', $filters['product_type']);
                }
                if (isset($filters['product_brand'])) {
                    $productQuery->where('product_brand', 'like', '%' . $filters['product_brand'] . '%');
                }
                if (isset($filters['product_name'])) {
                    $productQuery->where('product_name', 'like', '%' . $filters['product_name'] . '%');
                }
                if (isset($filters['model_number'])) {
                    $productQuery->where('model_number', 'like', '%' . $filters['model_number'] . '%');
                }
                if (isset($filters['currency'])) {
                    $productQuery->where('currency', $filters['currency']);
                }
                if (isset($filters['product_price_min'])) {
                    $productQuery->where('price', '>=', $filters['product_price_min']);
                }
                if (isset($filters['product_price_max'])) {
                    $productQuery->where('price', '<=', $filters['product_price_max']);
                }
                if (isset($filters['disscount_type'])) {
                    $productQuery->where('disscount_type', $filters['disscount_type']);
                }
                if (isset($filters['disscount_value_min'])) {
                    $productQuery->where('disscount_value', '>=', $filters['disscount_value_min']);
                }
                if (isset($filters['disscount_value_max'])) {
                    $productQuery->where('disscount_value', '<=', $filters['disscount_value_max']);
                }
            });
        }

        return $query->with(['addresses.governorate', 'addresses.area', 'addresses.neighborhood', 'products'])->get();
    }

    public function create_custom_discount($data, $solar_company_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return null;
        }
        $product = $agency->products()->find($data['product_id']);
        $discount = $agency->specific_disscounts()->create([
            'product_id' => $data['product_id'] ?? null,
            'discount_type_type' => 'App\Models\Solar_company',
            'discount_type_id' => $solar_company_id->id,
            'discount_amount' => $data['discount_amount'],
            'disscount_type' => $data['disscount_type'],
            'currency' => $data['currency'] ?? 'SY',
            'product_type' => $product->product_type,
            'product_brand' => $product->product_brand,
            'disscount_active' => $data['disscount_active'] ?? true,
            'quentity_condition' => $data['quentity_condition'] ?? 0,
            'public' => $data['public'] ?? true,
        ]);
        return $discount;
    }

    public function show_custom_discounts($solar_company_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return [];
        }
        $discounts = $agency
            ->specific_disscounts()
            ->where('discount_type_type', 'App\Models\Solar_company')
            ->where('discount_type_id', $solar_company_id)
            ->with('product')
            ->get();

        return $discounts;
    }

    public function update_custom_discount($discount_id, $data)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return null;
        }
        $discount = $agency->specific_disscounts()->findOrFail($discount_id);
        $discount->update($data);
        $discount->save();
        $discount->refresh();

        return $discount;
    }

    public function delete_custom_discount($discount_id)
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return false;
        }

        $discount = $agency->specific_disscounts()->findOrFail($discount_id);
        $discount->delete();
        return true;
    }

    public function get_all_custom_discounts_grouped_by_company()
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $agency_manager = Agency_manager::findOrFail($agency_manager->id);
        $agency = $agency_manager->agencies()->first();

        if (!$agency) {
            return collect();
        }

        $discounts = $agency
            ->specific_disscounts()
            ->where('discount_type_type', 'App\Models\Solar_company')->get()
            // ->with(['discountType', 'product'])
            ->map(function($discount){
            return[
                'custom_discount'=>$discount,
                'company'=>$discount->discountType,
                'product'=>$discount->product,
            ];    
            });


            // get()
            // ->groupBy(function ($discount) {
            //     return $discount->discount_type_type . ':' . $discount->discount_type_id;
            // });

        return $discounts;
    }

    public function get_purchase_requests_from_companies($manager)
    {
        $agency = $manager->agencies()->first();

        if (!$agency) {
            return collect();
        }

        return Order_list::query()
            ->where('orderable_entity_type', Agency::class)
            ->where('orderable_entity_id', $agency->id)
            ->where('request_entity_type', Solar_company::class)
            ->whereHas('Payment', function ($paymentQuery) {
                $paymentQuery->where('status', 'paid');
            })
            ->with([
                'request_entity',
                'Items.product.inverters',
                'Items.product.batteries',
                'Items.product.solarPanals',
            ])
            ->latest('id')
            ->get();
    }

    public function create_purchase_invoice($request, $agency, $orderList)
    {
        $buyer = $orderList->request_entity;  // Solar Company
        $seller = $agency;  // Agency
        $orderList->status='in_progress';
        $orderList->save();
        // Generate unique invoice number
        $invoiceNumber = 'INV-' . date('YmdHis') . '-' . $agency->id;

        // Get first item's product as object_entity (or could aggregate all products)
        $firstItem = $orderList->Items()->first();
        $objectEntity = $firstItem ? $firstItem->product : null;

        if (!$objectEntity) {
            return ['error' => 'order list has no items'];
        }
        if ($orderList->with_delivery) {
            // Handle delivery fee logic if needed
            // $delivery_fee=calculate_delivery_fee($orderList); // Implement this function based on your logic
        } else {
            $delivery_fee = 0;
        }
        $purchaseInvoice = $agency->Invoice_purchase()->create([
            'buyer_entity_type' => get_class($buyer),
            'buyer_entity_id' => $buyer->id,
            'buyer_name' => $buyer->agency_name ?? $buyer->name ?? 'company',
            'buyer_phone' => $buyer->agency_phone ?? $buyer->phone ?? null,
            'order_list_id' => $orderList->id,
            'object_entity_type' => 'App\Models\Order_list',
            'object_entity_id' => $orderList->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $orderList->created_at->toDateString(),
            'due_date' => $request->due_date,
            'currency' => 'USD',  // يمكن تحديده من الطلبية لاحقاً
            'delivery_fee' => $delivery_fee,  // تُحدد لاحقاً
            'installation_fee' => 0,  // تُحدد لاحقاً
            'subtotal' => $orderList->sub_total_amount ?? 0,
            'total_discount' => $orderList->total_discount_amount ?? 0,
            'total_amount' => $orderList->total_amount ?? 0,
            'payment_status' => 'paid',
            'net_profit' => 0,
        ]);
        if ($delivery_fee > 0) {
            // notify the driver to deliver the order
        }
        return $purchaseInvoice;
    }
}
