<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class CustomerService
{
	protected $customerRepositoryInterface;
	protected $tokenRepositoryInterface;

	public function __construct(
		CustomerRepositoryInterface $customerRepositoryInterface,
		TokenRepositoryInterface $tokenRepositoryInterface
	) {
		$this->customerRepositoryInterface = $customerRepositoryInterface;
		$this->tokenRepositoryInterface = $tokenRepositoryInterface;
	}

	public function register($request, $data)
	{
		if ($request->hasFile('image')) {
			$image = $request->file('image')->getClientOriginalName();
			$imagepath = $request->file('image')->storeAs('Customer/images', $image, 'public');
			$customer = $this->customerRepositoryInterface->Create($request, $imagepath, $data);
			$imageUrl = asset('storage/' . $imagepath);
		} else {
			$customer = $this->customerRepositoryInterface->Create($request, null, $data);
			$imageUrl = null;
		}

		$token = $customer->createToken('authToken')->plainTextToken;
		$this->tokenRepositoryInterface->Add_expierd_token($token);
		$refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);

		return [
			'customer' => $customer,
			'token' => $token,
			'refresh_token' => $refresh_token,
			'imageUrl' => $imageUrl,
		];
	}

	public function customer_profile()
	{
		$customer = Auth::guard('customer')->user();
		$profile = $this->customerRepositoryInterface->customer_profile($customer->id);
		$imageUrl = $profile->image ? asset('storage/' . $profile->image) : null;

		return ['customer' => $profile, 'imageUrl' => $imageUrl];
	}

	public function update_profile($request, $data)
	{
		$customer_id = Auth::guard('customer')->user()->id;
		$customer = Customer::findOrFail($customer_id);

		if ($request->hasFile('image')) {
			$originalName = $request->file('image')->getClientOriginalName();
			$path = $request->file('image')->storeAs('Customer/images', $originalName, 'public');
			$data['image'] = $path;
			$imageUrl = asset('storage/' . $path);
		} else {
			$imageUrl = $customer->image ? asset('storage/' . $customer->image) : null;
		}

		if (!empty($data['password'])) {
			$data['password'] = Hash::make($data['password']);
		}

		$customer->update($data);
		$customer->fresh();
		$customer->save();

		return [$customer, $imageUrl];
	}
}
