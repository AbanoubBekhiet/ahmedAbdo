<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignUpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponseTrait;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller
{

    use ApiResponseTrait;
    
    public function register(SignUpRequest $request)
    {
        $validatedData = $request->validated();

        $result = DB::transaction(function () use ($validatedData) {
            
            $user = User::create([
                'name' => $validatedData['name'],
                'phone_number' => $validatedData['phone_number'],
                'password' => Hash::make($validatedData['password']),
                'role' => "customer",
            ]);

            $profile = Profile::create([
                'user_id' => $user->id,
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'shop_name' => $validatedData['shop_name'],
                'address' => $validatedData['address']
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            return compact('user', 'profile', 'token');
        });

        $user = $result['user'];
        $profile = $result['profile'];
        $token = $result['token'];
        
        return $this->successResponse(
            message: 'تم إنشاء حسابك بنجاح',
            data: [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role, 
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                    'shop_name' => $profile->shop_name,
                    'address' => $profile->address,
                ],
            ],
            statusCode: 201
        );
    }
    
    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();
        if (!Auth::attempt($validatedData)) {
            return $this->errorResponse('Invalid credentials', 401);
        }
        
        $user = Auth::user();
        
        $token = $user->createToken('authToken')->plainTextToken;
        
        return $this->successResponse(
            message: 'Login successful',
            data: [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role, 
                ],
            ],
           statusCode: 200
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            message: 'تم تسجيل الخروج بنجاح',
            statusCode: 200
        );
    }

}
