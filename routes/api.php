<?php

use App\Http\Controllers\ClientRegisterControler;
use App\Http\Controllers\LivreurController;
use App\Http\Controllers\ZoneGeographicController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Public routes
Route::post('/register', [ClientRegisterControler::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Add the route for adding a livreur
        Route::get('/livreurs', [LivreurController::class, 'index']);
        Route::get('/livreurs/{id}', [LivreurController::class, 'show']);
        Route::delete('/livreurs/{id}', [LivreurController::class, 'destroy']);
        Route::post('/livreurs', [LivreurController::class, 'store']);
        Route::patch('/livreurs/{id}/disponible', [LivreurController::class, 'updateDisponibleByAdmin']);
        Route::get('/zone-geographics', [ZoneGeographicController::class, 'index']);
        // Other admin routes will go here
    });
    
    // Client routes
    Route::middleware('role:client')->prefix('client')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/zone-geographics', [ZoneGeographicController::class, 'index']);
    }); 
    
    // Livreur routes
    Route::middleware('role:livreur')->prefix('livreur')->group(function () {
        // Livreur specific routes will go here
    });
});