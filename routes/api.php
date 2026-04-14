<?php

use App\Http\Controllers\AgencyManagerController;
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

Route::post('logout', [OtpController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('check_admin')->group(function () {
    Route::get('Admin_profile', [System_admin::class, 'Admin_profile']);
    Route::post('admin/update_profile', [System_admin::class, 'update_profile']);
    Route::post('Add_governorates', [System_admin::class, 'Add_governorates']);
    Route::post('Add_area/{governorates}', [System_admin::class, 'Add_area']);
    Route::post('add_neighborhoods/{area}', [System_admin::class, 'add_neighborhoods']);
    Route::get('get_UnActive_company', [System_admin::class, 'get_UnActive_company']);
    Route::get('get_UnActive_agency', [System_admin::class, 'get_UnActive_agency']);
    Route::get('show_all_company_registerd/{entity_type}', [System_admin::class, 'show_all_company_registerd']);
    Route::get('show_all_agency_registerd', [System_admin::class, 'show_all_agency_registerd']);
    Route::post('proccess_company_register', [System_admin::class, 'proccess_company_register']);
    Route::post('subscriptions_policy', [System_admin::class, 'subscriptions_policy']);
    Route::post('update_subscriptions_policy/{subscribe_polices}', [System_admin::class, 'update_subscriptions_policy']);
    Route::post('custom_subscribe_policy', [System_admin::class, 'custom_subscribe_policy']);
    Route::get('show_all_company_registerd', [System_admin::class, 'show_all_company_registerd']);
    Route::get('show_all_agency_registerd', [System_admin::class, 'show_all_agency_registerd']);
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
    Route::get('show_all_agency', [SolarCompanyManager::class, 'show_all_agency']);
    Route::post('filter_agency', [SolarCompanyManager::class, 'filter_agency']);
    Route::get('show_agency_products/{agency_id}', [SolarCompanyManager::class, 'show_agency_products']);
});

Route::middleware('check_Agency_manager')->group(function () {
    Route::get('agency_manager_profile', [AgencyManagerController::class, 'agency_manager_profile']);
    Route::post('Agency_register', [AgencyManagerController::class, 'Agency_register']);
    Route::post('agency_manager/update_profile', [AgencyManagerController::class, 'update_profile']);
    Route::post('Update_agency/{agency}', [AgencyManagerController::class, 'Update_agency']);
    Route::post('Add_agency_address/{agency}', [AgencyManagerController::class, 'Add_agency_address']);
    Route::post('agency_subscribe_in_policy', [AgencyManagerController::class, 'subscribe_in_policy'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('add_agency_products', [AgencyManagerController::class, 'add_agency_products'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::get('show_agency_products', [AgencyManagerController::class, 'show_agency_products'])->middleware(['check_agency_manager_active', 'check_agency_active']);
    Route::post('update_agency_product/{product_id}', [AgencyManagerController::class, 'update_agency_product'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
    Route::post('delete_agency_product/{product_id}', [AgencyManagerController::class, 'delete_agency_product'])->middleware(['check_agency_manager_active', 'check_agency_active', 'check_agency_subscription']);
});
