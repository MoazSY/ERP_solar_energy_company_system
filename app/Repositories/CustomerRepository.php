<?php
namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function Create($request, $image_path, $data)
    {
        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'email' => $data['email']??$request->email??null,
            'password' => Hash::make($request->password),
            'phoneNumber' => $data['phoneNumber'] ?? $request->phoneNumber ?? null,
            'account_number' => $request->account_number ?? null,
            'syriatel_cash_phone' => $request->syriatel_cash_phone ?? null,
            'image' => $image_path,
            'about_him' => $request->about_him ?? null,
        ]);

        return $customer;
    }

    public function customer_profile($customer_id)
    {
        return Customer::findOrFail($customer_id);
    }
}
