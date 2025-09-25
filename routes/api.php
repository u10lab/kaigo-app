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
    Route::get('/users', [AuthController::class, 'getUsers']);
    
    // freee連携API
    Route::get('/freee/auth-status', [FreeeController::class, 'getAuthStatus']);
    Route::get('/freee/employees', [FreeeController::class, 'getEmployees']);
    Route::post('/freee/update-employee-ids', [FreeeController::class, 'updateEmployeeIds']);
    Route::post('/freee/import-employees', [FreeeController::class, 'importEmployeesFromFreee']);
    Route::post('/freee/map-employee-id', [FreeeController::class, 'mapEmployeeId']);
    Route::post('/freee/clock-in', [FreeeController::class, 'clockIn']);
    Route::post('/freee/clock-out', [FreeeController::class, 'clockOut']);
    
        // サービス記録API
        Route::prefix('service')->group(function () {
            Route::post('/start', [ServiceController::class, 'start']);
            Route::post('/{id}/end', [ServiceController::class, 'end']);
            Route::get('/records', [ServiceController::class, 'getRecords']);
            Route::get('/current-status', [ServiceController::class, 'getCurrentStatus']);
            Route::get('/work-status', [ServiceController::class, 'getWorkStatus']);
            Route::get('/clock-out-status', [ServiceController::class, 'checkClockOutStatus']);
            Route::post('/clock-in', [ServiceController::class, 'recordClockIn']);
            Route::post('/clock-out', [ServiceController::class, 'recordClockOut']);
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
        Route::get('/status', [PaidLeaveController::class, 'getStatus']);
        Route::get('/all-employees-status', [PaidLeaveController::class, 'getAllEmployeesStatus']);
        Route::get('/applications', [PaidLeaveController::class, 'getApplications']);
        Route::post('/apply', [PaidLeaveController::class, 'apply']);
        Route::post('/approve', [PaidLeaveController::class, 'approve']);
        Route::post('/reject', [PaidLeaveController::class, 'reject']);
        Route::get('/annual-check', [PaidLeaveController::class, 'getAnnualLeaveCheck']);
        
        
    });
    
    // CSV出力API
    Route::prefix('export')->group(function () {
        Route::get('/care-insurance/{month}', [ExportController::class, 'exportCareInsurance']);
        Route::get('/care-insurance/{month}/stats', [ExportController::class, 'getCareInsuranceStats']);
        Route::get('/history', [ExportController::class, 'getExportHistory']);
        Route::get('/download/{historyId}', [ExportController::class, 'downloadFile']);
    });
});
