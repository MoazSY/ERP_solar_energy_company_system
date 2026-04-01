<?php
namespace App\Services;

use App\Models\Agency;
use App\Models\Solar_company;
use App\Models\System_admin;
use App\Repositories\SystemAdminRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SystemAdminService{
    protected $SystemAdminRepositoryInterface;
    protected $tokenRepositoryInterface;
    public function __construct(SystemAdminRepositoryInterface $systemAdminRepositoryInterface,TokenRepositoryInterface $tokenRepositoryInterface)
    {
    $this->SystemAdminRepositoryInterface=$systemAdminRepositoryInterface;
    $this->tokenRepositoryInterface=$tokenRepositoryInterface;
    }
    public function register($request,$data){
        if($request->hasFile('image')){
            $image=$request->file('image')->getClientOriginalName();
            $imagepath=$request->file('image')->storeAs('SystemAdmin/images',$image,'public');
            $admin=$this->SystemAdminRepositoryInterface->Create($request,$imagepath,$data);
            $imageUrl=asset('storage/' . $imagepath);
        }
        else{
            $admin=$this->SystemAdminRepositoryInterface->Create($request,null,$data);
            $imageUrl=null;
        }
        $token=$admin->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token=$this->tokenRepositoryInterface->Add_refresh_token($token);
         return ['admin'=>$admin,'token'=>$token,'refresh_token'=>$refresh_token,'imageUrl'=>$imageUrl];

    }
    public function Admin_profile(){
    $admin=Auth::guard('admin')->user();
    $profile=$this->SystemAdminRepositoryInterface->Admin_profile($admin->id);
     $image=$profile->image;
         if($image==null)
            $imageUrl=null;
         else
         $imageUrl=asset('storage/'.$image);
         return ['admin'=>$profile,'imageUrl'=>$imageUrl];
    }
    public function update_profile($request,$data){
        $admin_id=Auth::guard('admin')->user()->id;
        $admin=System_admin::findOrFail($admin_id);
        if($request->hasFile('image')){
            $originalName=$request->file('image')->getClientOriginalName();
            $path=$request->file('image')->storeAs('admin/images',$originalName,'public');
            $data['image']=$path;
            $imageUrl = asset('storage/' . $path);
        }else{
        if($admin->image==null){
        $imageUrl=null;
            }
            else{
            $imageUrl = asset('storage/' .$admin->image);
            }
        }
        if(!empty($data['password'])){
        $data['password']=Hash::make($data['password']);
        }
        $admin->update($data);
        $admin->fresh();
        $admin->save();
        return [$admin,$imageUrl];
    }
    public function add_governorates($request){
        $governorates=$this->SystemAdminRepositoryInterface->add_governorates($request);
        return $governorates;
    }
    public function add_area($request,$governorate){
        $area=$this->SystemAdminRepositoryInterface->add_area($request,$governorate);
        return $area;
    }
    public function add_neighborhoods($request,$area){
        $neighborhoods=$this->SystemAdminRepositoryInterface->add_neighborhoods($request,$area);
        return $neighborhoods;
    }
    public function get_governorates(){
        return $this->SystemAdminRepositoryInterface->get_governorates();
    }
    public function get_areas($governorate){
        return $this->SystemAdminRepositoryInterface->get_areas($governorate);
    }
    public function get_neighborhoods($area){
        return $this->SystemAdminRepositoryInterface->get_neighborhoods($area);
    }
    public function UnActive_company(){
        $companies= $this->SystemAdminRepositoryInterface->UnActive_company();
        $result = $companies->map(fn($company) => [
        'company_logo' => $company->company_logo != null 
        ? asset('storage/' . $company->company_logo) 
        : null,
        'un_active_company' => $company
        ]);
        return $result;
    }
    public function proccess_company_register($request){
        $admin=System_admin::findOrFail(Auth::guard('admin')->user()->id);
        if($request->entity_type=='solar_company'){
        $solar_company=Solar_company::findOrFail($request->entity_id);
        return $this->SystemAdminRepositoryInterface->proccess_company_register($request,$admin,$solar_company);
        }
        else
            $agency=Agency::findOrFail($request->entity_id);
        return $this->SystemAdminRepositoryInterface->proccess_company_register($request,$admin,$agency);
        
    }
}
