<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can fetch statistics', function () {
    // Create admin user
    $admin = User::create([
        'name' => 'Admin User',
        'role' => 'admin',
        'phone_number' => '1234567890',
        'password' => bcrypt('password'),
    ]);

    // Create customers
    // 1. All-time customers (registered this month by default)
    $customer1 = User::create([
        'name' => 'Customer One',
        'role' => 'customer',
        'phone_number' => '1111111111',
        'password' => bcrypt('password'),
    ]);

    $customer2 = User::create([
        'name' => 'Customer Two',
        'role' => 'customer',
        'phone_number' => '2222222222',
        'password' => bcrypt('password'),
    ]);

    // 2. Customer registered last month (not counted in this month's stats)
    $customer3 = User::create([
        'name' => 'Customer Three',
        'role' => 'customer',
        'phone_number' => '3333333333',
        'password' => bcrypt('password'),
    ]);
    DB::table('users')->where('id', $customer3->id)->update(['created_at' => now()->subMonth()]);

    // Create orders
    // Order 1: Delivered, total 150.00, created this month
    Order::create([
        'user_id' => $customer1->id,
        'total_price' => 150.00,
        'status' => 'تم التوصيل',
    ]);

    // Order 2: Preparing, total 200.00, created this month
    Order::create([
        'user_id' => $customer2->id,
        'total_price' => 200.00,
        'status' => 'جاري التجهيز',
    ]);

    // Order 3: Cancelled, total 50.00, created last month
    $order3 = Order::create([
        'user_id' => $customer1->id,
        'total_price' => 50.00,
        'status' => 'ملغي',
    ]);
    DB::table('orders')->where('id', $order3->id)->update(['created_at' => now()->subMonth()]);

    // Authenticate as Admin
    Sanctum::actingAs($admin);

    // Get statistics
    $response = $this->getJson('/api/statistics');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'تم جلب الإحصائيات بنجاح',
            'data' => [
                'total_customers' => 3,
                'total_customers_registered_this_month' => 2,
                'total_sum_of_orders_total_price' => 400.00,
                'total_sum_of_orders_total_price_this_month' => 350.00,
                'total_sum_of_orders_by_status' => [
                    'جاري التجهيز' => 200.00,
                    'تم التوصيل' => 150.00,
                    'ملغي' => 50.00,
                ],
                'total_sum_of_orders_by_status_this_month' => [
                    'جاري التجهيز' => 200.00,
                    'تم التوصيل' => 150.00,
                    'ملغي' => 0.00,
                ],
            ]
        ]);
});

test('non-admin cannot fetch statistics', function () {
    $customer = User::create([
        'name' => 'Customer User',
        'role' => 'customer',
        'phone_number' => '9999999999',
        'password' => bcrypt('password'),
    ]);

    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/statistics');

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Unauthorized access.',
        ]);
});
