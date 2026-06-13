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


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories/products', [CategoryController::class, 'categoriesWithProducts']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin');
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin'); 
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin');
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductsController::class, 'index']);
    Route::post('/products', [ProductsController::class, 'store'])->middleware('role:admin');
    Route::post('/products/import', [ProductsController::class, 'import'])->middleware('role:admin');
    Route::get('/products/{product}', [ProductsController::class, 'show']);
    Route::put('/products/{product}', [ProductsController::class, 'update'])->middleware('role:admin'); 
    Route::delete('/products/{product}', [ProductsController::class, 'destroy'])->middleware('role:admin');
    Route::put('/products/{product}/change-status', [ProductsController::class, 'changeProductStatus'])->middleware('role:admin');
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
    Route::get('/cart', [CartController::class, 'myCart']);
    Route::get('/cart/users', [CartController::class, 'usersCart'])->middleware('role:admin');
    Route::post('/cart', [CartController::class, 'addToCart']);
    Route::put('/cart/{cart}', [CartController::class, 'updateCartItem']);
    Route::delete('/cart/{cart}', [CartController::class, 'deleteCartItem']);
    Route::delete('/cart', [CartController::class, 'emptyCart']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/all', [OrdersController::class, 'getAllOrders'])->middleware('role:admin');
    Route::get('/orders/my-orders', [OrdersController::class, 'getMyOrders']);
    Route::post('/orders', [OrdersController::class, 'createOrder']);
    Route::get('/orders/{order}', [OrdersController::class, 'getSingleOrder']);
    Route::put('/orders/{order}', [OrdersController::class, 'updateOrderStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/settings/update', [SettingsController::class, 'updateSettings'])->middleware('role:admin');
    Route::get('/settings', [SettingsController::class, 'getSettings']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'myProfile']);
    Route::put('/profile/update', [UserController::class, 'updateProfile']);
    Route::get('/users/customers', [UserController::class, 'showCustomers'])->middleware('role:admin');
    Route::get('/users/delivery-boys', [UserController::class, 'showDeliveryBoys'])->middleware('role:admin');
    Route::post('/users/delivery-boy', [UserController::class, 'storeDeliveryBoy'])->middleware('role:admin');
    Route::put('/users/delivery-boy/{delivery_boy}', [UserController::class, 'updateDeliveryBoy'])->middleware('role:admin');
    Route::delete('/users/delivery-boy/{delivery_boy}', [UserController::class, 'deleteDeliveryBoy'])->middleware('role:admin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'myWallet'])->middleware('role:admin,customer');
    Route::post('/wallet/add/{userId}', [WalletController::class, 'addToWallet'])->middleware('role:admin,customer');
    Route::post('/wallet/withdraw/{userId}', [WalletController::class, 'withdrawFromWallet'])->middleware('role:admin,customer');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/statistics', [StatisticsController::class, 'getStatistics'])->middleware('role:admin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/offers', [OffersController::class, 'index']);
    Route::post('/offers', [OffersController::class, 'store'])->middleware('role:admin');
    Route::delete('/offers/{offer}', [OffersController::class, 'destroy'])->middleware('role:admin');
    Route::get('/offers/{offer}', [OffersController::class, 'show']);
});