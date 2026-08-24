<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Users\UpdateProfileReqeust;
use App\Http\Requests\Users\StoreDeliveryReqeust;
use App\Http\Requests\Users\UpdateDeliveryReqeust;
use App\Http\Requests\Users\UpdateFcmTokenRequest;
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

    public function showSubAdmins()
    {
        $users=User::with('profile')->where('role','sub_admin')->orderBy('created_at','desc')->cursorPaginate(30);
        return $this->successResponse([
            "message"=>"تم جلب بيانات المسؤولين الفرعيين بنجاح",
            "data"=>$users,
            "statusCode"=>200
        ]);
    }

    public function storeSubAdmin(StoreDeliveryReqeust $request){
        $validatedData=$request->validated();
        $user=User::create([
            'name'=>$validatedData['name'],
            'phone_number'=>$validatedData['phone_number'],
            'password'=>Hash::make($validatedData['password']),
            'role'=>'sub_admin',
        ]);

        $user->profile()->create([
            'fcm_token' => $validatedData['fcm_token'] ?? null,
        ]);

        $user->load('profile');
        return $this->successResponse([
            "message"=>"تم اضافة المسؤول الفرعي بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function updateSubAdmin(UpdateDeliveryReqeust $request, $id){
        $validatedData=$request->validated();
        $user=User::where('id',$id)->where('role','sub_admin')->first();
        if(!$user){
            return $this->errorResponse(
                "المستخدم غير موجود",
                404
            );
        }
        $user->update([
            'password'=>isset($validatedData['password'])?Hash::make($validatedData['password']):$user->password,
        ]);
        $user->load('profile');
        return $this->successResponse([
            "message"=>"تم تحديث بيانات المسؤول الفرعي بنجاح",
            "data"=>$user,
            "statusCode"=>200
        ]);
    }

    public function deleteSubAdmin($id){
        $user=User::where('id',$id)->where('role','sub_admin')->first();
        if(!$user){
            return $this->errorResponse(
                "المستخدم غير موجود",
                404
            );
        }
        $user->delete();
        return $this->successResponse([
            "message"=>"تم حذف المسؤول الفرعي بنجاح",
            "statusCode"=>200
        ]);

    }

    public function updateFcmToken(UpdateFcmTokenRequest $request)
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['fcm_token' => $validatedData['fcm_token']]
        );

        return $this->successResponse([
            "message" => "تم تحديث رمز الجهاز (FCM) بنجاح",
            "statusCode" => 200
        ]);
    }

    public function updateCustomerPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ], [
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور 6 أرقام/حروف على الأقل',
        ]);

        $user = User::where('id', $id)->where('role', 'customer')->first();
        if (!$user) {
            return $this->errorResponse(
                "العميل غير موجود",
                404
            );
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return $this->successResponse([
            "message" => "تم تحديث كلمة مرور العميل بنجاح",
            "statusCode" => 200
        ]);
    }

    public function changeMyPassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|same:confirm_password',
            'confirm_password' => 'required|string',
        ], [
            'old_password.required' => 'كلمة المرور القديمة مطلوبة',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.min' => 'يجب أن تكون كلمة المرور الجديدة 6 أرقام/حروف على الأقل',
            'new_password.same' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين',
            'confirm_password.required' => 'تأكيد كلمة المرور مطلوب',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'كلمة المرور القديمة غير صحيحة',
                'errors' => [
                    'old_password' => ['كلمة المرور القديمة غير صحيحة']
                ]
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return $this->successResponse([
            "message" => "تم تغيير كلمة المرور بنجاح",
            "statusCode" => 200
        ]);
    }

    public function updateMyPhone(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'phone_number' => 'required|string|unique:users,phone_number,' . $user->id,
        ], [
            'phone_number.required' => 'رقم الهاتف مطلوب',
            'phone_number.unique' => 'رقم الهاتف مستخدم بالفعل بحساب آخر',
        ]);

        $user->update([
            'phone_number' => $request->input('phone_number'),
        ]);

        return $this->successResponse([
            "message" => "تم تحديث رقم الهاتف بنجاح",
            "data" => $user,
            "statusCode" => 200
        ]);
    }
}
