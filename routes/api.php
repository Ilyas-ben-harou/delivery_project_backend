<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientRegisterControler;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\LivreurController;
use App\Http\Controllers\OrderAssignmentController;
use App\Http\Controllers\OrderDocumentController;
use App\Http\Controllers\ZoneGeographicController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RouteOptimizationController; // New controller for route optimization
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerInfoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarningController;

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
        // Livreur management
        Route::get('/livreurs', [LivreurController::class, 'index']);
        Route::get('/livreurs/{id}', [LivreurController::class, 'show']);
        Route::delete('/livreurs/{id}', [LivreurController::class, 'destroy']);
        Route::post('/livreurs', [LivreurController::class, 'store']);
        Route::put('/livreurs/{id}', [LivreurController::class, 'update']);
        Route::patch('/livreurs/{id}/disponible', [LivreurController::class, 'updateDisponibleByAdmin']);

        // Zone geographic management
        Route::get('/zone-geographics', [ZoneGeographicController::class, 'index']);
        Route::post('/zone-geographics', [ZoneGeographicController::class, 'store']);
        Route::get('/zone-geographics/{id}', [ZoneGeographicController::class, 'show']);
        Route::put('/zone-geographics/{id}', [ZoneGeographicController::class, 'update']);
        Route::delete('/zone-geographics/{id}', [ZoneGeographicController::class, 'destroy']);

        // Order management
        Route::get('/orders', [AdminController::class, 'getOrders']);
        Route::get('/orders/{id}', [AdminController::class, 'getOrder']);
        Route::put('/orders/{id}', [OrderController::class, 'update']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
        Route::patch('/orders/{id}/assign', [OrderController::class, 'assignToLivreur']);

        //client management
        Route::post('/clients/register',[ClientController::class,'register']);
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{id}', [ClientController::class, 'show']);
        Route::put('/clients/{id}', [ClientController::class, 'update']);
        Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
        // Customer info management
        // Route::get('/customer-infos', [CustomerInfoController::class, 'index']);

        // Dashboard statistics
        // Route::get('/dashboard', [DashboardController::class, 'adminStats']);

        // Financial management

        // Financial dashboard
        Route::get('/financial/dashboard', [FinancialController::class, 'dashboard']);
        
        // City pricing management
        Route::get('/financial/pricing', [FinancialController::class, 'getPricing']);
        Route::post('/financial/pricing', [FinancialController::class, 'updatePricing']);
        Route::delete('/financial/pricing/{id}', [FinancialController::class, 'deletePricing']);
        
        // Distributor payments
        Route::get('/financial/distributor-payments', [FinancialController::class, 'getDistributorPayments']);
        
        // Financial reports
        Route::post('/financial/reports', [FinancialController::class, 'generateReport']);
    });

    // Client routes
    Route::middleware('role:client')->prefix('client')->group(function () {
        // Order management
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}', [OrderController::class, 'update']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
        // Zone geographic lookup for order creation
        Route::get('/zone-geographics', [ZoneGeographicController::class, 'index']);

        // Client dashboard stats
        // Route::get('/dashboard', [DashboardController::class, 'clientStats']);
    });

    // Livreur routes
    Route::middleware('role:livreur')->prefix('livreur')->group(function () {
        Route::put('/update-availability', [LivreurController::class, 'updateAvailability']);

        // Retrieve assigned orders
        Route::post('/orders', [OrderController::class, 'getLivreurOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}', [App\Http\Controllers\OrderController::class, 'updateStatus']);

        // Update order status (e.g., mark as delivered)
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatusByLivreur']);

        // Update own availability status
        Route::patch('/disponible', [LivreurController::class, 'updateDisponible']);

        // Get zones assigned to this livreur
        Route::get('/zones', [LivreurController::class, 'getAssignedZones']);

        // Livreur profile
        Route::get('/profile', [LivreurController::class, 'getProfile']);
        Route::put('/profile', [LivreurController::class, 'updateProfile']);

        // Livreur dashboard stats
        // Route::get('/dashboard', [DashboardController::class, 'livreurStats']);

        // Route optimization endpoints for livreur
         // Route optimization endpoints for livreur
        Route::prefix('/earnings')->group(function () {
        Route::get('/', [EarningController::class, 'index']);
        Route::get('/summary', [EarningController::class, 'summary']);
        Route::get('/reports', [EarningController::class, 'reports']);
        Route::post('/export', [EarningController::class, 'export']);/*
         Route::get('/', 'EarningsController@index');
        Route::get('/summary', 'EarningsController@summary');
        Route::get('/weekly', 'EarningsController@weeklyReport');
        Route::get('/monthly', 'EarningsController@monthlyReport');
        Route::get('/export', 'EarningsController@export');
        Route::get('/{earning}', 'EarningsController@show');*/
    });
});
});