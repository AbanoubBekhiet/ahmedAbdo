<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\OffersController;
use App\Http\Controllers\MonthlyTargetController;
use App\Http\Controllers\UserTargetController;
use App\Http\Controllers\UserMonthlyTargetController;
use App\Http\Controllers\RegionController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories/products', [CategoryController::class, 'categoriesWithProducts']);
    Route::get('/categories', [CategoryController::class, 'index']);
    // sub_admin has full CRUD on categories
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin,sub_admin');
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin,sub_admin');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin,sub_admin');
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductsController::class, 'index']);
    // Only admin can add new products or import
    Route::post('/products', [ProductsController::class, 'store'])->middleware('role:admin');
    Route::post('/products/import', [ProductsController::class, 'import'])->middleware('role:admin');
    Route::get('/products/{product}', [ProductsController::class, 'show']);
    // sub_admin can edit and delete products
    Route::put('/products/{product}', [ProductsController::class, 'update'])->middleware('role:admin,sub_admin');
    Route::delete('/products/{product}', [ProductsController::class, 'destroy'])->middleware('role:admin,sub_admin');
    Route::put('/products/{product}/change-status', [ProductsController::class, 'changeProductStatus'])->middleware('role:admin,sub_admin');
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/targets', [TargetController::class, 'index']);
    Route::post('/targets', [TargetController::class, 'store'])->middleware('role:admin');
    Route::get('/targets/{target}', [TargetController::class, 'show']);
    Route::put('/targets/{target}', [TargetController::class, 'update'])->middleware('role:admin');
    Route::delete('/targets/{target}', [TargetController::class, 'destroy'])->middleware('role:admin');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/monthly-targets', [MonthlyTargetController::class, 'index']);
    Route::post('/monthly-targets', [MonthlyTargetController::class, 'store'])->middleware('role:admin');
    Route::get('/monthly-targets/{monthly_target}', [MonthlyTargetController::class, 'show']);
    Route::put('/monthly-targets/{monthly_target}', [MonthlyTargetController::class, 'update'])->middleware('role:admin');
    Route::delete('/monthly-targets/{monthly_target}', [MonthlyTargetController::class, 'destroy'])->middleware('role:admin');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-targets', [UserTargetController::class, 'index']);
    Route::post('/user-targets', [UserTargetController::class, 'store'])->middleware('role:admin');
    Route::get('/user-targets/{user_target}', [UserTargetController::class, 'show']);
    Route::put('/user-targets/{user_target}', [UserTargetController::class, 'update'])->middleware('role:admin');
    Route::delete('/user-targets/{user_target}', [UserTargetController::class, 'destroy'])->middleware('role:admin');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-monthly-targets', [UserMonthlyTargetController::class, 'index']);
    Route::post('/user-monthly-targets', [UserMonthlyTargetController::class, 'store'])->middleware('role:admin');
    Route::get('/user-monthly-targets/{user_monthly_target}', [UserMonthlyTargetController::class, 'show']);
    Route::put('/user-monthly-targets/{user_monthly_target}', [UserMonthlyTargetController::class, 'update'])->middleware('role:admin');
    Route::delete('/user-monthly-targets/{user_monthly_target}', [UserMonthlyTargetController::class, 'destroy'])->middleware('role:admin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'myCart']);
    Route::get('/cart/users', [CartController::class, 'usersCart'])->middleware('role:admin');
    Route::post('/cart', [CartController::class, 'addToCart']);
    Route::put('/cart/{cart}', [CartController::class, 'updateCartItem']);
    Route::delete('/cart/{cart}', [CartController::class, 'deleteCartItem']);
    Route::delete('/cart', [CartController::class, 'emptyCart']);
});


Route::middleware('auth:sanctum')->group(function () {
    // sub_admin can view all orders (but not necessarily change status - handled in controller)
    Route::get('/orders/all', [OrdersController::class, 'getAllOrders'])->middleware('role:admin,sub_admin');
    Route::get('/orders/my-orders', [OrdersController::class, 'getMyOrders']);
    Route::post('/orders', [OrdersController::class, 'createOrder']);
    Route::get('/orders/{order}', [OrdersController::class, 'getSingleOrder']);
    Route::put('/orders/{order}', [OrdersController::class, 'updateOrderStatus']);
    Route::put('/orders/{order}/customer-update', [OrdersController::class, 'updateCustomerOrder']);
    Route::put('/orders/{order}/admin-update', [OrdersController::class, 'updateAdminOrder'])->middleware('role:admin,sub_admin');
    Route::delete('/orders/{order}/customer-cancel', [OrdersController::class, 'cancelCustomerOrder']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/settings/update', [SettingsController::class, 'updateSettings'])->middleware('role:admin');
    Route::get('/settings', [SettingsController::class, 'getSettings']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'myProfile']);
    Route::put('/profile/update', [UserController::class, 'updateProfile']);
    Route::put('/profile/fcm-token', [UserController::class, 'updateFcmToken']);
    Route::put('/profile/change-password', [UserController::class, 'changeMyPassword']);
    Route::put('/profile/update-phone', [UserController::class, 'updateMyPhone']);
    // sub_admin can view customers
    Route::get('/users/customers', [UserController::class, 'showCustomers'])->middleware('role:admin,sub_admin');
    // Only admin can reset customer password
    Route::put('/users/customer/{customer}/password', [UserController::class, 'updateCustomerPassword'])->middleware('role:admin');
    // Only admin manages sub-admins
    Route::get('/users/sub-admins', [UserController::class, 'showSubAdmins'])->middleware('role:admin');
    Route::post('/users/sub-admin', [UserController::class, 'storeSubAdmin'])->middleware('role:admin');
    Route::put('/users/sub-admin/{sub_admin}', [UserController::class, 'updateSubAdmin'])->middleware('role:admin');
    Route::delete('/users/sub-admin/{sub_admin}', [UserController::class, 'deleteSubAdmin'])->middleware('role:admin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'myWallet'])->middleware('role:admin,customer');
    Route::post('/wallet/add/{userId}', [WalletController::class, 'addToWallet'])->middleware('role:admin,customer');
    Route::post('/wallet/withdraw/{userId}', [WalletController::class, 'withdrawFromWallet'])->middleware('role:admin,customer');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::match(['get', 'post'], '/statistics', [StatisticsController::class, 'getStatistics'])->middleware('role:admin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/offers', [OffersController::class, 'index']);
    // sub_admin can manage offers
    Route::post('/offers', [OffersController::class, 'store'])->middleware('role:admin,sub_admin');
    Route::delete('/offers/{offer}', [OffersController::class, 'destroy'])->middleware('role:admin,sub_admin');
    Route::get('/offers/{offer}', [OffersController::class, 'show']);
});

// Regions routes (Guests can list active, admin/sub-admin can perform CRUD)
Route::get('/regions', [RegionController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/regions', [RegionController::class, 'store'])->middleware('role:admin,sub_admin');
    Route::put('/regions/{id}', [RegionController::class, 'update'])->middleware('role:admin,sub_admin');
    Route::delete('/regions/{id}', [RegionController::class, 'destroy'])->middleware('role:admin,sub_admin');
});