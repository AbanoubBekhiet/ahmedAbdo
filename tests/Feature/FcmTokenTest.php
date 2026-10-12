<?php

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('unauthenticated user cannot update fcm token', function () {
    $response = $this->putJson('/api/profile/fcm-token', [
        'fcm_token' => 'sample_token_123'
    ]);

    $response->assertStatus(401);
});

test('authenticated user can update their fcm token', function () {
    $user = User::create([
        'name' => 'John Doe',
        'role' => 'customer',
        'phone_number' => '0123456789',
        'password' => bcrypt('password'),
    ]);

    // Ensure they have a profile
    $profile = Profile::create([
        'user_id' => $user->id,
        'latitude' => 30.0,
        'longitude' => 31.0,
        'shop_name' => 'My Shop',
        'address' => 'Cairo, Egypt',
        'fcm_token' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/fcm-token', [
        'fcm_token' => 'new_fcm_token_987'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'message' => 'تم تحديث رمز الجهاز (FCM) بنجاح',
                'statusCode' => 200
            ]
        ]);

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'fcm_token' => 'new_fcm_token_987'
    ]);
});

test('updating fcm token validates fcm_token parameter', function () {
    $user = User::create([
        'name' => 'John Doe',
        'role' => 'customer',
        'phone_number' => '0123456789',
        'password' => bcrypt('password'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/fcm-token', [
        // empty payload
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fcm_token']);
});
