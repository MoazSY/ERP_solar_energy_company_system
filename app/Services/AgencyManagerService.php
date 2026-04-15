<?php
namespace App\Services;

use App\Models\Agency;
use App\Models\Agency_manager;
use App\Repositories\AgencyManagerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AgencyManagerService
{
    protected $agencyManagerRepositoryInterface;
    protected $tokenRepositoryInterface;

    public function __construct(AgencyManagerRepositoryInterface $agencyManagerRepositoryInterface, TokenRepositoryInterface $tokenRepositoryInterface)
    {
        $this->agencyManagerRepositoryInterface = $agencyManagerRepositoryInterface;
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
    }

    public function register($request, $data)
    {
        $identification_image = $request->file('identification_image')->getClientOriginalName();
        $identification_image_path = $request->file('identification_image')->storeAs('AgencyManager/identification_image', $identification_image, 'public');
        $identification_image_URL = asset('storage/' . $identification_image_path);

        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $imagepath = $request->file('image')->storeAs('AgencyManager/images', $image, 'public');
            $agency_manager = $this->agencyManagerRepositoryInterface->Create($request, $imagepath, $identification_image_path, $data);
            $imageUrl = asset('storage/' . $imagepath);
        } else {
            $agency_manager = $this->agencyManagerRepositoryInterface->Create($request, null, $identification_image_path, $data);
            $imageUrl = null;
        }
        $token = $agency_manager->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);
        return ['agency_manager' => $agency_manager, 'token' => $token, 'refresh_token' => $refresh_token, 'imageUrl' => $imageUrl, 'identification_image_URL' => $identification_image_URL];
    }

    public function agency_manager_profile()
    {
        $agency_manager = Auth::guard('agency_manager')->user();
        $profile = $this->agencyManagerRepositoryInterface->agency_manager_profile($agency_manager->id);
        $image = $profile[0]->image;
        $agency = $profile[1];
        $agency = $agency->map(function ($item) {
            $agency_logo = $item->agency_logo;
            if ($agency_logo == null) {
                $agency_logoUrl = null;
            } else {
                $agency_logoUrl = asset('storage/' . $agency_logo);
            }
            return ['agency' => $item, 'agency_logoUrl' => $agency_logoUrl];
        });
        $identification_image = $profile[0]->identification_image;
        if ($identification_image == null) {
            $identification_imageUrl = null;
        } else {
            $identification_imageUrl = asset('storage/' . $identification_image);
        }
        if ($image == null)
            $imageUrl = null;
        else
            $imageUrl = asset('storage/' . $image);

        return ['agency_manager' => $profile[0], 'imageUrl' => $imageUrl, 'identification_imageUrl' => $identification_imageUrl, 'agency' => $agency];
    }

    public function update_profile($request, $data)
    {
        $agency_manager_id = Auth::guard('agency_manager')->user()->id;
        $agency_manager = Agency_manager::findOrFail($agency_manager_id);

        if ($request->hasFile('identification_image')) {
            $originalName = $request->file('identification_image')->getClientOriginalName();
            $path = $request->file('identification_image')->storeAs('AgencyManager/identification_image', $originalName, 'public');
            $data['identification_image'] = $path;
            $imageUrl = asset('storage/' . $path);
            $agency_manager->Activate_Account = false;
            $agency_manager->save();
        } else {
            if ($agency_manager->identification_image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $agency_manager->identification_image);
            }
        }
        if ($request->hasFile('image')) {
            $originalName = $request->file('image')->getClientOriginalName();
            $path = $request->file('image')->storeAs('AgencyManager/images', $originalName, 'public');
            $data['image'] = $path;
            $imageUrl = asset('storage/' . $path);
        } else {
            if ($agency_manager->image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $agency_manager->image);
            }
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $agency_manager->update($data);
        $agency_manager->fresh();
        $agency_manager->save();
        return [$agency_manager, $imageUrl];
    }

    public function Agency_register($request, $data)
    {
        $agency_manager_id = Auth::guard('agency_manager')->user()->id;
        $agency_manager = Agency_manager::findOrFail($agency_manager_id);
        if ($request->hasFile('agency_logo')) {
            $agency_logo = $request->file('agency_logo')->getClientOriginalName();
            $agency_logo_path = $request->file('agency_logo')->storeAs('AgencyManager/agency_logo', $agency_logo, 'public');
            $agency_logo_URL = asset('storage/' . $agency_logo_path);
            $agency = $this->agencyManagerRepositoryInterface->Agency_register($request, $data, $agency_manager, $agency_logo_path);
        } else {
            $agency = $this->agencyManagerRepositoryInterface->Agency_register($request, $data, $agency_manager, null);
            $agency_logo_URL = null;
        }
        return ['agency' => $agency, 'agencyLogo' => $agency_logo_URL];
    }

    public function update_agency($request, $data, $agency)
    {
        $agency = Agency::findOrFail($agency->id);
        if ($request->file('agency_logo')) {
            $agency_logo = $request->file('agency_logo')->getClientOriginalName();
            $agency_logo_path = $request->file('agency_logo')->storeAs('AgencyManager/agency_logo', $agency_logo, 'public');
            $data['agency_logo'] = $agency_logo_path;
            $agency_logo_URL = asset('storage/' . $agency_logo_path);
        } else {
            if ($agency->agency_logo == null) {
                $agency_logo_URL = null;
            } else {
                $agency_logo_URL = asset('storage/' . $agency->agency_logo);
            }
        }
        if ($request->commerical_register_number) {
            $agency->agency_status = 'pending';
            $agency->save();
        }
        $agency->update($data);
        $agency->fresh();
        $agency->save();
        return [$agency, $agency_logo_URL];
    }

    public function agency_address($request, $agency)
    {
        $agency_address = $this->agencyManagerRepositoryInterface->agency_address($request, $agency);
        return $agency_address;
    }

    public function subscribe_in_policy($request)
    {
        $agency_manager_id = Auth::guard('agency_manager')->user()->id;
        $agency_manager = Agency_manager::findOrFail($agency_manager_id);
        $agency = $agency_manager->agencies()->first();
        $subscribe = $this->agencyManagerRepositoryInterface->subscribe_in_policy($request, $agency);
        return $subscribe;
    }

    public function add_agency_products($request, $data)
    {
        $agency_manager_id = Auth::guard('agency_manager')->user()->id;
        $agency_manager = Agency_manager::findOrFail($agency_manager_id);
        $agency = $agency_manager->agencies()->first();
        if ($request->hasFile('product_image')) {
            $product_image = $request->file('product_image')->getClientOriginalName();
            $product_image_path = $request->file('product_image')->storeAs('AgencyManager/product_images', $product_image, 'public');
            $data['product_image'] = $product_image_path;
            $product_image_URL = asset('storage/' . $product_image_path);
        } else {
            $data['product_image'] = null;
            $product_image_URL = null;
        }
        $result = $this->agencyManagerRepositoryInterface->add_agency_products($data, $agency);
        return [$result, $product_image_URL];
    }

    public function add_agency_product_battery($request, $product_id)
    {
        return $this->agencyManagerRepositoryInterface->add_agency_product_battery($request, $product_id);
    }

    public function add_agency_product_solar_panel($request, $product_id)
    {
        return $this->agencyManagerRepositoryInterface->add_agency_product_solar_panel($request, $product_id);
    }

    public function add_agency_product_inverter($request, $product_id)
    {
        return $this->agencyManagerRepositoryInterface->add_agency_product_inverter($request, $product_id);
    }

    public function show_agency_products()
    {
        $agencyManager = Auth::guard('agency_manager')->user();
        $agencyManager = Agency_manager::findOrFail($agencyManager->id);
        $products = $this->agencyManagerRepositoryInterface->show_agency_products($agencyManager);
        $products = $products->map(function ($item) {
            $product_image = $item->product_image;
            if ($product_image == null) {
                $product_image_URL = null;
            } else {
                $product_image_URL = asset('storage/' . $product_image);
            }
            $details = null;
            if ($item->product_type === 'battery') {
                $details = $item->batteries;
            } elseif ($item->product_type === 'solar_panel') {
                $details = $item->solarPanals;
            } elseif ($item->product_type === 'inverter') {
                $details = $item->inverters;
            }
            return ['product' => $item, 'product_image' => $product_image_URL, 'details' => $details];
        });
        return $products;
    }

    public function update_agency_product($request, $data, $product_id)
    {
        return $this->agencyManagerRepositoryInterface->update_agency_product($request, $data, $product_id);
    }

    public function delete_agency_product($product_id)
    {
        return $this->agencyManagerRepositoryInterface->delete_agency_product($product_id);
    }

    public function delete_agency_product_details($product_id)
    {
        return $this->agencyManagerRepositoryInterface->delete_agency_product_details($product_id);
    }

    public function filter_agency_products($filters)
    {
        return $this->agencyManagerRepositoryInterface->filter_agency_products($filters);
    }
}
