<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductsController;


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
    Route::get('/products/{product}', [ProductsController::class, 'show']);
    Route::put('/products/{product}', [ProductsController::class, 'update'])->middleware('role:admin'); 
    Route::delete('/products/{product}', [ProductsController::class, 'destroy'])->middleware('role:admin');
});

