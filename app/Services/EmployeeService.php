<?php

namespace App\Services;

use App\Models\Agency_manager;
use App\Models\Employee;
use App\Models\Solar_company_manager;
use App\Repositories\EmployeeRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    protected $employeeRepositoryInterface;
    protected $tokenRepositoryInterface;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepositoryInterface,
        TokenRepositoryInterface $tokenRepositoryInterface
    ) {
        $this->employeeRepositoryInterface = $employeeRepositoryInterface;
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
    }

    public function register($request, $data)
    {
        $identification_image = $request->file('identification_image')->getClientOriginalName();
        $identification_image_path = $request->file('identification_image')->storeAs('Employee/identification_image', $identification_image, 'public');
        $identification_image_URL = asset('storage/' . $identification_image_path);

        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $imagepath = $request->file('image')->storeAs('Employee/images', $image, 'public');
            $employee = $this->employeeRepositoryInterface->Create($request, $imagepath, $identification_image_path, $data);
            $imageUrl = asset('storage/' . $imagepath);
        } else {
            $employee = $this->employeeRepositoryInterface->Create($request, null, $identification_image_path, $data);
            $imageUrl = null;
        }

        $token = $employee->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);

        return [
            'employee' => $employee,
            'token' => $token,
            'refresh_token' => $refresh_token,
            'imageUrl' => $imageUrl,
            'identification_image_URL' => $identification_image_URL,
        ];
    }

    public function employee_profile()
    {
        $employeeAuth = Auth::guard('employee')->user();
        $employee = $this->employeeRepositoryInterface->employee_profile($employeeAuth->id);

        if (!$employee) {
            return null;
        }

        $image = $employee->image;
        $identification_image = $employee->identification_image;

        if ($identification_image == null) {
            $identification_imageUrl = null;
        } else {
            $identification_imageUrl = asset('storage/' . $identification_image);
        }

        if ($image == null) {
            $imageUrl = null;
        } else {
            $imageUrl = asset('storage/' . $image);
        }

        return [
            'employee' => $employee,
            'imageUrl' => $imageUrl,
            'identification_imageUrl' => $identification_imageUrl,
        ];
    }

    public function update_profile($request, $data)
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);

        if ($request->hasFile('identification_image')) {
            $originalName = $request->file('identification_image')->getClientOriginalName();
            $path = $request->file('identification_image')->storeAs('Employee/identification_image', $originalName, 'public');
            $data['identification_image'] = $path;
            $identificationImageUrl = asset('storage/' . $path);
            $employee->Activate_Account = false;
            $employee->save();
        } else {
            if ($employee->identification_image == null) {
                $identificationImageUrl = null;
            } else {
                $identificationImageUrl = asset('storage/' . $employee->identification_image);
            }
        }

        if ($request->hasFile('image')) {
            $originalName = $request->file('image')->getClientOriginalName();
            $path = $request->file('image')->storeAs('Employee/images', $originalName, 'public');
            $data['image'] = $path;
            $imageUrl = asset('storage/' . $path);
        } else {
            if ($employee->image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $employee->image);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $employee->update($data);
        $employee->fresh();
        $employee->save();

        return [$employee, $imageUrl, $identificationImageUrl];
    }

    public function request_employment_order($request)
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);

        return $this->employeeRepositoryInterface->request_employment_order($request, $employee);
    }

    public function process_employment_order($request)
    {
        if (Auth::guard('company_manager')->check()) {
            $manager = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id);
            $entity = $manager->solarCompanies()->first();

            if (!$entity) {
                return ['error' => 'No solar company found for this manager'];
            }

            return $this->employeeRepositoryInterface->process_employment_order($request, $entity, \App\Models\Solar_company::class);
        }

        if (Auth::guard('agency_manager')->check()) {
            $manager = Agency_manager::findOrFail(Auth::guard('agency_manager')->user()->id);
            $entity = $manager->agencies()->first();

            if (!$entity) {
                return ['error' => 'No agency found for this manager'];
            }

            return $this->employeeRepositoryInterface->process_employment_order($request, $entity, \App\Models\Agency::class);
        }

        return ['error' => 'Unauthorized'];
    }

    public function show_employment_orders()
    {
        if (Auth::guard('employee')->check()) {
            $employee_id = Auth::guard('employee')->user()->id;
            $employee=Employee::findOrFail($employee_id);
            return $this->employeeRepositoryInterface->show_employee_employment_orders($employee);
        }

        if (Auth::guard('company_manager')->check()) {
            $manager = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id);
            $entity = $manager->solarCompanies()->first();

            if (!$entity) {
                return collect();
            }
            return $this->employeeRepositoryInterface->show_entity_employment_orders($entity);
        }

        if (Auth::guard('agency_manager')->check()) {
            $manager = Agency_manager::findOrFail(Auth::guard('agency_manager')->user()->id);
            $entity = $manager->agencies()->first();

            if (!$entity) {
                return collect();
            }

            return $this->employeeRepositoryInterface->show_entity_employment_orders($entity);
        }

        return collect();
    }
}
