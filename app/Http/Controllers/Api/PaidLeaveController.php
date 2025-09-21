<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PaidLeaveRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaidLeaveController extends Controller
{
    /**
     * 個別有給休暇状況取得
     * GET /api/paid-leave/{userId}
     */
    public function getPaidLeaveStatus(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            // 現在の年度を取得
            $currentYear = now()->year;
            
            // 該当ユーザーの有給休暇記録取得
            $paidLeaveRecord = PaidLeaveRecord::where('user_id', $userId)
                ->where('fiscal_year', $currentYear)
                ->first();

            // 有給休暇記録が存在しない場合は初期化
            if (!$paidLeaveRecord) {
                $paidLeaveRecord = $this->initializePaidLeaveRecord($user, $currentYear);
            }

            // 年5日取得義務の進捗確認
            $fiveDayProgress = $this->calculateFiveDayProgress($paidLeaveRecord);

            $result = [
                'user_id' => $user->id,
                'employee_name' => $user->name,
                'employee_id' => $user->employee_id,
                'fiscal_year' => $currentYear,
                'granted_days' => $paidLeaveRecord->granted_days,
                'used_days' => $paidLeaveRecord->used_days,
                'remaining_days' => $paidLeaveRecord->remaining_days,
                'grant_date' => $paidLeaveRecord->grant_date,
                'expiry_date' => $paidLeaveRecord->expiry_date,
                'five_day_obligation_met' => $paidLeaveRecord->five_day_obligation_met,
                'five_day_progress' => $fiveDayProgress,
                'updated_at' => $paidLeaveRecord->updated_at->toISOString(),
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Paid leave status fetch failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '有給休暇状況の取得に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 全従業員有給休暇一覧
     * GET /api/paid-leave/all
     */
    public function getAllPaidLeaveStatus(Request $request)
    {
        try {
            $currentYear = now()->year;
            
            // デバッグログ
            Log::info('getAllPaidLeaveStatus called', ['currentYear' => $currentYear]);
            
            // 全従業員の有給休暇状況取得
            $users = User::where('is_active', true)->get();
            Log::info('Users found', ['count' => $users->count()]);
            
            if ($users->isEmpty()) {
                return response()->json([
                    'error' => 'アクティブな従業員が見つかりません。',
                    'message' => 'is_active=trueの従業員が存在しません。',
                ], 404);
            }
            
            $paidLeaveList = [];

            foreach ($users as $user) {
                $paidLeaveRecord = PaidLeaveRecord::where('user_id', $user->id)
                    ->where('fiscal_year', $currentYear)
                    ->first();

                if (!$paidLeaveRecord) {
                    $paidLeaveRecord = $this->initializePaidLeaveRecord($user, $currentYear);
                }

                $fiveDayProgress = $this->calculateFiveDayProgress($paidLeaveRecord);

                $paidLeaveList[] = [
                    'user_id' => $user->id,
                    'employee_name' => $user->name,
                    'employee_id' => $user->employee_id,
                    'fiscal_year' => $currentYear,
                    'granted_days' => $paidLeaveRecord->granted_days,
                    'used_days' => $paidLeaveRecord->used_days,
                    'remaining_days' => $paidLeaveRecord->remaining_days,
                    'five_day_obligation_met' => $paidLeaveRecord->five_day_obligation_met,
                    'five_day_progress' => $fiveDayProgress,
                    'expiry_date' => $paidLeaveRecord->expiry_date,
                ];
            }

            return response()->json([
                'fiscal_year' => $currentYear,
                'employees' => $paidLeaveList,
                'total_employees' => count($paidLeaveList),
                'five_day_compliance_rate' => $this->calculateFiveDayComplianceRate($paidLeaveList),
            ]);

        } catch (\Exception $e) {
            Log::error('All paid leave status fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => '全従業員有給休暇状況の取得に失敗しました。',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * 有給休暇使用記録
     * POST /api/paid-leave/use
     */
    public function usePaidLeave(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'used_days' => 'required|numeric|min:0.5|max:20',
            'usage_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $userId = $request->user_id;
            $usedDays = $request->used_days;
            $usageDate = Carbon::parse($request->usage_date);
            $currentYear = $usageDate->year;

            // 有給休暇記録取得
            $paidLeaveRecord = PaidLeaveRecord::where('user_id', $userId)
                ->where('fiscal_year', $currentYear)
                ->first();

            if (!$paidLeaveRecord) {
                $user = User::findOrFail($userId);
                $paidLeaveRecord = $this->initializePaidLeaveRecord($user, $currentYear);
            }

            // 残日数チェック
            if ($paidLeaveRecord->remaining_days < $usedDays) {
                return response()->json([
                    'error' => '有給休暇の残日数が不足しています。',
                    'remaining_days' => $paidLeaveRecord->remaining_days,
                    'requested_days' => $usedDays,
                ], 400);
            }

            // 有給休暇使用記録更新
            $paidLeaveRecord->used_days += $usedDays;
            $paidLeaveRecord->remaining_days -= $usedDays;

            // 年5日取得義務の確認
            if ($paidLeaveRecord->used_days >= 5 && !$paidLeaveRecord->five_day_obligation_met) {
                $paidLeaveRecord->five_day_obligation_met = true;
            }

            $paidLeaveRecord->save();

            Log::info('Paid leave used', [
                'user_id' => $userId,
                'used_days' => $usedDays,
                'usage_date' => $usageDate,
                'remaining_days' => $paidLeaveRecord->remaining_days,
            ]);

            return response()->json([
                'message' => '有給休暇の使用記録を更新しました。',
                'paid_leave_record' => [
                    'user_id' => $userId,
                    'used_days' => $paidLeaveRecord->used_days,
                    'remaining_days' => $paidLeaveRecord->remaining_days,
                    'five_day_obligation_met' => $paidLeaveRecord->five_day_obligation_met,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Paid leave usage failed', [
                'user_id' => $request->user_id,
                'used_days' => $request->used_days,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '有給休暇の使用記録に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 年5日取得義務チェック
     * GET /api/paid-leave/five-day-check
     */
    public function checkFiveDayObligation(Request $request)
    {
        try {
            $currentYear = now()->year;
            
            // 年5日取得義務未達成の従業員を取得
            $nonCompliantEmployees = PaidLeaveRecord::where('fiscal_year', $currentYear)
                ->where('five_day_obligation_met', false)
                ->with('user')
                ->get();

            $result = [
                'fiscal_year' => $currentYear,
                'check_date' => now()->toISOString(),
                'non_compliant_employees' => [],
                'total_non_compliant' => $nonCompliantEmployees->count(),
            ];

            foreach ($nonCompliantEmployees as $record) {
                $result['non_compliant_employees'][] = [
                    'user_id' => $record->user_id,
                    'employee_name' => $record->user->name,
                    'employee_id' => $record->user->employee_id,
                    'used_days' => $record->used_days,
                    'remaining_days' => $record->remaining_days,
                    'days_needed' => max(0, 5 - $record->used_days),
                    'expiry_date' => $record->expiry_date,
                ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Five day obligation check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '年5日取得義務チェックに失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 年次有給付与処理
     * PUT /api/paid-leave/{userId}/grant
     */
    public function grantPaidLeave(Request $request, $userId)
    {
        $request->validate([
            'granted_days' => 'required|numeric|min:0|max:40',
            'fiscal_year' => 'required|integer|min:2020|max:2030',
        ]);

        try {
            $user = User::findOrFail($userId);
            $grantedDays = $request->granted_days;
            $fiscalYear = $request->fiscal_year;

            // 既存の有給休暇記録を取得または作成
            $paidLeaveRecord = PaidLeaveRecord::updateOrCreate(
                [
                    'user_id' => $userId,
                    'fiscal_year' => $fiscalYear,
                ],
                [
                    'granted_days' => $grantedDays,
                    'used_days' => 0,
                    'remaining_days' => $grantedDays,
                    'grant_date' => now(),
                    'expiry_date' => Carbon::create($fiscalYear + 1, 12, 31),
                    'five_day_obligation_met' => false,
                ]
            );

            Log::info('Paid leave granted', [
                'user_id' => $userId,
                'granted_days' => $grantedDays,
                'fiscal_year' => $fiscalYear,
            ]);

            return response()->json([
                'message' => '年次有給休暇を付与しました。',
                'paid_leave_record' => [
                    'user_id' => $userId,
                    'employee_name' => $user->name,
                    'granted_days' => $paidLeaveRecord->granted_days,
                    'remaining_days' => $paidLeaveRecord->remaining_days,
                    'grant_date' => $paidLeaveRecord->grant_date,
                    'expiry_date' => $paidLeaveRecord->expiry_date,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Paid leave grant failed', [
                'user_id' => $userId,
                'granted_days' => $request->granted_days,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '年次有給休暇の付与に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 有給休暇記録の初期化
     */
    private function initializePaidLeaveRecord(User $user, int $fiscalYear): PaidLeaveRecord
    {
        // 労働日数に基づく有給日数計算（簡易版）
        $grantedDays = $this->calculateGrantedDays($user, $fiscalYear);

        return PaidLeaveRecord::create([
            'user_id' => $user->id,
            'granted_days' => $grantedDays,
            'used_days' => 0,
            'remaining_days' => $grantedDays,
            'grant_date' => now(),
            'expiry_date' => Carbon::create($fiscalYear + 1, 12, 31),
            'fiscal_year' => $fiscalYear,
            'five_day_obligation_met' => false,
        ]);
    }

    /**
     * 付与日数の計算（簡易版）
     */
    private function calculateGrantedDays(User $user, int $fiscalYear): float
    {
        // 簡易計算：勤続年数に応じた付与日数
        // 実際の実装では、労働日数や勤務時間を考慮する必要があります
        return 10.0; // デフォルト10日
    }

    /**
     * 年5日取得義務の進捗確認
     */
    private function calculateFiveDayProgress(PaidLeaveRecord $record): array
    {
        $usedDays = $record->used_days;
        $isMet = $record->five_day_obligation_met;
        $daysNeeded = max(0, 5 - $usedDays);

        return [
            'is_met' => $isMet,
            'used_days' => $usedDays,
            'days_needed' => $daysNeeded,
            'progress_percentage' => min(100, ($usedDays / 5) * 100),
            'status' => $isMet ? 'compliant' : ($usedDays >= 3 ? 'warning' : 'at_risk'),
        ];
    }

    /**
     * 年5日取得義務遵守率の計算
     */
    private function calculateFiveDayComplianceRate(array $paidLeaveList): float
    {
        if (empty($paidLeaveList)) {
            return 0.0;
        }

        $compliantCount = array_filter($paidLeaveList, function ($record) {
            return $record['five_day_obligation_met'];
        });

        return (count($compliantCount) / count($paidLeaveList)) * 100;
    }
}
