<?php
namespace App\Services;

// use App\Models\Agency;

use App\Models\Agency_manager;
use App\Models\Deliveries;
// use App\Models\Offers;
use App\Models\Order_list;
use App\Models\Products;
use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use App\Models\Subscribe_polices;
use App\Models\System_admin;
use App\Repositories\SolarCompanyManagerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use App\Services\ApiSyriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class SolarCompanyManagerService
{
    protected $solarCompanyManagerRepositoryInterface;
    protected $tokenRepositoryInterface;
    protected $apiSyriaService;
    protected $osrmService;

    public function __construct(
        SolarCompanyManagerRepositoryInterface $solarCompanyManagerRepositoryInterface,
        TokenRepositoryInterface $tokenRepositoryInterface,
        ApiSyriaService $apiSyriaService,
        OsrmService $osrmService
    ) {
        $this->solarCompanyManagerRepositoryInterface = $solarCompanyManagerRepositoryInterface;
        $this->tokenRepositoryInterface = $tokenRepositoryInterface;
        $this->apiSyriaService = $apiSyriaService;
        $this->osrmService = $osrmService;
    }

    public function Register($request, $data)
    {
        $identification_image = $request->file('identification_image')->getClientOriginalName();
        $identification_image_path = $request->file('identification_image')->storeAs('CompanyManager/identification_image', $identification_image, 'public');
        $identification_image_URL = asset('storage/' . $identification_image_path);

        if ($request->hasFile('image')) {
            $image = $request->file('image')->getClientOriginalName();
            $imagepath = $request->file('image')->storeAs('CompanyManager/images', $image, 'public');
            $company_mamager = $this->solarCompanyManagerRepositoryInterface->Create($request, $imagepath, $identification_image_path, $data);
            $imageUrl = asset('storage/' . $imagepath);
        } else {
            $company_mamager = $this->solarCompanyManagerRepositoryInterface->Create($request, null, $identification_image_path, $data);
            $imageUrl = null;
        }
        $token = $company_mamager->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token = $this->tokenRepositoryInterface->Add_refresh_token($token);
        return ['company_manager' => $company_mamager, 'token' => $token, 'refresh_token' => $refresh_token, 'imageUrl' => $imageUrl, 'identification_image_URL' => $identification_image_URL];
    }

    public function company_manager_profile()
    {
        $company_manager = Auth::guard('company_manager')->user();
        $profile = $this->solarCompanyManagerRepositoryInterface->company_manager_profile($company_manager->id);
        $image = $profile[0]->image;
        $company = $profile[1];
        $company = $company->map(function ($item) {
            $company_logo = $item->company_logo;
            if ($company_logo == null) {
                $company_logoUrl = null;
            } else {
                $company_logoUrl = asset('storage/' . $company_logo);
            }
            return ['company' => $item, 'company_logoUrl' => $company_logoUrl];
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

        return ['company_manager' => $profile[0], 'imageUrl' => $imageUrl, 'identification_imageUrl' => $identification_imageUrl, 'solar_company' => $company];
    }

    public function update_profile($request, $data)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company_manager = Solar_company_manager::findOrFail($company_manager_id);

        if ($request->hasFile('identification_image')) {
            $originalName = $request->file('identification_image')->getClientOriginalName();
            $path = $request->file('identification_image')->storeAs('CompanyManager/identification_image', $originalName, 'public');
            $data['identification_image'] = $path;
            $imageUrl = asset('storage/' . $path);
            $company_manager->Activate_Account = false;
            $company_manager->save();
        } else {
            if ($company_manager->identification_image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $company_manager->identification_image);
            }
        }
        if ($request->hasFile('image')) {
            $originalName = $request->file('image')->getClientOriginalName();
            $path = $request->file('image')->storeAs('CompanyManager/images', $originalName, 'public');
            $data['image'] = $path;
            $imageUrl = asset('storage/' . $path);
        } else {
            if ($company_manager->image == null) {
                $imageUrl = null;
            } else {
                $imageUrl = asset('storage/' . $company_manager->image);
            }
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $company_manager->update($data);
        $company_manager->fresh();
        $company_manager->save();
        return [$company_manager, $imageUrl];
    }

    public function Company_register($request, $data)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company_mamager = Solar_company_manager::findOrFail($company_manager_id);
        if ($request->hasFile('company_logo')) {
            $company_logo = $request->file('company_logo')->getClientOriginalName();
            $company_logo_path = $request->file('company_logo')->storeAs('CompanyManager/company_logo', $company_logo, 'public');
            $company_logo_URL = asset('storage/' . $company_logo_path);
            $solarCompany = $this->solarCompanyManagerRepositoryInterface->Company_register($request, $data, $company_mamager, $company_logo_path);
        } else {
            $solarCompany = $this->solarCompanyManagerRepositoryInterface->Company_register($request, $data, $company_mamager, null);
            $company_logo_URL = null;
        }
        return ['solarCompany' => $solarCompany, 'companyLogo' => $company_logo_URL];
    }

    public function update_company($request, $data, $solarCompany)
    {
        $solarCompany = Solar_company::findOrFail($solarCompany->id);
        if ($request->file('company_logo')) {
            $company_logo = $request->file('company_logo')->getClientOriginalName();
            $company_logo_path = $request->file('company_logo')->storeAs('CompanyManager/company_logo', $company_logo, 'public');
            $data['company_logo'] = $company_logo_path;
            $company_logo_URL = asset('storage/' . $company_logo_path);
        } else {
            if ($solarCompany->company_logo == null) {
                $company_logo_URL = null;
            } else {
                $company_logo_URL = asset('storage/' . $solarCompany->company_logo);
            }
        }
        if ($request->commerical_register_number) {
            $solarCompany->company_status = 'pending';
            $solarCompany->save();
        }
        $solarCompany->update($data);
        $solarCompany->fresh();
        $solarCompany->save();
        return [$solarCompany, $company_logo_URL];
    }

    public function company_address($request, $solarCompany)
    {
        $company_address = $this->solarCompanyManagerRepositoryInterface->company_address($request, $solarCompany);
        return $company_address;
    }

    public function show_custom_subscriptions()
    {
        $token = PersonalAccessToken::findToken(request()->bearerToken());
        $user = $token->tokenable;
        if ($user instanceof \App\Models\Solar_company_manager) {
            $user = Solar_company_manager::findOrFail($user->id);
            $subscriptions = $this->solarCompanyManagerRepositoryInterface->show_custom_subscriptions($user);
        } elseif ($user instanceof \App\Models\Agency_manager) {
            $user = Agency_manager::findOrFail($user->id);
            $subscriptions = app(\App\Repositories\AgencyManagerRepositoryInterface::class)->show_custom_subscriptions($user);
        } else {
            return null;
        }

        return $subscriptions;
    }

    public function subscribe_in_policy($request)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company_manager = Solar_company_manager::findOrFail($company_manager_id);
        $company = $company_manager->solarCompanies()->first();
        $subscribePolicy = Subscribe_polices::findOrFail($request->subscribe_policy_id);
        $beneficiaryAdmin = System_admin::find($subscribePolicy->admin_id);

        if (!$beneficiaryAdmin) {
            return ['error' => 'Payment beneficiary admin is not configured'];
        }

        if ($request->payment_method !== 'syriatel_cash' && $request->payment_method !== 'shamcash') {
            return ['error' => 'Unsupported payment method'];
        }

        if ($subscribePolicy->currency == 'USD') {
            $amount = (float) $subscribePolicy->subscription_fee * 1.35;  // Convert USD to  new SYP
        } else {
            $amount = (float) $subscribePolicy->subscription_fee / 100;  // Convert from old SYP to new SYP
        }

        if ($request->payment_method === 'syriatel_cash') {
            $toGsm = $beneficiaryAdmin->syriatel_cash_phone;
            if (!$toGsm) {
                return ['error' => 'Syriatel beneficiary phone is not configured on target account'];
            }

            $paymentResponse = $this->apiSyriaService->transferCash(
                $request->gsm,
                $toGsm,
                $amount,
                $request->pin_code
            );
        } else {
            $toAccountAddress = $beneficiaryAdmin->account_number;
            if (!$toAccountAddress) {
                return ['error' => 'ShamCash beneficiary account address is not configured on target account'];
            }

            if (!$request->account_address) {
                return ['error' => 'Your ShamCash account address is required for payment verification'];
            }

            $verificationResult = $this->apiSyriaService->verifyShamcashPaymentFromLogs(
                $toAccountAddress,
                $amount,
                $request->account_address
            );
            if (!$verificationResult['success']) {
                return ['error' => $verificationResult['message']];
            }

            $paymentResponse = [
                'success' => true,
                'message' => 'ShamCash payment verified from logs',
                'data' => $verificationResult['matched_log'] ?? null,
            ];
        }

        if (!$paymentResponse['success']) {
            return ['error' => $paymentResponse['message']];
        }

        $subscribe = $this->solarCompanyManagerRepositoryInterface->subscribe_in_policy($request, $company, $paymentResponse);
        return $subscribe;
    }

    public function show_all_agency()
    {
        $agencies = $this->solarCompanyManagerRepositoryInterface->show_all_agency();
        $agencies = $agencies->map(function ($item) {
            $manager_account = Agency_manager::findOrFail($item->agency_manager_id)->first()->account_number;
            $agency_logo = $item->agency_logo;
            if ($agency_logo == null) {
                $agency_logoUrl = null;
            } else {
                $agency_logoUrl = asset('storage/' . $agency_logo);
            }
            return ['agency' => $item, 'agency_logoUrl' => $agency_logoUrl, 'account_number' => $manager_account];
        });
        return $agencies;
    }

    public function filter_agency($filter)
    {
        return $this->solarCompanyManagerRepositoryInterface->filter_agency($filter);
    }

    public function show_agency_products($agency_id)
    {
        $products = $this->solarCompanyManagerRepositoryInterface->show_agency_products($agency_id);
        $products = $products->map(function ($item) {
            $product_image = $item->product_image;
            if ($product_image == null) {
                $product_image_URL = null;
            } else {
                $product_image_URL = asset('storage/' . $product_image);
            }

            $detailed_info = null;
            switch ($item->product_type) {
                case 'inverter':
                    $detailed_info = $item->inverters;
                    break;
                case 'battery':
                    $detailed_info = $item->batteries;
                    break;
                case 'solar_panel':
                    $detailed_info = $item->solarPanals;
                    break;
            }

            return [
                'product' => $item,
                'product_image' => $product_image_URL,
                'detailed_info' => $detailed_info
            ];
        });

        // Group products by product_type
        $grouped_products = $products->groupBy(function ($item) {
            return $item['product']->product_type;
        });

        return $grouped_products;
    }

    public function show_company_products()
    {
        $companyManager = Auth::guard('company_manager')->user();
        $companyManager = Solar_company_manager::findOrFail($companyManager->id);
        $company = $companyManager->solarCompanies()->first();

        if (!$company) {
            return null;
        }

        $products = $this->solarCompanyManagerRepositoryInterface->show_company_products($company);
        $products = $products->map(function ($item) {
            $product_image = $item->product_image;
            $product_image_URL = $product_image ? asset('storage/' . $product_image) : null;

            $details = null;
            if ($item->product_type === 'battery') {
                $details = $item->batteries;
            } elseif ($item->product_type === 'solar_panel') {
                $details = $item->solarPanals;
            } elseif ($item->product_type === 'inverter') {
                $details = $item->inverters;
            }

            return [
                'product' => $item,
                'product_image' => $product_image_URL,
                'details' => $details,
            ];
        });
        return $products;
    }

    public function request_purchase_invoice_agency($agency_id, $request)
    {
        $company = Solar_company_manager::findOrFail(Auth::guard('company_manager')->user()->id)->solarCompanies()->first();
        $agency = \App\Models\Agency::with('agencyManager')->findOrFail($agency_id);
        $beneficiaryManager = $agency->agencyManager;

        if (!$beneficiaryManager) {
            return ['error' => 'Agency manager beneficiary is not configured'];
        }

        if ($request->payment_method !== 'syriatel_cash' && $request->payment_method !== 'shamcash') {
            return ['error' => 'Unsupported payment method'];
        }

        $products = collect($request->products);
        $productIds = $products->pluck('id')->all();
        $productsMap = Products::whereIn('id', $productIds)
            ->with(['inverters', 'batteries', 'solarPanals'])
            ->get()
            ->keyBy('id');

        $amount = 0;
        foreach ($products as $item) {
            $product = $productsMap->get($item['id']);
            if (!$product) {
                continue;
            }

            $unitPrice = (float) $product->price;
            if ($product->currency === 'USD') {
                $unitPrice *= 1.35;
            } else {
                $unitPrice /= 100;  // convert from old SYP to new SYP
            }

            $quantity = (int) $item['quantity'];  //
            $lineSubTotal = $unitPrice * $quantity;

            if ($product->disscount_type === 'percentage') {
                $discount = ((float) $product->disscount_value / 100) * $lineSubTotal;
            } else {
                $discount = (float) $product->disscount_value * $quantity;
            }

            $amount += max($lineSubTotal - $discount, 0);
        }

        if ($amount <= 0) {
            return ['error' => 'Invalid amount for payment'];
        }

        $deliveryPricing = null;
        if ($request->with_delivery) {
            $deliveryPricing = $this->osrmService->calculateDeliveryFeeForPurchase($agency, $company, $products, $productsMap);

            if (isset($deliveryPricing['error'])) {
                return ['error' => $deliveryPricing['error']];
            }

            $amount += $deliveryPricing['delivery_fee'];
            $request->merge(['calculated_delivery_fee' => $deliveryPricing['delivery_fee']]);
        }
        if ($request->payment_method === 'syriatel_cash') {
            $toGsm = $beneficiaryManager->syriatel_cash_phone;
            if (!$toGsm) {
                return ['error' => 'Syriatel beneficiary phone is not configured on target account'];
            }

            $paymentResponse = $this->apiSyriaService->transferCash(
                $request->gsm,
                $toGsm,
                $amount,
                $request->pin_code
            );
        } else {
            $toAccountAddress = $beneficiaryManager->account_number;
            if (!$toAccountAddress) {
                return ['error' => 'ShamCash beneficiary account address is not configured on target account'];
            }

            if (!$request->account_address) {
                return ['error' => 'Your ShamCash account address is required for payment verification'];
            }

            $verificationResult = $this->apiSyriaService->verifyShamcashPaymentFromLogs(
                $toAccountAddress,
                $amount,
                $request->account_address
            );
            if (!$verificationResult['success']) {
                return ['error' => $verificationResult['message']];
            }

            $paymentResponse = [
                'success' => true,
                'message' => 'ShamCash payment verified from logs',
                'data' => $verificationResult['matched_log'] ?? null,
            ];
        }

        if (!$paymentResponse['success']) {
            return ['error' => $paymentResponse['message']];
        }

        $result = $this->solarCompanyManagerRepositoryInterface->request_purchase_invoice_agency(
            $agency_id,
            $request,
            $company,
            $paymentResponse,
            $request->payment_method,
            $amount
        );

        if ($deliveryPricing && is_array($result) && isset($result[0])) {
            $result[0]->setAttribute('calculated_delivery_fee', $deliveryPricing['delivery_fee']);
            $result[0]->setAttribute('delivery_distance_km', $deliveryPricing['distance_km']);
            $result[0]->setAttribute('delivery_duration_minutes', $deliveryPricing['duration_minutes']);
            $result[0]->setAttribute('delivery_weight_kg', $deliveryPricing['weight_kg']);
            $result[0]->setAttribute('delivery_rule_id', $deliveryPricing['rule_id']);
        }

        return $result;
    }

    public function get_purchase_requests_from_agencies()
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return collect();
        }
        return $this->solarCompanyManagerRepositoryInterface->get_purchase_requests_from_agencies($company);
    }

    public function delivery_rules($request)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }

        return $this->solarCompanyManagerRepositoryInterface->delivery_rules($request, $company);
    }

    public function show_delivery_rules()
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return collect();
        }

        return $this->solarCompanyManagerRepositoryInterface->show_delivery_rules($company);
    }

    public function update_delivery_rule($rule_id, $data)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return null;
        }

        return $this->solarCompanyManagerRepositoryInterface->update_delivery_rule($company, $rule_id, $data);
    }

    public function delete_delivery_rule($rule_id)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return false;
        }

        return $this->solarCompanyManagerRepositoryInterface->delete_delivery_rule($company, $rule_id);
    }

    public function assign_delivery_task($request)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }

        $orderList = Order_list::with(['orderableEntityType', 'purchaseInvoices', 'Items.product.inverters', 'Items.product.batteries', 'Items.product.solarPanals'])
            ->findOrFail($request->order_list_id);
        if (!$orderList) {
            return ['error' => 'Order list not found'];
        }
        if ($orderList->request_entity_type != Solar_company::class || (int) $orderList->request_entity_id != (int) $company->id) {
            return ['error' => 'Unauthorized'];
        }

        if ($orderList->with_delivery) {
            return ['error' => 'This order list has already been assigned for delivery from agency'];
        }
        if (!$orderList->purchaseInvoices) {
            return ['error' => 'order list does not have associated purchase invoices'];
        }

        if (!$orderList->orderableEntityType instanceof \App\Models\Agency) {
            return ['error' => 'Agency not found for this order list'];
        }

        return $this->solarCompanyManagerRepositoryInterface->assign_delivery_task($request, $company, $orderList);
    }

    public function show_delivery_task()
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company_manager = Solar_company_manager::findOrFail($company_manager_id);
        $company = $company_manager->solarCompanies()->first();
        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }
        return $this->solarCompanyManagerRepositoryInterface->show_delivery_tasks($company);
    }

    public function filter_delivery_tasks($filters)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();
        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }
        return $this->solarCompanyManagerRepositoryInterface->filter_delivery_tasks($company, $filters);
    }

    public function recieve_orderList($request, $orderList)
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();
        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }
        if ($orderList->request_entity_type != Solar_company::class || (int) $orderList->request_entity_id != (int) $company->id) {
            return ['error' => 'Unauthorized'];
        }
        if ($orderList->deliveries()->where('delivery_status', '!=', 'delivered')->exists()) {
            return ['error' => 'Cannot receive order list with undelivered deliveries'];
        }
        return $this->solarCompanyManagerRepositoryInterface->recieve_orderList($request, $orderList, $company);
    }

    public function paid_to_employee($request, $task_id)
    {
        $amount = 0;
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();
        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }
        if ($request->task_type == 'delivery') {
            $delivery_task = Deliveries::findOrFail($task_id);
            $task = $delivery_task;
            if (!$delivery_task) {
                return ['error' => 'Delivery task not found'];
            }
            if ($delivery_task->delivery_status != 'delivered') {
                return ['error' => 'cant pay to un delivered task'];
            }
            if ($delivery_task->client_recieve_delivery != true) {
                return ['error' => 'cant pay to task before client recieve the delivery'];
            }
            $driver_id = $delivery_task->driver_id;
            $driver = \App\Models\Employee::findOrFail($driver_id);
            $employee = $driver;
            $amount = $delivery_task->delivery_fee;
            if ($amount <= 0) {
                return ['error' => 'This delivery task does not have a delivery fee set, payment cannot be processed'];
            }
            if ($delivery_task->currency === 'USD') {
                $amount = $amount * 1.35;  // convert to new syria pounds
            } else {
                $amount = $amount / 100;  // convert to new syria pounds
            }
        } elseif ($request->task_type == 'project_task') {
            $project_task = \App\Models\Project_task::findOrFail($task_id);
            $task = $project_task;
            if (!$project_task) {
                return ['error' => 'Project task not found'];
            }
            if ($project_task->task_status != 'completed') {
                return ['error' => 'cant pay to un completed task'];
            }
            if ($project_task->client_recieve_task != true) {
                return ['error' => 'cant pay to task before client recieve the delivery'];
            }
            $employee_id = $project_task->employee_id;
            $employee = \App\Models\Employee::findOrFail($employee_id);
            $amount = $project_task->task_fee;
            if ($amount <= 0) {
                return ['error' => 'This project task does not have a task fee set, payment cannot be processed'];
            }
            if ($project_task->currency === 'USD') {
                $amount = $amount * 1.35;  // convert to new syria pounds
            } else {
                $amount = $amount / 100;  // convert to new syria pounds
            }
        }
        if ($request->payment_method !== 'syriatel_cash' && $request->payment_method !== 'shamcash' && $request->payment_method !== 'cash') {
            return ['error' => 'Unsupported payment method'];
        }
        if ($request->payment_method === 'syriatel_cash') {
            $toGsm = $employee->syriatel_cash_phone;
            if (!$toGsm) {
                return ['error' => 'Syriatel beneficiary phone is not configured on target account'];
            }
            $paymentResponse = $this->apiSyriaService->transferCash(
                $request->gsm,
                $toGsm,
                $amount,
                $request->pin_code
            );
        } elseif ($request->payment_method === 'shamcash') {
            $toAccountAddress = $employee->account_number;
            if (!$toAccountAddress) {
                return ['error' => 'ShamCash beneficiary account address is not configured on target account'];
            }
            if (!$request->account_address) {
                return ['error' => 'Your ShamCash account address is required for payment verification'];
            }
            $verificationResult = $this->apiSyriaService->verifyShamcashPaymentFromLogs(
                $toAccountAddress,
                $amount,
                $request->account_address
            );
            if (!$verificationResult['success']) {
                return ['error' => $verificationResult['message']];
            }
            $paymentResponse = [
                'success' => true,
                'message' => 'ShamCash payment verified from logs',
                'data' => $verificationResult['matched_log'] ?? null,
            ];
        } elseif ($request->payment_method === 'cash') {
            $paymentResponse = [
                'success' => true,
                'message' => 'Cash payment selected, please confirm with the driver that the payment has been made',
                'data' => 'null'
            ];
        } else {
            return ['error' => 'Unsupported payment method'];
        }
        if (!$paymentResponse['success']) {
            return ['error' => $paymentResponse['message']];
        }

        return $this->solarCompanyManagerRepositoryInterface->paid_to_employee($request, $task, $company, $amount, $paymentResponse);
    }

    public function solar_system_offers($request, array $data = [])
    {
        $company_manager_id = Auth::guard('company_manager')->user()->id;
        $company = Solar_company_manager::findOrFail($company_manager_id)->solarCompanies()->first();

        if (!$company) {
            return ['error' => 'company not found for the current manager'];
        }

        return DB::transaction(function () use ($request, $data, $company) {
            $products = collect($data['products'] ?? []);
            $productsMap = Products::whereIn('id', $products->pluck('id')->all())->get()->keyBy('id');
            $subtotalAmount = 0;
            foreach ($products as $item) {
                $product = $productsMap->get($item['id']);
                if (!$product) {
                    continue;
                }
                $unitPrice = (float) $product->price;
                if ($product->currency === 'USD') {
                    $unitPrice *= 1.35;
                } else {
                    $unitPrice /= 100;
                }
                $quantity = (int) $item['quantity'];
                $lineSubTotal = $unitPrice * $quantity;
                if ($product->disscount_type === 'percentage') {
                    $discount = ((float) $product->disscount_value / 100) * $lineSubTotal;
                } else {
                    $discount = (float) $product->disscount_value * $quantity;
                }
                $subtotalAmount += max($lineSubTotal - $discount, 0);
            }
            $offerLevelDiscountAmount = 0;
            if (($data['discount_type'] ?? 'fixed') === 'percentage') {
                $offerLevelDiscountAmount = ($subtotalAmount * (float) ($data['discount_value'] ?? 0)) / 100;
            } else {
                $offerLevelDiscountAmount = (float) ($data['discount_value'] ?? 0);
            }
            $totalAmount = max(
                $subtotalAmount
                    - $offerLevelDiscountAmount
                    + (float) ($data['average_delivery_cost'] ?? 0)
                    + (float) ($data['average_installation_cost'] ?? 0)
                    + (float) ($data['average_metal_installation_cost'] ?? 0),
                0
            );
            $panarImages = [];
            if ($request->hasFile('panar_image')) {
                foreach ((array) $request->file('panar_image') as $image) {
                    $originalName = $image->getClientOriginalName();
                    $path = $image->storeAs('CompanyManager/offers/images', $originalName, 'public');
                    $panarImages[] = $path;
                }
            }
            $videoPath = null;
            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $originalName = $video->getClientOriginalName();
                $videoPath = $video->storeAs('CompanyManager/offers/videos', $originalName, 'public');
            }
            $offerExpiredDate = $data['offer_expired_date'] ?? null;
            if (!$offerExpiredDate && !empty($data['offer_date']) && !empty($data['validity_days'])) {
                $offerExpiredDate = now()->parse($data['offer_date'])->addDays((int) $data['validity_days'])->toDateString();
            }
            $offer = $company->offers()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'offer_name' => $data['offer_name'] ?? null,
                'offer_details' => $data['offer_details'] ?? null,
                'system_type' => $data['system_type'] ?? 'off_grid',
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $offerLevelDiscountAmount,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'average_total_amount' => $totalAmount,
                'currency' => $data['currency'] ?? 'SY',
                'validity_days' => $data['validity_days'] ?? 0,
                'average_delivery_cost' => $data['average_delivery_cost'] ?? 0,
                'average_installation_cost' => $data['average_installation_cost'] ?? 0,
                'average_metal_installation_cost' => $data['average_metal_installation_cost'] ?? 0,
                'status_reply' => 'pending',
                'offer_available' => true,
                'panar_image' => $panarImages,
                'video' => $videoPath,
                'public_private' => $data['public_private'] ?? 'private',
                'offer_date' => $data['offer_date'] ?? now()->toDateString(),
                'offer_expired_date' => $offerExpiredDate,
            ]);
            foreach ($products as $item) {
                $product = $productsMap->get($item['id']);
                if (!$product) {
                    continue;
                }
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price;
                $lineSubTotal = $unitPrice * $quantity;

                $unitDiscountAmount = (float) ($product->disscount_value ?? 0);
                $lineDiscount = $product->disscount_type === 'percentage'
                    ? (($unitDiscountAmount / 100) * $lineSubTotal)
                    : ($unitDiscountAmount * $quantity);

                $offer->Items()->create([
                    'product_id' => $product->id,
                    'item_name_snapshot' => $product->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => max($lineSubTotal - $lineDiscount, 0),
                    'unit_discount_amount' => $unitDiscountAmount,
                    'total_discount_amount' => $lineDiscount,
                    'discount_type' => $product->disscount_type,
                    'currency' => $product->currency,
                ]);
            }
            $panarImagesUrl= array_map(function ($path) {
                return asset('storage/' . $path);
            }, $panarImages);
            $videoUrl = $videoPath ? asset('storage/' . $videoPath) : null;
            return [
                $offer->load('Items','Items.product'),

                $panarImagesUrl,
                $videoUrl,
            ];
        });
    }
}
