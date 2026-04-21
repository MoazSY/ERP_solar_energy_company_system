<?php
namespace App\Repositories;

use App\Models\Agency;
// use App\Models\Company_agency_employee;
use App\Models\Employee;
use App\Models\Employment_orders;
use App\Models\Solar_company;
use Illuminate\Support\Facades\Hash;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function Create($request, $image_path, $identification_image_path, $data)
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
            'image' => $image_path,
            'identification_image' => $identification_image_path,
            'about_him' => $request->about_him,
            'Activate_Account' => false
        ]);

        return $employee;
    }

    public function employee_profile($employee_id)
    {
        return Employee::findOrFail($employee_id);
    }

    public function request_employment_order($request, $employee)
    {
        $entityMap = [
            'solar_company' => Solar_company::class,
            'agency' => Agency::class,
        ];

        $entityClass = $entityMap[$request->entity_type] ?? null;
        if (!$entityClass) {
            return ['error' => 'Invalid entity type'];
        }

        $entity = $entityClass::find($request->entity_id);
        if (!$entity) {
            return ['error' => 'Target entity not found'];
        }

        $pendingOrder = Employment_orders::query()
            ->where('employee_id', $employee->id)
            ->where('entity_type_type', $entityClass)
            ->where('entity_type_id', $entity->id)
            ->where('job_title', $request->job_title)
            ->where('status', 'pending')
            ->first();

        if ($pendingOrder) {
            return ['error' => 'You already have a pending request for this role'];
        }
           $employment_order= $employee->employmentOrders()->create([
            'entity_type_type' =>get_class($entity),
            'entity_type_id' => $entity->id,
            'job_title' => $request->job_title,
            'status' => 'pending',
            ]);
            return $employment_order;
    }

    public function process_employment_order($request, $entity, $entityTypeClass)
    {
        $order=$entity->Employment_orders()->where('id',$request->employment_order_id)->where('status','pending')->first();
        if (!$order) {
            return ['error' => 'Employment order not found or already processed'];
        }

        $order->status = $request->status;
        $order->reject_cause = $request->status === 'rejected' ? $request->reject_cause : null;
        $order->save();
        return $order;
    }

    public function show_employee_employment_orders($employee)
    {
        $employment_orders=$employee->employmentOrders()->with(['entity_type'])->latest('id')->get();

        return $employment_orders;
    }

    public function show_entity_employment_orders($entity)
    {
       $embloyee_orders= $entity->Employment_orders()->where('status','pending')->with(['employee'])->latest('id')->get();

        return $embloyee_orders;
    }
}
