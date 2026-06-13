<?php
namespace App\Repositories;

use App\Models\Agency;
use App\Models\Areas;
use App\Models\Governorates;
use App\Models\Neighborhood;
use App\Models\Proccess_report;
use App\Models\Report;
use App\Models\Solar_company;
use App\Models\System_admin;
use App\Support\CompanyBanHelper;
use Illuminate\Support\Facades\DB;
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

    public function commision_policy($request, $admin)
    {
        $commissionPolicy = $admin->commisionPolices()->create([
            'policy_name' => $request->policy_name,
            'description' => $request->description,
            'target_type' => $request->target_type,
            'applies_to' => $request->applies_to,
            'commision_type' => $request->commision_type,
            'commision_value' => $request->commision_value,
            'is_active' => $request->is_active ?? true,
            'start_date' => $request->start_date??null,
            'end_date' => $request->end_date??null,
            'priority' => $request->priority ?? 0,
        ]);

        return $commissionPolicy;
    }

    public function update_commision_policy($request, $admin, $policy)
    {
        if ($policy->admin_id !== $admin->id) {
            abort(403, 'Unauthorized action');
        }

        $policy->update($request);
        $policy->fresh();
        $policy->save();

        return $policy;
    }

    public function delete_commision_policy($policy)
    {
        return $policy->delete();
    }

    public function show_commision_policies($admin)
    {
        return $admin->commisionPolices()->get();
    }

    public function filter_reports(array $filters)
    {
        $query = Report::with(['company', 'customer', 'proccessReports']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }

        if (array_key_exists('is_processed', $filters)) {
            if ($filters['is_processed']) {
                $query->whereHas('proccessReports');
            } else {
                $query->whereDoesntHave('proccessReports');
            }
        }

        return $query->get()->map(function (Report $report) {
            $latestProcess = $report->proccessReports->sortByDesc('created_at')->first();
            return [
                'id' => $report->id,
                'company_id' => $report->company_id,
                'company_name' => $report->company?->company_name ?? null,
                'customer_id' => $report->customer_id,
                'customer_name' => $report->customer?->first_name . ' ' . $report->customer?->last_name,
                'report_type' => $report->report_type,
                'report_subject' => $report->report_subject,
                'report_content' => $report->report_content,
                'created_at' => $report->created_at,
                'is_processed' => $report->proccessReports->isNotEmpty(),
                'latest_process' => $latestProcess ? [
                    'process_method' => $latestProcess->proccess_method,
                    'block_type' => $latestProcess->block_type,
                    'block_duaration_value' => $latestProcess->block_duaration_value,
                    'compensation_amount' => $latestProcess->compensation_amount,
                    'fine_amount' => $latestProcess->fine_amount,
                    'notes' => $latestProcess->notes,
                    'proccess_datetime' => $latestProcess->proccess_datetime,
                ] : null,
            ];
        });
    }

    public function proccess_report(array $data, $admin, $report)
    {
        return DB::transaction(function () use ($data, $admin, $report) {
            if ($report->proccessReports()->exists()) {
                throw new \RuntimeException('Report has already been processed');
            }

            $proccessDatetime = now();
            $company = $report->company;

            if (!$company) {
                throw new \RuntimeException('Report company not found');
            }

            if ($data['proccess_method'] === 'block') {
                $bannedUntil = CompanyBanHelper::calculateBanEnd(
                    $data['block_type'],
                    (int) $data['block_duaration_value'],
                    $proccessDatetime
                );

                $company->update(['banned_until' => $bannedUntil]);
            }

            return Proccess_report::create([
                'report_id' => $report->id,
                'admin_id' => $admin->id,
                'proccess_method' => $data['proccess_method'],
                'block_type' => $data['block_type'] ?? null,
                'block_duaration_value' => $data['block_duaration_value'] ?? null,
                'fine_amount' => $data['fine_amount'] ?? null,
                'notes' => $data['notes'] ?? null,
                'proccess_datetime' => $proccessDatetime,
            ])->load(['report.company', 'admin']);
        });
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
