<?php
namespace App\Services;

use App\Models\Agency;
use App\Models\Areas;
use App\Models\Company_agency_subscribe;
use App\Models\Neighborhood;
use App\Models\Solar_company;
use App\Models\Subscribe_polices;
use App\Models\System_admin;
use App\Repositories\SystemAdminRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SystemAdminService
{
    protected $SystemAdminRepositoryInterface;
    protected $tokenRepositoryInterface;

    public function __construct(SystemAdminRepositoryInterface $systemAdminRepositoryInterface, TokenRepositoryInterface $tokenRepositoryInterface)
    {
        $this->SystemAdminRepositoryInterface = $systemAdminRepositoryInterface;
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
    }

    public function register($request, $data)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $imagepath = $request->file('image')->storeAs('SystemAdmin/images', $image, 'public');
            $admin = $this->SystemAdminRepositoryInterface->Create($request, $imagepath, $data);
            $imageUrl = asset('storage/' . $imagepath);
        } else {
            $admin = $this->SystemAdminRepositoryInterface->Create($request, null, $data);
            $imageUrl = null;
        }
        $token = $admin->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);
        return ['admin' => $admin, 'token' => $token, 'refresh_token' => $refresh_token, 'imageUrl' => $imageUrl];
    }

    public function Admin_profile()
    {
        $admin = Auth::guard('admin')->user();
        $profile = $this->SystemAdminRepositoryInterface->Admin_profile($admin->id);
        $image = $profile->image;
        if ($image == null)
            $imageUrl = null;
        else
            $imageUrl = asset('storage/' . $image);
        return ['admin' => $profile, 'imageUrl' => $imageUrl];
    }

    public function update_profile($request, $data)
    {
        $admin_id = Auth::guard('admin')->user()->id;
        $admin = System_admin::findOrFail($admin_id);
        if ($request->hasFile('image')) {
            $originalName = $request->file('image')->getClientOriginalName();
            $path = $request->file('image')->storeAs('admin/images', $originalName, 'public');
            $data['image'] = $path;
            $imageUrl = asset('storage/' . $path);
        } else {
            if ($admin->image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $admin->image);
            }
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $admin->update($data);
        $admin->fresh();
        $admin->save();
        return [$admin, $imageUrl];
    }

    public function add_governorates($request)
    {
        $governorates = $this->SystemAdminRepositoryInterface->add_governorates($request);
        return $governorates;
    }

    public function add_area($request, $governorate)
    {
        foreach ($request->areas as $areaData) {
            $exists = Areas::where('governorate_id', $governorate->id)
                ->where('name', $areaData['name'])
                ->exists();

            if ($exists) {
                throw new \Exception("area name'{$areaData['name']}' exists already in this governorate");
            }
        }

        $area = $this->SystemAdminRepositoryInterface->add_area($request, $governorate);
        return $area;
    }

    public function add_neighborhoods($request, $area)
    {
        // التحقق من فرادة اسم الحي في نفس المنطقة
        foreach ($request->neighborhoods as $neighborhood) {
            $exists = Neighborhood::where('area_id', $area->id)
                ->where('name', $neighborhood['name'])
                ->exists();

            if ($exists) {
                throw new \Exception("neighborhood name '{$neighborhood['name']}' exists already in this area");
            }
        }
        $neighborhoods = $this->SystemAdminRepositoryInterface->add_neighborhoods($request, $area);
        return $neighborhoods;
    }

    public function get_governorates()
    {
        return $this->SystemAdminRepositoryInterface->get_governorates();
    }

    public function get_areas($governorate)
    {
        return $this->SystemAdminRepositoryInterface->get_areas($governorate);
    }

    public function get_neighborhoods($area)
    {
        return $this->SystemAdminRepositoryInterface->get_neighborhoods($area);
    }

    public function UnActive_company()
    {
        $companies = $this->SystemAdminRepositoryInterface->UnActive_company();
        $result = $companies->map(fn($company) => [
            'company_logo' => $company->company_logo != null
                ? asset('storage/' . $company->company_logo)
                : null,
            'un_active_company' => $company
        ]);
        return $result;
    }

    public function UnActive_agency()
    {
        $agencies = $this->SystemAdminRepositoryInterface->UnActive_agency();
        $result = $agencies->map(fn($agency) => [
            'agency_logo' => $agency->agency_logo != null
                ? asset('storage/' . $agency->agency_logo)
                : null,
            'un_active_agency' => $agency
        ]);
        return $result;
    }

    public function show_all_company_registerd()
    {
        $all_company = $this->SystemAdminRepositoryInterface->show_all_company_registerd();
        $result = $all_company->filter(function ($c) {
            return $c->has('proccess_register')->where('status', 'approved')->exists();
        })->map(function ($company) {
            $company_logo = $company->company_logo;
            if ($company_logo == null) {
                $company_logoUrl = null;
            } else {
                $company_logoUrl = asset('storage/' . $company_logo);
            }
            return ['company' => $company, 'company_logoUrl' => $company_logoUrl];
        });
        return $result;
    }

    public function show_all_agency_registerd()
    {
        $all_agency = $this->SystemAdminRepositoryInterface->show_all_agency_registerd();
        $result = $all_agency->filter(function ($a) {
            return $a->has('proccess_register')->where('status', 'approved')->exists();
        })->map(function ($agency) {
            $agency_logo = $agency->agency_logo;
            if ($agency_logo == null) {
                $agency_logoUrl = null;
            } else {
                $agency_logoUrl = asset('storage/' . $agency_logo);
            }
            return ['agency' => $agency, 'agency_logoUrl' => $agency_logoUrl];
        });
        return $result;
    }

    public function proccess_company_register($request)
    {
        $admin = System_admin::findOrFail(Auth::guard('admin')->user()->id);
        if ($request->entity_type == 'solar_company') {
            $solar_company = Solar_company::findOrFail($request->entity_id);
            return $this->SystemAdminRepositoryInterface->proccess_company_register($request, $admin, $solar_company);
        } else
            $agency = Agency::findOrFail($request->entity_id);
        return $this->SystemAdminRepositoryInterface->proccess_company_register($request, $admin, $agency);
    }

    public function subscriptions_policy($request)
    {
        $admin = System_admin::findOrFail(Auth::guard('admin')->user()->id);
        return $this->SystemAdminRepositoryInterface->subscriptions_policy($request, $admin);
    }

    public function update_subscriptions_policy($request, $policy)
    {
        $admin = System_admin::findOrFail(Auth::guard('admin')->user()->id);
        return $this->SystemAdminRepositoryInterface->update_subscriptions_policy($request, $admin, $policy);
    }

    public function custom_subscribe_policy($request)
    {
        if ($request->entity_type == 'solar_company') {
            $company = Solar_company::findOrFail($request->entity_id);
        } elseif ($request->entity_type == 'agency') {
            $company = Agency::findOrFail($request->entity_id);
        } else {
            return null;
        }
        $custom_subscribe = $this->SystemAdminRepositoryInterface->custom_subscribe_policy($request, $company);
        return $custom_subscribe;
    }

    public function show_subscribtions_policies()
    {
        $generalPolicies = Subscribe_polices::whereDoesntHave('customSubscribes')->get();
    }

    public function show_custom_subscribtions_policies()
    {
        $customPolicies = Subscribe_polices::whereHas('customSubscribes', function ($query) {
            $query->where('is_active', true);  // سياسات لها اشتراك مخصص فعال
        })
            ->with(['customSubscribes' => function ($query) {
                $query->where('is_active', true);
            }, 'customSubscribes.subscribeable'])  //  تحميل الكيان المرتبط
            ->get();

        return $customPolicies;
    }

    public function show_subscribers_of_policy($policy)
    {
        $company_agency_subscribe = Company_agency_subscribe::where('subscribe_policy_id', $policy->id)->where('is_active', true)->get();
        $subscriber = $company_agency_subscribe->map(function ($subscribe) {
            return [
                'subscriber' => $subscribe->subscribeable,  // الكيان المشترك (شركة أو وكالة)
                'subscriber_type' => $subscribe->subscribeable_type,  // نوع الكيان (solar_company أو agency)
                'subscription_details' => $subscribe  // تفاصيل الاشتراك
            ];
        });
        return $subscriber;
    }

    public function show_subscribtions_policies_for_entity($request)
    {
        if ($request->entity_type == 'solar_company') {
            $company = Solar_company::findOrFail($request->entity_id);
        } elseif ($request->entity_type == 'agency') {
            $company = Agency::findOrFail($request->entity_id);
        } else {
            return null;
        }
        $subscriptions = $company->companyAgencySubscribes()->with(['subscribePolicy', 'customSubscribes'])->get();

        // إضافة مؤشر لمعرفة إذا كان الاشتراك مخصصًا أم عامًا
        $result = $subscriptions->map(function ($subscription) {
            return [
                'subscription' => $subscription,
                'is_custom' => $subscription->customSubscribes->isNotEmpty(),  // true إذا كان مخصصًا
                'custom_details' => $subscription->customSubscribes  // تفاصيل الاشتراك المخصص إن وجد
            ];
        });
        return $result;
    }
}
