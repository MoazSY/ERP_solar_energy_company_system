<?php

use App\Http\Controllers\AgencyManagerController;
use App\Http\Controllers\ApiSyriaToolsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SolarCompanyManager;
use App\Http\Controllers\System_admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('Send_verify_code/{otp_type}', [OtpController::class, 'sendOtp']);
Route::post('verify_code/{otp_type}', [OtpController::class, 'verifyOtp']);
Route::post('Refresh_token', [OtpController::class, 'Refresh_token']);
Route::post('admin_register', [System_admin::class, 'Register']);
Route::post('company_manager_register', [SolarCompanyManager::class, 'Register']);
Route::post('agency_manager_register', [AgencyManagerController::class, 'Register']);
Route::get('get_governorates', [System_admin::class, 'get_governorates']);
Route::get('get_areas/{governorates}', [System_admin::class, 'get_areas']);
Route::get('get_neighborhoods/{area}', [System_admin::class, 'get_neighborhoods']);
Route::get('show_subscribtions_policies', [System_admin::class, 'show_subscribtions_policies']);
Route::post('login', [OtpController::class, 'login']);
Route::get('show_all_company_registerd', [System_admin::class, 'show_all_company_registerd']);
Route::get('show_all_agency_registerd', [System_admin::class, 'show_all_agency_registerd']);
Route::post('customer_register', [CustomerController::class, 'Register']);
Route::post('logout', [OtpController::class, 'logout'])->middleware('auth:sanctum');
Route::post('filter_employee', [EmployeeController::class, 'filter_employee']);
Route::post('filter_agency', [SolarCompanyManager::class, 'filter_agency']);
Route::post('filter_solar_companies', [AgencyManagerController::class, 'filter_solar_companies']);

Route::middleware('check_admin')->group(function () {
    Route::get('Admin_profile', [System_admin::class, 'Admin_profile']);
    Route::post('admin/update_profile', [System_admin::class, 'update_profile']);
    Route::post('Add_governorates', [System_admin::class, 'Add_governorates']);
    Route::post('Add_area/{governorates}', [System_admin::class, 'Add_area']);
    Route::post('add_neighborhoods/{area}', [System_admin::class, 'add_neighborhoods']);
    Route::get('get_UnActive_company', [System_admin::class, 'get_UnActive_company']);
    Route::get('get_UnActive_agency', [System_admin::class, 'get_UnActive_agency']);
    Route::post('proccess_company_register', [System_admin::class, 'proccess_company_register']);
    Route::post('subscriptions_policy', [System_admin::class, 'subscriptions_policy']);
    Route::post('update_subscriptions_policy/{subscribe_polices}', [System_admin::class, 'update_subscriptions_policy']);
    Route::post('custom_subscribe_policy', [System_admin::class, 'custom_subscribe_policy']);
    Route::get('show_custom_subscribtions_policies', [System_admin::class, 'show_custom_subscribtions_policies']);
    Route::post('show_subscribtions_policies_for_entity', [System_admin::class, 'show_subscribtions_policies_for_entity']);
    Route::get('show_subscribers_of_policy/{policy}', [System_admin::class, 'show_subscribers_of_policy']);
});

Route::middleware('check_company_manager')->group(function () {
    Route::get('company_manager_profile', [SolarCompanyManager::class, 'company_manager_profile']);
    Route::post('Company_register', [SolarCompanyManager::class, 'Company_register']);
    Route::post('company_manager/update_profile', [SolarCompanyManager::class, 'update_profile']);
    Route::post('Update_company/{solarCompany}', [SolarCompanyManager::class, 'Update_company']);
    Route::post('Add_company_address/{solarCompany}', [SolarCompanyManager::class, 'Add_company_address']);
    Route::post('company_subscribe_in_policy', [SolarCompanyManager::class, 'subscribe_in_policy'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('show_company_products', [SolarCompanyManager::class, 'show_company_products']);
    Route::get('show_all_agency', [SolarCompanyManager::class, 'show_all_agency']);
    Route::get('show_agency_products/{agency_id}', [SolarCompanyManager::class, 'show_agency_products']);
    Route::post('request_purchase_invoice_agency/{agency_id}', [SolarCompanyManager::class, 'request_purchase_invoice_agency']);
    Route::get('get_purchase_requests_from_agencies', [SolarCompanyManager::class, 'get_purchase_requests_from_agencies']);
    Route::post('company_manager/assign_delivery_task', [SolarCompanyManager::class, 'assign_delivery_task'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('company_manager/show_delivery_task', [SolarCompanyManager::class, 'show_delivery_task'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('company_manager/filter_delivery_tasks', [SolarCompanyManager::class, 'filter_delivery_tasks'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('company_manager/delivery_rules', [SolarCompanyManager::class, 'delivery_rules'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('company_manager/show_delivery_rules', [SolarCompanyManager::class, 'show_delivery_rules'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('company_manager/update_delivery_rule/{rule_id}', [SolarCompanyManager::class, 'update_delivery_rule'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('company_manager/delete_delivery_rule/{rule_id}', [SolarCompanyManager::class, 'delete_delivery_rule'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('recieve_orderList/{orderList}', [SolarCompanyManager::class, 'recieve_orderList'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('paid_to_employee/{task_id}', [SolarCompanyManager::class, 'paid_to_employee'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('solar_system_offers', [SolarCompanyManager::class, 'solar_system_offers'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('show_company_offers', [SolarCompanyManager::class, 'show_company_offers'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('show_subscribers_in_offer/{offer_id}', [SolarCompanyManager::class, 'show_subscribers_in_offer'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('update_company_offer/{offer_id}', [SolarCompanyManager::class, 'update_company_offer'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::post('delete_company_offer/{offer_id}', [SolarCompanyManager::class, 'delete_company_offer'])->middleware(['check_company_manager_active', 'check_company_active']);
    Route::get('show_customer_requests', [SolarCompanyManager::class, 'show_customer_requests'])->middleware(['check_company_manager_active', 'check_company_active']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('api_status', [ApiSyriaToolsController::class, 'api_status']);
    Route::get('api_accounts', [ApiSyriaToolsController::class, 'api_accounts']);
    Route::post('shamcash_balance', [ApiSyriaToolsController::class, 'shamcash_balance']);
    Route::post('shamcash_logs', [ApiSyriaToolsController::class, 'shamcash_logs']);
    Route::post('shamcash_find_transaction', [ApiSyriaToolsController::class, 'shamcash_find_transaction']);
    Route::get('show_custom_subscriptions', [SolarCompanyManager::class, 'show_custom_subscriptions']);
    Route::post('register_employee_company_agency', [EmployeeController::class, 'register_employee_company_agency']);
    Route::post('register_employee', [EmployeeController::class, 'register_employee']);
    Route::get('show_entity_employees', [EmployeeController::class, 'show_entity_employees']);
});

Route::middleware('check_Agency_manager')->group(function () {
    Route::get('agency_manager_profile', [AgencyManagerController::class, 'agency_manager_profile']);
    Route::post('Agency_register', [AgencyManagerController::class, 'Agency_register']);
    Route::post('agency_manager/update_profile', [AgencyManagerController::class, 'update_profile']);
    Route::post('Update_agency/{agency}', [AgencyManagerController::class, 'Update_agency']);
    Route::post('Add_agency_address/{agency}', [AgencyManagerController::class, 'Add_agency_address']);
    Route::post('agency_subscribe_in_policy', [AgencyManagerController::class, 'subscribe_in_policy'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('add_agency_products', [AgencyManagerController::class, 'add_agency_products'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('add_agency_product_battery/{product_id}', [AgencyManagerController::class, 'add_agency_product_battery'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('add_agency_product_inverter/{product_id}', [AgencyManagerController::class, 'add_agency_product_inverter'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('add_agency_product_solar_panel/{product_id}', [AgencyManagerController::class, 'add_agency_product_solar_panel'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::get('show_agency_products', [AgencyManagerController::class, 'show_agency_products'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('update_agency_product/{product_id}', [AgencyManagerController::class, 'update_agency_product'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('delete_agency_product/{product_id}', [AgencyManagerController::class, 'delete_agency_product'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('delete_agency_product_details/{product_id}', [AgencyManagerController::class, 'delete_agency_product_details'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('filter_agency_products', [AgencyManagerController::class, 'filter_agency_products'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('create_custom_discount/{solar_company_id}', [AgencyManagerController::class, 'create_custom_discount'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::get('show_custom_discounts/{solar_company_id}', [AgencyManagerController::class, 'show_custom_discounts'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('update_custom_discount/{discount_id}', [AgencyManagerController::class, 'update_custom_discount'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('delete_custom_discount/{discount_id}', [AgencyManagerController::class, 'delete_custom_discount'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::get('get_all_custom_discounts_grouped_by_company', [AgencyManagerController::class, 'get_all_custom_discounts_grouped_by_company'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::get('get_purchase_requests_from_companies', [AgencyManagerController::class, 'get_purchase_requests_from_companies'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('create_purchase_invoice', [AgencyManagerController::class, 'create_purchase_invoice'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('delivery_rules', [AgencyManagerController::class, 'deliviry_rules'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::get('show_delivery_rules', [AgencyManagerController::class, 'show_delivery_rules'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('update_delivery_rule/{rule_id}', [AgencyManagerController::class, 'update_delivery_rule'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('delete_delivery_rule/{rule_id}', [AgencyManagerController::class, 'delete_delivery_rule'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('assign_delivery_task', [AgencyManagerController::class, 'assign_delivery_task'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::get('Agency_manager/show_delivery_tasks', [AgencyManagerController::class, 'show_delivery_tasks'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('Agency_manager/filter_delivery_tasks', [AgencyManagerController::class, 'filter_delivery_tasks'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('paid_to_driver/{task_id}', [AgencyManagerController::class, 'paid_to_driver'])->middleware(['check_agency_manager_active', 'check_agency_active']);
});

Route::middleware('check_employee')->group(function () {
    Route::get('employee_profile', [EmployeeController::class, 'employee_profile']);
    Route::post('employee/update_profile', [EmployeeController::class, 'update_profile']);
    Route::get('show_delivery_tasks', [EmployeeController::class, 'show_delivery_tasks']);
    Route::post('proccess_delivery_task', [EmployeeController::class, 'proccess_delivery_task']);
    Route::post('deliver_orderList', [EmployeeController::class, 'deliver_orderList']);
    Route::post('task_start', [EmployeeController::class, 'delivery_task_start']);
    Route::get('show_orderList_for_inventory_manager', [EmployeeController::class, 'show_input_orderList_for_inventory_manager']);

    // Inventory Management Routes
    Route::post('insert_product_to_stock', [EmployeeController::class, 'insert_product_to_stock']);
    Route::get('show_inventory_products', [EmployeeController::class, 'show_inventory_products']);
    Route::post('add_inventory_product_battery/{product_id}', [EmployeeController::class, 'add_inventory_product_battery']);
    Route::post('add_inventory_product_inverter/{product_id}', [EmployeeController::class, 'add_inventory_product_inverter']);
    Route::post('add_inventory_product_solar_panel/{product_id}', [EmployeeController::class, 'add_inventory_product_solar_panel']);
    Route::post('update_inventory_product/{product_id}', [EmployeeController::class, 'update_inventory_product']);
    Route::post('delete_inventory_product/{product_id}', [EmployeeController::class, 'delete_inventory_product']);
    Route::post('delete_inventory_product_details/{product_id}', [EmployeeController::class, 'delete_inventory_product_details']);
    Route::post('filter_inventory_products', [EmployeeController::class, 'filter_inventory_products']);
    Route::post('recieve_cash_from_manager', [EmployeeController::class, 'recieve_cash_from_manager']);
});

Route::middleware('check_customer')->group(function () {
    Route::get('customer_profile', [CustomerController::class, 'customer_profile']);
    Route::post('customer/update_profile', [CustomerController::class, 'update_profile']);
    Route::post('add_customer_address', [CustomerController::class, 'add_customer_address']);
    Route::get('company_offers/{company_id}', [CustomerController::class, 'show_company_offers']);
    Route::get('customer_specific_offers', [CustomerController::class, 'show_my_specific_offers']);
    Route::post('subscribe_offer/{offer_id}', [CustomerController::class, 'subscribe_offer']);
    Route::post('unsubscribe_offer/{offer_id}', [CustomerController::class, 'unsubscribe_offer']);
    Route::get('my_subscribe_offers', [CustomerController::class, 'show_subscribe_offers']);
    Route::post('request_solar_system', [CustomerController::class, 'request_solar_system']);
    Route::post('request_solar_system/electrical_devices', [CustomerController::class, 'add_electrical_devices_to_request_solar_system']);
    Route::get('electrical_devices', [CustomerController::class, 'electrical_devices']);
    Route::post('request_technical_inspection', [CustomerController::class, 'request_technical_inspection']);
    Route::get('my_requests', [CustomerController::class, 'show_my_requests']);
    Route::get('my_solar_systems', [CustomerController::class, 'show_my_solar_systems']);
    // Route::post('filter_requests', [CustomerController::class, 'filter_requests']);
    Route::post('cancel_solar_system_request/{request_id}', [CustomerController::class, 'cancel_solar_system_request']);
    Route::post('filter_company_products/{company_id}', [CustomerController::class, 'filter_company_products']);
    // Route::post('update_solar_system_request/{request_id}', [CustomerController::class, 'update_solar_system_request']);
    Route::post('request_products_order/{company_id}', [CustomerController::class, 'request_products_order']);
    Route::get('invoices_details', [CustomerController::class, 'show_invoices_details']);
    Route::post('approve_pay_invoice/{invoice_id}', [CustomerController::class, 'approve_pay_invoice']);
    Route::post('recieve_invoice/{invoice_id}', [CustomerController::class, 'recieve_invoice']);
    Route::get('installations_services_status', [CustomerController::class, 'show_installations_services_status']);
    Route::post('pay_for_additional_consumables/{installation_id}', [CustomerController::class, 'pay_for_additional_consumables']);
    Route::post('technical_employee_rating/{installation_id}', [CustomerController::class, 'technical_employee_rating']);
    Route::post('task_feedsback/{task_id}', [CustomerController::class, 'task_feedsback']);
    Route::post('company_feedsback/{company_id}', [CustomerController::class, 'company_feedsback']);
    Route::post('company_rating/{company_id}', [CustomerController::class, 'company_rating']);
    Route::get('company_gallary/{company_id}', [CustomerController::class, 'show_company_gallary']);
    Route::get('requested_products_orders', [CustomerController::class, 'show_requested_products_orders']);
    Route::post('request_maintenance_service', [CustomerController::class, 'request_maintenance_service']);
    Route::get('my_maintenance_requests', [CustomerController::class, 'show_my_maintenance_requests']);
    Route::post('cancel_maintenance_request/{request_id}', [CustomerController::class, 'cancel_maintenance_request']);
    Route::post('update_maintenance_request/{request_id}', [CustomerController::class, 'update_maintenance_request']);
    Route::post('recieve_maintenance_service/{request_id}', [CustomerController::class, 'recieve_maintenance_service']);
    Route::post('simulation_solar_system_finacial_savings', [CustomerController::class, 'simulation_solar_system_finacial_savings']);
    Route::post('company_report/{company_id}', [CustomerController::class, 'company_report']);
});
