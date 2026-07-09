<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Users\UpdateProfileReqeust;
use App\Http\Requests\Users\StoreDeliveryReqeust;
use App\Http\Requests\Users\UpdateDeliveryReqeust;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function myProfile(){
        $user_id=Auth::id();
        $user=User::with('profile')->where('id',$user_id)->first();
        if(!$user){
            return $this->errorResponse([
                "message"=>"المستخدم غير موجود",
                "statusCode"=>404
            ]);
        }
        $userTargets=$user->userTargets()->with('target')->get();
        $userMonthlyTargets=$user->userMonthlyTargets()->with('monthlyTarget')->get();
        $user->userTargets=$userTargets;
        $user->userMonthlyTargets=$userMonthlyTargets;
        return $this->successResponse([
            "message"=>"تم جلب بيانات المستخدم بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function updateProfile(UpdateProfileReqeust $request){
        $validatedData=$request->validated();
        $user=Auth::user();
        $user->update([
            'name'=>$validatedData['name'],
        ]);
        $user->profile()->update([
            'latitude'=>$validatedData['latitude'],
            'longitude'=>$validatedData['longitude'],
            'shop_name'=>$validatedData['shop_name'],
            'address'=>$validatedData['address'],
        ]);
        $user->load('profile');
        return $this->successResponse([
            "message"=>"تم تحديث بيانات المستخدم بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function showCustomers()
    {
        $users=User::with('profile')->where('role','customer')->orderBy('created_at','desc')->cursorPaginate(30);
        return $this->successResponse([
            "message"=>"تم جلب بيانات العملاء بنجاح",
            "data"=>$users,
            "statusCode"=>200
        ]);
    }
    public function showDeliveryBoys()
    {
        $users=User::with('profile')->where('role','delivery')->orderBy('created_at','desc')->cursorPaginate(30);
        return $this->successResponse([
            "message"=>"تم جلب بيانات الديلفري بنجاح",
            "data"=>$users,
            "statusCode"=>200
        ]);
    }

    public function storeDeliveryBoy(StoreDeliveryReqeust $request){
        $validatedData=$request->validated();
        $user=User::create([
            'name'=>$validatedData['name'],
            'phone_number'=>$validatedData['phone_number'],
            'password'=>Hash::make($validatedData['password']),
            'role'=>'delivery',
        ]);
        return $this->successResponse([
            "message"=>"تم اضافة الديلفري بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function updateDeliveryBoy(UpdateDeliveryReqeust $request, $id){
        $validatedData=$request->validated();
        $user=User::where('id',$id)->where('role','delivery')->first();
        if(!$user){
            return $this->errorResponse(
                "المستخدم غير موجود",
                404
            );
        }
        $user->update([
            'name'=>$validatedData['name'],
            'phone_number'=>$validatedData['phone_number'],
            'password'=>isset($validatedData['password'])?Hash::make($validatedData['password']):$user->password,
        ]);
        $user->load('profile');
        return $this->successResponse([
            "message"=>"تم تحديث بيانات الديلفري بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function deleteDeliveryBoy($id){
        $user=User::where('id',$id)->where('role','delivery')->first();
        if(!$user){
            return $this->errorResponse(
                "المستخدم غير موجود",
                404
            );
        }
        $user->delete();
        return $this->successResponse([
            "message"=>"تم حذف الديلفري بنجاح",
            "statusCode"=>200
        ]);

    }

}
