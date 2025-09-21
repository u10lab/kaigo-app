<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FreeeController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 認証関連ルート
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// freee連携ルート（認証不要）
Route::get('/freee/auth-url', [FreeeController::class, 'getAuthUrl']);
Route::get('/freee/callback', [FreeeController::class, 'callback']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    
    // freee連携API
    Route::post('/freee/clock-in', [FreeeController::class, 'clockIn']);
    Route::post('/freee/clock-out', [FreeeController::class, 'clockOut']);
    Route::get('/freee/work-records', [FreeeController::class, 'getWorkRecords']);
    Route::get('/freee/payroll', [FreeeController::class, 'getPayroll']);
    
    // サービス記録API
    Route::prefix('service')->group(function () {
        Route::post('/start', [ServiceController::class, 'start']);
        Route::post('/{id}/end', [ServiceController::class, 'end']);
        Route::get('/records', [ServiceController::class, 'getRecords']);
        Route::get('/current-status', [ServiceController::class, 'getCurrentStatus']);
        Route::get('/clients', [ServiceController::class, 'getClients']);
        Route::get('/service-types', [ServiceController::class, 'getServiceTypes']);
    });
});
