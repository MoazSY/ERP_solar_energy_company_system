<?php
namespace App\Services;

use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use App\Repositories\SolarCompanyManagerRepositoryInterface;
use App\Repositories\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SolarCompanyManagerService{
    protected $solarCompanyManagerRepositoryInterface;
    protected $tokenRepositoryInterface;
public function __construct(SolarCompanyManagerRepositoryInterface $solarCompanyManagerRepositoryInterface, TokenRepositoryInterface $tokenRepositoryInterface)
{
$this->solarCompanyManagerRepositoryInterface=$solarCompanyManagerRepositoryInterface;
$this->tokenRepositoryInterface=$tokenRepositoryInterface;
}
    public function Register($request,$data){
        $identification_image=$request->file('identification_image')->getClientOriginalName();
        $identification_image_path=$request->file('identification_image')->storeAs('CompanyManager/identification_image',$identification_image,'public');
        $identification_image_URL=asset('storage/' . $identification_image_path);

        if($request->hasFile('image')){
            $image=$request->file('image')->getClientOriginalName();
            $imagepath=$request->file('image')->storeAs('CompanyManager/images',$image,'public');
            $company_mamager=$this->solarCompanyManagerRepositoryInterface->Create($request,$imagepath,$identification_image_path,$data);
            $imageUrl=asset('storage/' . $imagepath);
        }
        else{
            $company_mamager=$this->solarCompanyManagerRepositoryInterface->Create($request,null,$identification_image_path,$data);
            $imageUrl=null;
        }
        $token=$company_mamager->createToken('authToken')->plainTextToken;
        $this->tokenRepositoryInterface->Add_expierd_token($token);
        $refresh_token=$this->tokenRepositoryInterface->Add_refresh_token($token);
         return ['company_manager'=>$company_mamager,'token'=>$token,'refresh_token'=>$refresh_token,'imageUrl'=>$imageUrl,'identification_image_URL'=>$identification_image_URL];
    }
    public function company_manager_profile(){
    $company_manager=Auth::guard('company_manager')->user();
    $profile=$this->solarCompanyManagerRepositoryInterface->company_manager_profile($company_manager->id);
     $image=$profile[0]->image;
     $company_logo=$profile[1]->company_logo;
     $identification_image=$profile[0]->identification_image;
     if($company_logo==null){
        $company_logoUrl=null;
     }else{
         $company_logoUrl=asset('storage/'.$company_logo);
     }
     if($identification_image==null){
            $identification_imageUrl=null;
     }else{
         $identification_imageUrl=asset('storage/'.$identification_image);

     }
         if($image==null)
            $imageUrl=null;
         else
         $imageUrl=asset('storage/'.$image);

         $company_logoUrl=asset('storage/'.$company_logo);
         $company_logoUrl=asset('storage/'.$company_logo);
         return ['company_manager'=>$profile[0],'solar_company'=>$profile[1],'imageUrl'=>$imageUrl,'identification_imageUrl'=>$identification_imageUrl,'company_logoUrl'=>$company_logoUrl];
    }
        public function update_profile($request,$data){
        $company_manager_id=Auth::guard('company_manager')->user()->id;
        $company_manager=Solar_company_manager::findOrFail($company_manager_id);
        
        if($request->hasFile('identification_image')){
             $originalName=$request->file('identification_image')->getClientOriginalName();
            $path=$request->file('identification_image')->storeAs('CompanyManager/identification_image',$originalName,'public');
            $data['identification_image']=$path;
            $imageUrl = asset('storage/' . $path);
            $company_manager->Activate_Account=false;
            $company_manager->save();
        }else{
        if($company_manager->identification_image==null){
        $imageUrl=null;
            }
            else{
            $imageUrl = asset('storage/' .$company_manager->identification_image);
            }
        }
        if($request->hasFile('image')){
            $originalName=$request->file('image')->getClientOriginalName();
            $path=$request->file('image')->storeAs('CompanyManager/images',$originalName,'public');
            $data['image']=$path;
            $imageUrl = asset('storage/' . $path);
        }else{
        if($company_manager->image==null){
        $imageUrl=null;
            }
            else{
            $imageUrl = asset('storage/' .$company_manager->image);
            }
        }
        if(!empty($data['password'])){
        $data['password']=Hash::make($data['password']);
        }
        $company_manager->update($data);
        $company_manager->fresh();
        $company_manager->save();
        return [$company_manager,$imageUrl];
    }


    public function Company_register($request,$data){
        $company_manager_id=Auth::guard('admin')->user()->id;
        $company_mamager=Solar_company_manager::findOrFail($company_manager_id);
        if($request->hasFile('company_logo')){
        $company_logo=$request->file('company_logo')->getClientOriginalName();
        $company_logo_path=$request->file('company_logo')->storeAs('CompanyManager/company_logo',$company_logo,'public');
        $company_logo_URL=asset('storage/' .$company_logo_path);
        $solarCompany=$this->solarCompanyManagerRepositoryInterface->Company_register($request,$data,$company_mamager,$company_logo_path);
        }else{
        $solarCompany=$this->solarCompanyManagerRepositoryInterface->Company_register($request,$data,$company_mamager,null);
        $company_logo_URL=null;
        }
        return ['solarCompany'=>$solarCompany,'companyLogo'=>$company_logo_URL];
    }
    public function update_company($request,$data,$solarCompany){
        $solarCompany=Solar_company::findOrFail($solarCompany->id);
        if($request->file('company_logo')){
        $company_logo=$request->file('company_logo')->getClientOriginalName();
        $company_logo_path=$request->file('company_logo')->storeAs('CompanyManager/company_logo',$company_logo,'public');
        $data['company_logo']=$company_logo_path;
        $company_logo_URL=asset('storage/' .$company_logo_path);
        }else{
        if($solarCompany->company_logo==null){
        $company_logo_URL=null;
        }
        else{
        $company_logo_URL=asset('storage/' .$solarCompany->company_logo);
        }
        }
        if($request->commerical_register_number){
        $solarCompany->company_status='pending';
        $solarCompany->save();
        }
        $solarCompany->update($data);
        $solarCompany->fresh();
        $solarCompany->save();
        return [$solarCompany,$company_logo_URL];
 
    }
    public function company_address($request,$solarCompany){
        $company_address=$this->solarCompanyManagerRepositoryInterface->company_address($request,$solarCompany);
        return $company_address;
    }
}
