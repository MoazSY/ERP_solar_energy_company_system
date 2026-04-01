<?php

use App\Http\Controllers\OtpController;
use App\Http\Controllers\SolarCompanyManager ;
use App\Http\Controllers\System_admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('Send_verify_code/{otp_type}',[OtpController::class,'sendOtp']);
Route::post('verify_code/{otp_type}',[OtpController::class,'verifyOtp']);
Route::post('Refresh_token',[OtpController::class,'Refresh_token']);
Route::post('login',[OtpController::class,'login']);
Route::post('logout',[OtpController::class,'logout'])->middleware('auth:sanctum');

Route::post('admin_register',[System_admin::class,'Register']);
Route::get('Admin_profile',[System_admin::class,'Admin_profile']);
Route::post('update_profile',[System_admin::class,'update_profile'])->middleware('auth:sanctum');
Route::post('Add_governorates',[System_admin::class,'Add_governorates']);
Route::post('Add_area/{governorates}',[System_admin::class,'Add_area']);
Route::post('add_neighborhoods/{area}',[System_admin::class,'add_neighborhoods']);
Route::get('get_governorates',[System_admin::class,'get_governorates']);
Route::get('get_areas/{governorates}',[System_admin::class,'get_areas']);
Route::get('get_neighborhoods/{area}',[System_admin::class,'get_neighborhoods']);
Route::get('get_UnActive_company',[System_admin::class,'get_UnActive_company']);
Route::post('proccess_company_register',[System_admin::class,'proccess_company_register']);


Route::post('company_manager_register',[SolarCompanyManager::class,'Register']);
Route::post('Company_register',[SolarCompanyManager::class,'Company_register']);
Route::get('company_manager_profile',[SolarCompanyManager::class,'company_manager_profile']);
Route::post('update_profile',[SolarCompanyManager::class,'update_profile'])->middleware('auth:sanctum');
Route::post('Update_company/{solarCompany}',[SolarCompanyManager::class,'Update_company']);
Route::post('Add_company_address/{solarCompany}',[SolarCompanyManager::class,'Add_company_address']);

