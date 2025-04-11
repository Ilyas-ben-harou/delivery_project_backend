<?php

use App\Http\Controllers\ClientRegisterControler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes protégées nécessitant authentification
// Route::middleware('auth:sanctum')->group(function () {
//     // Routes pour la gestion des commandes
//     Route::prefix('orders')->group(function () {
//         // Routes de base CRUD
//         Route::get('/', [OrderController::class, 'index']);
//         Route::post('/', [OrderController::class, 'store']);
//         Route::get('/{id}', [OrderController::class, 'show']);
//         Route::put('/{id}', [OrderController::class, 'update']);
//         Route::delete('/{id}', [OrderController::class, 'destroy']);

//         // Routes spécifiques pour la gestion des commandes
//         Route::post('/{id}/document', [OrderController::class, 'generateDeliveryDocument']);
//         Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
//         Route::put('/{id}/reassign', [OrderController::class, 'reassignOrder']);
//     });
// });




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [ClientRegisterControler::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/client', function (Request $request) {
        return $request->Client();
    });
});
