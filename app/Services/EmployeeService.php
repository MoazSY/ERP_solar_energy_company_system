<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Agency_manager;
use App\Models\Deliveries;
use App\Models\Employee;
use App\Models\Solar_company;
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

    public function create_internal_employee_request($request, $data)
    {
        if (Auth::guard('company_manager')->check()) {
            $manager = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id);
            $entity = $manager->solarCompanies()->first();

            if (!$entity) {
                return ['error' => 'No solar company found for this manager'];
            }

            $entityTypeClass = Solar_company::class;
        } elseif (Auth::guard('agency_manager')->check()) {
            $manager = Agency_manager::findOrFail(Auth::guard('agency_manager')->user()->id);
            $entity = $manager->agencies()->first();

            if (!$entity) {
                return ['error' => 'No agency found for this manager'];
            }

            $entityTypeClass = Agency::class;
        } else {
            return ['error' => 'Unauthorized'];
        }

        if ($request->hasFile('identification_image')) {
            $identification_image = $request->file('identification_image')->getClientOriginalName();
            $request['identification_image'] = $request->file('identification_image')->storeAs('Employee/identification_image', $identification_image, 'public');
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $request['image'] = $request->file('image')->storeAs('Employee/images', $image, 'public');
        }

        return $this->employeeRepositoryInterface->create_internal_employee_request($request, $entity, $entityTypeClass, $data);
    }

    public function register_employee_company_agency($request)
    {
        if (Auth::guard('company_manager')->check()) {
            $manager = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id);
            $entity = $manager->solarCompanies()->first();

            if (!$entity) {
                return ['error' => 'No solar company found for this manager'];
            }

            $entityTypeClass = Solar_company::class;
        } elseif (Auth::guard('agency_manager')->check()) {
            $manager = Agency_manager::findOrFail(Auth::guard('agency_manager')->user()->id);
            $entity = $manager->agencies()->first();

            if (!$entity) {
                return ['error' => 'No agency found for this manager'];
            }

            $entityTypeClass = Agency::class;
        } else {
            return ['error' => 'Unauthorized'];
        }

        return $this->employeeRepositoryInterface->register_employee_company_agency($request, $entity, $entityTypeClass);
    }

    public function filter_employee($filter)
    {
        $employee = $this->employeeRepositoryInterface->search_employees($filter);
        $map = $employee->map(function ($emp) {
            return [
                'employee' => $emp,
                'imageUrl' => $emp->image ? asset('storage/' . $emp->image) : null,
                'identification_imageUrl' => $emp->identification_image ? asset('storage/' . $emp->identification_image) : null,
            ];
        });
        return $map;
    }

    public function show_entity_employees()
    {
        if (Auth::guard('company_manager')->check()) {
            $manager = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id);
            $entity = $manager->solarCompanies()->first();

            if (!$entity) {
                return ['error' => 'No solar company found for this manager'];
            }

            return $this->employeeRepositoryInterface->show_entity_employees($entity, Solar_company::class);
        }

        if (Auth::guard('agency_manager')->check()) {
            $manager = Agency_manager::findOrFail(Auth::guard('agency_manager')->user()->id);
            $entity = $manager->agencies()->first();

            if (!$entity) {
                return ['error' => 'No agency found for this manager'];
            }

            return $this->employeeRepositoryInterface->show_entity_employees($entity, Agency::class);
        }

        return ['error' => 'Unauthorized'];
    }

    public function show_delivery_tasks()
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);
        if ($employee->employee_type != 'driver') {
            return ['error' => 'Unauthorized'];
        }
        return $this->employeeRepositoryInterface->show_delivery_tasks($employee);
    }

    public function proccess_delivery_task($request)
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);
        if ($employee->employee_type != 'driver') {
            return ['error' => 'Unauthorized'];
        }
        return $this->employeeRepositoryInterface->proccess_delivery_task($request, $employee);
    }

    public function deliver_orderList($request)
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);
        if ($employee->employee_type != 'driver') {
            return ['error' => 'Unauthorized'];
        }
        $delivery = Deliveries::findOrFail($request->delivery_task_id);
        if (!$delivery->shipped_at) {
            return ['error' => 'Delivery has not been started yet'];
        }
        $delivery->delivery_status = 'delivered';
        $delivery->delivered_at = now();
        $delivery->save();
        $delivery_time = $delivery->delivered_at->diffInMinutes($delivery->shipped_at);
        $delivery->setAttribute('delivery_time', $delivery_time);
        return $delivery;
    }

    public function task_start($request)
    {
        $employee_id = Auth::guard('employee')->user()->id;
        $employee = Employee::findOrFail($employee_id);
        if ($employee->employee_type != 'driver') {
            return ['error' => 'Unauthorized'];
        }
        $delivery = Deliveries::findOrFail($request->delivery_task_id);
        if($delivery->driver_approved_delivery_task != 'approve'){
            return ['error' => 'Delivery task has not been approved by the driver yet'];
        }
        $delivery->delivery_status = 'in_transit';
        $delivery->shipped_at = now();
        $delivery->save();
        return $delivery;
    }
}
