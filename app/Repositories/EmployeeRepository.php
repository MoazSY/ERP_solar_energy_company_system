<?php
namespace App\Repositories;

use App\Models\Company_agency_employee;
use App\Models\Deliveries;
use App\Models\Employee;
// use App\Models\Employment_orders;
use Illuminate\Support\Facades\Hash;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function employee_profile($employee_id)
    {
        return Employee::findOrFail($employee_id);
    }

    public function create_internal_employee_request($request, $entity, $entityTypeClass, $data)
    {
        $employee = Employee::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'employee_type' => $request->employee_type,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'],
            'account_number' => $request->account_number,
            'syriatel_cash_phone' => $request->syriatel_cash_phone,
            'image' => $request->image,
            'identification_image' => $request->identification_image,
            'about_him' => $request->about_him,
            'is_active' => false,
        ]);
        return [
            'employee' => $employee->fresh(),
        ];
    }

    public function register_employee_company_agency($request, $entity, $entityTypeClass)
    {
        $existing = Company_agency_employee::query()
            ->where('employee_id', $request->employee_id)
            ->where('entity_type_type', $entityTypeClass)
            ->where('entity_type_id', $entity->id)
            ->where('role', $request->role)
            ->first();

        if ($existing) {
            return ['error' => 'Employee is already assigned to this role in this entity'];
        }
        $assignment = Company_agency_employee::create([
            'employee_id' => $request->employee_id,
            'entity_type_type' => $entityTypeClass,
            'entity_type_id' => $entity->id,
            'role' => $request->role,
            'salary_type' => $request->salary_type,
            'currency' => $request->currency,
            'work_type' => $request->work_type,
            'payment_method' => $request->payment_method,
            'payment_frequency' => $request->payment_frequency,
            'salary_rate' => $request->salary_type === 'rate' ? ($request->salary_rate ?? 0) : 0,
            'salary_amount' => $request->salary_type === 'fixed' ? ($request->salary_amount ?? 0) : 0,
        ]);

        $employeeTypeMap = [
            'inventory_manager' => 'inventory_manager',
            'driver' => 'driver',
            'install_technician' => 'technician',
            'metal_base_technician' => 'technician',
            'blacksmith_workshop' => 'technician',
        ];

        $employeeType = $employeeTypeMap[$request->role] ?? null;

        Employee::where('id', $request->employee_id)->update([
            'is_active' => true,
            'employee_type' => $employeeType,
        ]);

        return $assignment->load(['employee', 'entityType']);
    }

    public function search_employees($filter)
    {
        $query = Employee::query();

        if (isset($filter['first_name'])) {
            $query->where('first_name', 'like', '%' . $filter['first_name'] . '%');
        }

        if (isset($filter['last_name'])) {
            $query->where('last_name', 'like', '%' . $filter['last_name'] . '%');
        }

        if (isset($filter['email'])) {
            $query->where('email', 'like', '%' . $filter['email'] . '%');
        }

        if (isset($filter['employee_type'])) {
            $query->where('employee_type', $filter['employee_type']);
        }

        return $query->get();
    }

    public function show_entity_employees($entity, $entityTypeClass)
    {
        $assignments = Company_agency_employee::query()
            ->where('entity_type_type', $entityTypeClass)
            ->where('entity_type_id', $entity->id)
            ->with(['employee'])
            ->latest('id')
            ->get();

        return $assignments->map(function ($assignment) {
            return [
                'assignment' => $assignment,
                'employee' => $assignment->employee,
                'imageUrl' => $assignment->employee?->image ? asset('storage/' . $assignment->employee->image) : null,
                'identification_imageUrl' => $assignment->employee?->identification_image ? asset('storage/' . $assignment->employee->identification_image) : null,
            ];
        });
    }
    public function show_delivery_tasks($employee)
    {
        $deliveries = $employee->driverDeliveries()->with(['deliverable_object', 'entity_type'])->get();
        $delivery_tasks = $deliveries->map(function ($delivery) {
            $targetEntity = $delivery->deliverable_object?->request_entity;

            return [
                'delivery' => $delivery,
                'order_list' => $delivery->deliverable_object,
                'entity_source' => $delivery->entity_type,
                'entity_target' => $targetEntity,
                'address' => $targetEntity?->addresses()->first(),
                'items' => $delivery->deliverable_object->Items()->with('product')->get()??null,
                'weight_kg' => $delivery
                    ->deliverable_object
                    ->Items()
                    ->with(['product.inverters', 'product.batteries', 'product.solarPanals'])
                    ->get()
                    ->sum(function ($item) {
                        $unitWeight = $item->product?->inverters?->weight_kg
                            ?? $item->product?->batteries?->weight_kg
                            ?? $item->product?->solarPanals?->weight_kg
                            ?? 0;

                        return $unitWeight * ($item->quantity ?? 1);
                    })
            ];
        });
        return $delivery_tasks;
    }
    public function proccess_delivery_task($request, $employee)
    {
        $delivery = Deliveries::findOrFail($request->delivery_id);

        if ($delivery->driver_id !== $employee->id) {
            return ['error' => 'Unauthorized'];
        }

        if ($delivery->driver_approved_delivery_task !== 'pending') {
            return ['error' => 'This delivery task has already been processed'];
        }

        $delivery->driver_approved_delivery_task = $request->action === 'approve' ? 'approve' : 'reject';
        $delivery->save();
    return $delivery;
    }
}
