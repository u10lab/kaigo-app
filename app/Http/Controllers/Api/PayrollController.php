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

        Log::info('Payroll calculation started', [
            'month' => $month,
            'user_id' => $userId
        ]);

        try {
            // ユーザー情報取得
            $user = User::findOrFail($userId);
            Log::info('User found', [
                'user_id' => $user->id,
                'name' => $user->name,
                'employee_id' => $user->employee_id
            ]);

            // freee APIから基本給・勤務時間データ取得
            $freeeData = $this->getFreeePayrollData($user, $month);
            Log::info('Freee payroll data retrieved', $freeeData);

            // サービス記録からサービス別時給差額計算
            $serviceAdjustments = $this->calculateServiceAdjustments($user, $month, $freeeData['base_hourly_rate']);
            Log::info('Service adjustments calculated', $serviceAdjustments);

            // 総給与計算（基本給 + 残業代 + サービス調整）
            $totalSalary = $freeeData['base_salary'] + ($freeeData['overtime_pay'] ?? 0) + $serviceAdjustments['total_adjustment'];
            Log::info('Total salary calculated', ['total_salary' => $totalSalary]);

            // レスポンス作成
            $result = [
                'user_id' => $user->id,
                'employee_name' => $user->name,
                'employee_id' => $user->employee_id,
                'month' => $month,
                'base_salary' => $freeeData['base_salary'],
                'overtime_pay' => $freeeData['overtime_pay'] ?? 0,
                'service_adjustments' => $serviceAdjustments['total_adjustment'],
                'total_salary' => $totalSalary,
                'total_hours' => $freeeData['total_hours'],
                'overtime_hours' => $freeeData['overtime_hours'],
                'pay_calc_type' => $freeeData['pay_calc_type'],
                'base_hourly_rate' => $freeeData['base_hourly_rate'],
                'service_breakdown' => $serviceAdjustments['breakdown'],
                'calculated_at' => now()->toISOString(),
                'freee_payroll_statement' => $freeeData['freee_data'],
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

        return $this->calculate($request);
    }

    /**
     * freee APIから給与データ取得（給与明細API使用）
     */
    private function getFreeePayrollData(User $user, string $month): array
    {
        if (!$user->employee_id) {
            throw new \Exception('従業員IDが設定されていません。');
        }

        try {
            $monthDate = Carbon::createFromFormat('Y-m', $month);
            $yearParam = (int) $monthDate->format('Y');
            $monthParam = (int) $monthDate->format('m');

            $payrollStatement = $this->freeeApiService->getEmployeePayrollStatement(
                (int) $user->employee_id,
                $yearParam,
                $monthParam
            );

            // 給与明細データが存在しない場合のフォールバック
            if (empty($payrollStatement['employee_payroll_statement'])) {
                return [
                    'base_salary' => 0,
                    'overtime_pay' => 0,
                    'total_hours' => 0,
                    'overtime_hours' => 0,
                    'pay_calc_type' => 'hourly',
                    'freee_data' => $payrollStatement,
                    'error' => '給与明細が存在しません。該当月の給与計算が完了していない可能性があります。',
                ];
            }

            $statement = $payrollStatement['employee_payroll_statement'];
            
            // 給与明細から各種金額を抽出
            $baseHourlyRate = (int) ($statement['basic_pay_amount'] ?? 0); // 時給
            $overtimePay = 0;
            // normal_work_timeは分単位のため、60で割って時間に変換
            $totalHours = (float) ($statement['normal_work_time'] ?? 0) / 60;
            $overtimeHours = 0;

            // payments配列から基本給以外の支払いを取得
            if (isset($statement['payments']) && is_array($statement['payments'])) {
                foreach ($statement['payments'] as $payment) {
                    $paymentName = $payment['name'] ?? '';
                    $amount = (float) ($payment['amount'] ?? 0);
                    
                    // 基本給以外の支払い（残業代など）
                    if (strpos($paymentName, '基本給') === false) {
                        $overtimePay += $amount;
                    }
                }
            }

            // overtime_pays配列から残業時間と残業代を取得
            if (isset($statement['overtime_pays']) && is_array($statement['overtime_pays'])) {
                foreach ($statement['overtime_pays'] as $overtime) {
                    // 時間（分）を時間に変換
                    if (isset($overtime['time'])) {
                        $overtimeHours += (float) $overtime['time'] / 60;
                    }
                    // 残業代も追加（timeがnullの場合もあるため）
                    if (isset($overtime['amount'])) {
                        $overtimePay += (float) $overtime['amount'];
                    }
                }
            }

            // 基本給合計 = 時給 × 総労働時間
            $baseSalary = $baseHourlyRate * $totalHours;

            return [
                'base_salary' => (int) $baseSalary,
                'base_hourly_rate' => $baseHourlyRate,
                'overtime_pay' => (int) $overtimePay,
                'total_hours' => round($totalHours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'pay_calc_type' => $statement['pay_calc_type'] ?? 'hourly',
                'freee_data' => $payrollStatement,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch payroll statement', [
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            // エラー時は0円でフォールバック
            return [
                'base_salary' => 0,
                'overtime_pay' => 0,
                'total_hours' => 0,
                'overtime_hours' => 0,
                'pay_calc_type' => 'hourly',
                'freee_data' => null,
                'error' => '給与明細の取得に失敗しました: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * サービス別時給差額計算
     */
    private function calculateServiceAdjustments(User $user, string $month, float $baseHourlyRate): array
    {
        // freee給与計算期間に合わせる（15日締め）
        // 例: 2025-10 → 2025-09-16 から 2025-10-15 まで
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->subMonth()->day(16); // 前月16日
        $endDate = $monthDate->copy()->day(15); // 当月15日

        // 該当月のサービス記録取得
        $serviceRecords = ServiceRecord::where('user_id', $user->id)
            ->whereBetween('service_date', [$startDate, $endDate])
            ->where('status', '実施済み')
            ->with(['serviceType'])
            ->get();

        Log::info('Service records found', [
            'user_id' => $user->id,
            'month' => $month,
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'count' => $serviceRecords->count(),
            'base_hourly_rate' => $baseHourlyRate . '円/時'
        ]);

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
            $adjustmentPerHour = $serviceData['rate'] - $baseHourlyRate;
            $adjustment = $serviceData['hours'] * $adjustmentPerHour;

            $breakdown[] = [
                'service_type' => $serviceData['service_type'],
                'hours' => round($serviceData['hours'], 1),
                'rate' => $serviceData['rate'],
                'base_rate' => $baseHourlyRate,
                'adjustment' => round($adjustment),
            ];

            $totalAdjustment += $adjustment;
        }

        return [
            'total_adjustment' => round($totalAdjustment),
            'breakdown' => $breakdown,
            'base_hourly_rate' => $baseHourlyRate,
        ];
    }
}
