<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Services\FreeeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollController extends Controller
{
    private FreeeApiService $freeeApiService;

    public function __construct(FreeeApiService $freeeApiService)
    {
        $this->freeeApiService = $freeeApiService;
    }

    /**
     * 給与計算結果取得
     * GET /api/payroll/calculate
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $month = $request->month;
        $userId = $request->user_id ?? Auth::id();

        try {
            // ユーザー情報取得
            $user = User::findOrFail($userId);

            // freee APIから基本給・勤務時間データ取得
            $freeeData = $this->getFreeePayrollData($user, $month);

            // サービス記録からサービス別時給差額計算
            $serviceAdjustments = $this->calculateServiceAdjustments($user, $month);

            // 総給与計算
            $totalSalary = $freeeData['base_salary'] + $serviceAdjustments['total_adjustment'];

            // レスポンス作成
            $result = [
                'user_id' => $user->id,
                'employee_name' => $user->name,
                'employee_id' => $user->employee_id,
                'month' => $month,
                'base_salary' => $freeeData['base_salary'],
                'service_adjustments' => $serviceAdjustments['total_adjustment'],
                'total_salary' => $totalSalary,
                'total_hours' => $freeeData['total_hours'],
                'overtime_hours' => $freeeData['overtime_hours'],
                'service_breakdown' => $serviceAdjustments['breakdown'],
                'calculated_at' => now()->toISOString(),
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Payroll calculation failed', [
                'user_id' => $userId,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '給与計算に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 給与再計算
     * POST /api/payroll/recalculate
     */
    public function recalculate(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $month = $request->month;
        $userId = $request->user_id ?? Auth::id();

        try {
            // 再計算実行
            $result = $this->calculate($request);

            Log::info('Payroll recalculated', [
                'user_id' => $userId,
                'month' => $month,
            ]);

            return response()->json([
                'message' => '給与を再計算しました。',
                'data' => json_decode($result->getContent(), true),
            ]);

        } catch (\Exception $e) {
            Log::error('Payroll recalculation failed', [
                'user_id' => $userId,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '給与再計算に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * freee APIから給与データ取得
     */
    private function getFreeePayrollData(User $user, string $month): array
    {
        try {
            // freee APIから給与データ取得
            $payrollData = $this->freeeApiService->getPayroll(
                'access_token_here', // 実際のトークン取得処理
                (int) $user->employee_id, // stringをintにキャスト
                $month
            );

            // モックデータ（freee API連携前）
            return [
                'base_salary' => 160000, // freee基本給
                'total_hours' => 160,
                'overtime_hours' => 0,
                'freee_data' => $payrollData,
            ];

        } catch (\Exception $e) {
            Log::warning('Freee API data unavailable, using mock data', [
                'user_id' => $user->id,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            // freee API利用不可時のモックデータ
            return [
                'base_salary' => 160000,
                'total_hours' => 160,
                'overtime_hours' => 0,
                'freee_data' => null,
            ];
        }
    }

    /**
     * サービス別時給差額計算
     */
    private function calculateServiceAdjustments(User $user, string $month): array
    {
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // 該当月のサービス記録取得
        $serviceRecords = ServiceRecord::where('user_id', $user->id)
            ->whereBetween('service_date', [$startDate, $endDate])
            ->where('status', '実施済み')
            ->with(['serviceType'])
            ->get();

        $totalAdjustment = 0;
        $breakdown = [];

        // サービス種別ごとに集計
        $serviceTotals = [];
        foreach ($serviceRecords as $record) {
            $serviceTypeId = $record->service_type_id;
            $hours = $record->service_minutes / 60; // 分を時間に変換
            $serviceRate = $record->serviceType->hourly_rate;

            if (!isset($serviceTotals[$serviceTypeId])) {
                $serviceTotals[$serviceTypeId] = [
                    'service_type' => $record->serviceType->name,
                    'hours' => 0,
                    'rate' => $serviceRate,
                    'adjustment' => 0,
                ];
            }

            $serviceTotals[$serviceTypeId]['hours'] += $hours;
        }

        // 各サービス種別の調整額計算
        foreach ($serviceTotals as $serviceData) {
            // 基本時給（仮に1000円とする）
            $baseRate = 1000;
            $adjustmentPerHour = $serviceData['rate'] - $baseRate;
            $adjustment = $serviceData['hours'] * $adjustmentPerHour;

            $breakdown[] = [
                'service_type' => $serviceData['service_type'],
                'hours' => round($serviceData['hours'], 1),
                'rate' => $serviceData['rate'],
                'adjustment' => round($adjustment),
            ];

            $totalAdjustment += $adjustment;
        }

        return [
            'total_adjustment' => round($totalAdjustment),
            'breakdown' => $breakdown,
        ];
    }
}
