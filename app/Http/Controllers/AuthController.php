<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignUpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller
{

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
                'user_id'   => $user->id,
                'region_id' => $validatedData['region_id'] ?? \App\Models\Region::first()?->id,
                'latitude'  => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'shop_name' => $validatedData['shop_name'],
                'fcm_token' => $validatedData['fcm_token'] ?? null,
                'address'   => $validatedData['address'],
            ]);

            $wallet=Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            return compact('user', 'profile', 'wallet', 'token');
        });

        $user = $result['user'];
        $profile = $result['profile'];
        $wallet = $result['wallet'];
        $token = $result['token'];

        try {
            if ($request->hasFile('exterior_photo')) {
                $user->addMediaFromRequest('exterior_photo')->toMediaCollection('exterior_photo');
            }
            if ($request->hasFile('interior_photo')) {
                $user->addMediaFromRequest('interior_photo')->toMediaCollection('interior_photo');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to upload shop photos on register: " . $e->getMessage());
        }

        // Send notification to admins and sub_admins
        try {
            app(NotificationController::class)->sendNewCustomerNotification($user);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send signup notification: " . $e->getMessage());
        }
        
        $profile->load('region');

        return $this->successResponse(
            message: 'تم إنشاء حسابك بنجاح',
            data: [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role, 
                    'fcm_token' => $profile->fcm_token,
                    'balance' => $wallet->balance,
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                    'shop_name' => $profile->shop_name,
                    'address' => $profile->address,
                    'exterior_photo_url' => $user->exterior_photo_url,
                    'interior_photo_url' => $user->interior_photo_url,
                    'region' => $profile->region ? [
                        'id' => $profile->region->id,
                        'name' => $profile->region->name,
                        'min_order_price' => $profile->region->min_order_price,
                        'min_order_products' => $profile->region->min_order_products,
                    ] : null,
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
        $user->load(['profile.region', 'wallet']);
        $profile = $user->profile;
        $wallet = $user->wallet;
        
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
                    'fcm_token' => $profile->fcm_token ?? null,
                    'balance' => $wallet->balance ?? 0,
                    'latitude' => $profile->latitude ?? null,
                    'longitude' => $profile->longitude ?? null,
                    'shop_name' => $profile->shop_name ?? null,
                    'address' => $profile->address ?? null,
                    'exterior_photo_url' => $user->exterior_photo_url,
                    'interior_photo_url' => $user->interior_photo_url,
                    'region' => ($profile && $profile->region) ? [
                        'id' => $profile->region->id,
                        'name' => $profile->region->name,
                        'min_order_price' => $profile->region->min_order_price,
                        'min_order_products' => $profile->region->min_order_products,
                    ] : null,
                ],
            ],
           statusCode: 200
        );
    }

    public function logout(Request $request)
    {
        if ($request->user()->profile) {
            $request->user()->profile->update([
                'fcm_token' => null,
            ]);
        }
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            message: 'تم تسجيل الخروج بنجاح',
            statusCode: 200
        );
    }

}
