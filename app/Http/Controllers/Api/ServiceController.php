<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRecord;
use App\Models\Client;
use App\Models\ServiceType;
use App\Models\WorkRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ServiceController extends Controller
{
    /**
     * サービス開始
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:clients,id',
            'service_type_id' => 'required|integer|exists:service_types,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Service start validation failed', [
                'request_data' => $request->all(),
                'errors' => $validator->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 既存の未終了サービスがあるかチェック
            $existingService = ServiceRecord::where('user_id', Auth::id())
                ->whereNull('service_end_time')
                ->where('status', '実施済み')
                ->first();

            if ($existingService) {
                return response()->json([
                    'success' => false,
                    'message' => '既にサービスが開始されています。先にサービスを終了してください。',
                    'existing_service' => $existingService
                ], 400);
            }

            // 新しいサービス記録を作成
            $serviceRecord = ServiceRecord::create([
                'user_id' => Auth::id(),
                'client_id' => $request->client_id,
                'service_type_id' => $request->service_type_id,
                'service_start_time' => now(),
                'service_date' => now()->toDateString(),
                'status' => '実施済み',
                'notes' => $request->notes ?? null,
            ]);

            // 関連データを取得
            $serviceRecord->load(['client', 'serviceType']);

            return response()->json([
                'success' => true,
                'message' => 'サービスを開始しました。',
                'data' => $serviceRecord
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サービス開始に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * サービス終了
     */
    public function end(Request $request, $id)
    {
        try {
            $serviceRecord = ServiceRecord::where('id', $id)
                ->where('user_id', Auth::id())
                ->whereNull('service_end_time')
                ->first();

            if (!$serviceRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'サービス記録が見つからないか、既に終了しています。'
                ], 404);
            }

            $endTime = now();
            $startTime = Carbon::parse($serviceRecord->service_start_time);
            $serviceMinutes = $startTime->diffInMinutes($endTime);

            // サービス記録を更新
            $serviceRecord->update([
                'service_end_time' => $endTime,
                'service_minutes' => $serviceMinutes,
                'notes' => $request->notes ?? $serviceRecord->notes,
            ]);

            // 関連データを取得
            $serviceRecord->load(['client', 'serviceType']);

            return response()->json([
                'success' => true,
                'message' => 'サービスを終了しました。',
                'data' => $serviceRecord
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サービス終了に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * サービス記録取得
     */
    public function getRecords(Request $request)
    {
        try {
            $user = Auth::user();
            $query = ServiceRecord::with(['client', 'serviceType', 'user']);

            // ロール基準でのデータ分離
            if ($user->role === 'helper') {
                // ヘルパーは自分のデータのみ
                $query->where('user_id', $user->id);
            }
            // 管理者(admin)は全従業員のデータを取得（where条件なし）

            // 月別フィルタ（'2025-01'形式 → 日付範囲変換）
            if ($request->has('month') && $request->month !== 'all') {
                $year = substr($request->month, 0, 4);
                $month = substr($request->month, 5, 2);
                $startDate = "{$year}-{$month}-01";
                $endDate = date('Y-m-t', strtotime($startDate)); // 月末日を自動取得

                $query->whereBetween('service_date', [$startDate, $endDate]);
            }

            // 日付範囲フィルタ（直接指定の場合）
            if ($request->has('start_date')) {
                $query->where('service_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('service_date', '<=', $request->end_date);
            }

            // クライアントフィルタ
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            // サービス種別フィルタ
            if ($request->has('service_type_id')) {
                $query->where('service_type_id', $request->service_type_id);
            }

            // ステータスフィルタ
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $records = $query->orderBy('service_date', 'desc')
                ->orderBy('service_start_time', 'desc')
                ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $records
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サービス記録の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 現在のサービス状況取得
     */
    public function getCurrentStatus()
    {
        try {
            $currentService = ServiceRecord::with(['client', 'serviceType'])
                ->where('user_id', Auth::id())
                ->whereNull('service_end_time')
                ->where('status', '実施済み')
                ->first();

            if ($currentService) {
                $startTime = Carbon::parse($currentService->service_start_time);
                $currentMinutes = $startTime->diffInMinutes(now());
                
                $currentService->current_minutes = $currentMinutes;
            }

            return response()->json([
                'success' => true,
                'data' => $currentService
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '現在のサービス状況の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * クライアント一覧取得
     */
    public function getClients()
    {
        try {
            $clients = Client::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clients
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'クライアント一覧の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * サービス種別一覧取得
     */
    public function getServiceTypes()
    {
        try {
            $serviceTypes = ServiceType::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $serviceTypes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サービス種別一覧の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 出勤記録を作成
     */
    public function recordClockIn(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            $now = Carbon::now();
            
            // 今日の勤務記録を確認または作成
            $workRecord = WorkRecord::where('user_id', $user->id)
                ->where('work_date', $today)
                ->first();
            
            if (!$workRecord) {
                // 新規作成
                $workRecord = WorkRecord::create([
                    'user_id' => $user->id,
                    'work_date' => $today,
                    'clock_in_time' => $now,
                    'status' => '出勤中'
                ]);
            } else {
                // 既存の記録を更新
                $workRecord->update([
                    'clock_in_time' => $now,
                    'status' => '出勤中'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $workRecord
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Record clock in error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 退勤記録を更新
     */
    public function recordClockOut(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            $now = Carbon::now();
            
            // 今日の勤務記録を取得
            $workRecord = WorkRecord::where('user_id', $user->id)
                ->where('work_date', $today)
                ->first();
            
            if ($workRecord) {
                // 退勤記録を更新
                $workRecord->update([
                    'clock_out_time' => $now,
                    'status' => '退勤済み'
                ]);
            } else {
                // 出勤記録がない場合は新規作成
                $workRecord = WorkRecord::create([
                    'user_id' => $user->id,
                    'work_date' => $today,
                    'clock_in_time' => $now, // 退勤時点を出勤時刻として記録
                    'clock_out_time' => $now,
                    'status' => '退勤済み'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $workRecord
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Record clock out error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 退勤状態を確認
     */
    public function checkClockOutStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // 今日の勤務記録を確認
            $workRecord = WorkRecord::where('user_id', $user->id)
                ->where('work_date', $today)
                ->first();
            
            $isClockedOut = false;
            if ($workRecord && $workRecord->status === '退勤済み') {
                $isClockedOut = true;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_clocked_out' => $isClockedOut
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Check clock out status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 勤務状態を取得（サービス記録ベース）
     */
    public function getWorkStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // 今日のサービス記録を取得
            $todayServices = ServiceRecord::where('user_id', $user->id)
                ->whereDate('service_start_time', $today)
                ->orderBy('service_start_time')
                ->get();
            
            
            // 勤務状態を判定
            $workStatus = 'idle';
            $workStartTime = null;
            $workEndTime = null;
            
            if ($todayServices->isNotEmpty()) {
                $firstService = $todayServices->first();
                $lastService = $todayServices->last();
                
                $workStartTime = $firstService->service_start_time;
                
                // サービス記録がある場合は勤務中として扱う
                // サービス終了後も退勤まで勤務中状態を維持
                $workStatus = 'working';
                
                // 最後のサービスが終了している場合は終了時刻を設定
                if ($lastService->service_end_time) {
                    $workEndTime = $lastService->service_end_time;
                }
            } else {
                // サービス記録がない場合は未出勤
                $workStatus = 'idle';
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'work_status' => $workStatus,
                    'work_start_time' => $workStartTime ? $workStartTime->format('H:i') : null,
                    'work_end_time' => $workEndTime ? $workEndTime->format('H:i') : null,
                    'today_services' => $todayServices->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'client_name' => $service->client->name,
                            'service_type' => $service->serviceType->name,
                            'start_time' => $service->service_start_time->format('H:i'),
                            'end_time' => $service->service_end_time ? $service->service_end_time->format('H:i') : null,
                            'duration' => $service->service_end_time ? $service->service_start_time->diffInMinutes($service->service_end_time) : null
                        ];
                    })
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Get work status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
