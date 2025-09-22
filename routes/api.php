<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FreeeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PaidLeaveController;
use App\Http\Controllers\Api\ExportController;
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
    Route::get('/freee/employees', [FreeeController::class, 'getEmployees']);
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
    
    // 給与計算API
    Route::prefix('payroll')->group(function () {
        Route::get('/calculate', [PayrollController::class, 'calculate']);
        Route::post('/recalculate', [PayrollController::class, 'recalculate']);
    });
    
    // 有給休暇管理API
    Route::prefix('paid-leave')->group(function () {
        Route::get('/all', [PaidLeaveController::class, 'getAllPaidLeaveStatus']);
        Route::get('/five-day-check', [PaidLeaveController::class, 'checkFiveDayObligation']);
        Route::post('/use', [PaidLeaveController::class, 'usePaidLeave']);
        Route::get('/{userId}', [PaidLeaveController::class, 'getPaidLeaveStatus']);
        Route::put('/{userId}/grant', [PaidLeaveController::class, 'grantPaidLeave']);
    });
    
    // CSV出力API
    Route::prefix('export')->group(function () {
        Route::get('/care-insurance/{month}', [ExportController::class, 'exportCareInsurance']);
        Route::get('/care-insurance/{month}/stats', [ExportController::class, 'getCareInsuranceStats']);
    });
});
