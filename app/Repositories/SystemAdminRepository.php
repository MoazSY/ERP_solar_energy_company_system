<?php
namespace App\Repositories;

use App\Models\Areas;
use App\Models\Governorates;
use App\Models\Neighborhood;
use App\Models\Solar_company;
use App\Models\System_admin;
use Illuminate\Support\Facades\Hash;

class SystemAdminRepository implements SystemAdminRepositoryInterface{
    public function Create($request,$imagepath,$data){
        $admin=System_admin::create([
        'first_name'=>$request->first_name,
        'last_name'=>$request->last_name,
        'date_of_birth' => $request->date_of_birth,
        'email' => $data['email'],
        'password'=>Hash::make($request->password),
        'phoneNumber' => $data['phoneNumber'],
        'account_number'=>$request->account_number,
        'image'=>$imagepath,
        'about_him'=>$request->about_him,
        ]);
        return $admin;
    }
    public function Admin_profile($admin_id)
    {
        $profile=System_admin::findOrFail($admin_id);
        return $profile;
    }
    public function add_governorates($request){
        $governorates=Governorates::create([
            'name'=>$request->name
        ]);
        return $governorates;
    }
    public function add_area($request,$governorate){
        $governorate=Governorates::findOrFail($governorate->id);
        $governorate->areas()->createMany($request->area);
        return $governorate->areas;
    }
    public function add_neighborhoods($request,$area){
        $area=Areas::findOrFail($area->id);
        $area->neighborhoods()->createMany($request->neighborhoods);
        return $area->neighborhoods;
    }
    public function get_governorates(){
        $governorates=Governorates::all();
        return $governorates;
    }
    public function get_areas($governorate)
    {
        $areas=Areas::where('governorate_id','=',$governorate->id)->get();
        return $areas;
    }
    public function get_neighborhoods($area)
    {
        $neighborhoods=Neighborhood::where('area_id','=',$area->id)->get();
        return $neighborhoods;
    }
    public function unActive_company()
    {
        $UnActiveCompany=Solar_company::whereNot('company_status','active')->get();
        return $UnActiveCompany;
    }
    public function proccess_company_register($request,$admin,$entity){
       $proccess_result= $entity->proccess_register()->create([
            'admin_id'=>$admin->id,
            'status'=>$request->status,
            'rejection_reason'=>$request->rejection_reason
        ]);
        return $proccess_result;
    }
}
