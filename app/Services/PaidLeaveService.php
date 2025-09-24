<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaidLeaveRecord;
use App\Models\PaidLeaveApplication;
use App\Models\FreeeToken;
use Illuminate\Support\Facades\Log;

class PaidLeaveService
{
    protected $freeeApiService;

    public function __construct(FreeeApiService $freeeApiService)
    {
        $this->freeeApiService = $freeeApiService;
    }

    /**
     * 勤続年数による有給休暇付与日数を取得
     */
    public function getGrantedDaysByTenure(int $tenureMonths): int
    {
        if ($tenureMonths < 6) {
            return 0; // 6ヶ月未満は付与なし
        } elseif ($tenureMonths < 18) {
            return 10; // 6ヶ月以上1年6ヶ月未満
        } elseif ($tenureMonths < 30) {
            return 11; // 1年6ヶ月以上2年6ヶ月未満
        } elseif ($tenureMonths < 42) {
            return 12; // 2年6ヶ月以上3年6ヶ月未満
        } elseif ($tenureMonths < 54) {
            return 14; // 3年6ヶ月以上4年6ヶ月未満
        } elseif ($tenureMonths < 66) {
            return 16; // 4年6ヶ月以上5年6ヶ月未満
        } elseif ($tenureMonths < 78) {
            return 18; // 5年6ヶ月以上6年6ヶ月未満
        } else {
            return 20; // 6年6ヶ月以上
        }
    }

    /**
     * ユーザーの有給休暇状況を取得
     */
    public function getPaidLeaveStatus(int $userId, int $fiscalYear = null): array
    {
        $fiscalYear = $fiscalYear ?? date('Y');
        
        $record = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$record) {
            // レコードが存在しない場合は新規作成
            $record = $this->createPaidLeaveRecord($userId, $fiscalYear);
        }

        return [
            'user_id' => $userId,
            'fiscal_year' => $fiscalYear,
            'granted_days' => $record->granted_days,
            'used_days' => $record->used_days,
            'remaining_days' => $record->remaining_days,
            'grant_date' => $record->grant_date,
            'expiry_date' => $record->expiry_date,
            'five_day_obligation_met' => $record->five_day_obligation_met,
            'annual_requirement_used' => min($record->used_days, 5), // 年5日取得義務の達成日数
        ];
    }

    /**
     * 有給休暇レコードを新規作成
     */
    public function createPaidLeaveRecord(int $userId, int $fiscalYear): PaidLeaveRecord
    {
        $user = User::findOrFail($userId);
        
        // 入社日から勤続期間を計算
        $employmentDate = $user->hire_date ?? $user->created_at;
        $currentDate = now();
        $tenureMonths = $employmentDate->diffInMonths($currentDate);
        
        $grantedDays = $this->getGrantedDaysByTenure($tenureMonths);
        $grantDate = $employmentDate->copy()->addMonths(6);
        $expiryDate = $grantDate->copy()->addYear();

        // 既存レコードがあるかチェック
        $existingRecord = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if ($existingRecord) {
            return $existingRecord;
        }

        $record = PaidLeaveRecord::create([
            'user_id' => $userId,
            'granted_days' => $grantedDays,
            'used_days' => 0,
            'remaining_days' => $grantedDays,
            'grant_date' => $grantDate,
            'expiry_date' => $expiryDate,
            'fiscal_year' => (string)$fiscalYear,
            'five_day_obligation_met' => false,
        ]);

        Log::info('Paid leave record created', [
            'user_id' => $userId,
            'fiscal_year' => $fiscalYear,
            'granted_days' => $grantedDays,
            'grant_date' => $grantDate,
            'expiry_date' => $expiryDate
        ]);

        return $record;
    }

    /**
     * 年次有給休暇取得義務の達成状況をチェック
     */
    public function checkAnnualObligationStatus(int $userId, int $fiscalYear = null): array
    {
        $fiscalYear = $fiscalYear ?? date('Y');
        
        $record = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$record) {
            return [
                'user_id' => $userId,
                'fiscal_year' => $fiscalYear,
                'used_days' => 0,
                'required_days' => 5,
                'obligation_met' => false,
                'alert_level' => 'high',
                'remaining_days_in_year' => $this->getRemainingDaysInYear(),
            ];
        }

        $usedDays = $record->used_days;
        $requiredDays = 5;
        $obligationMet = $usedDays >= $requiredDays;
        
        // アラートレベルの決定
        $alertLevel = $this->getAlertLevel($usedDays);

        return [
            'user_id' => $userId,
            'fiscal_year' => $fiscalYear,
            'used_days' => $usedDays,
            'required_days' => $requiredDays,
            'obligation_met' => $obligationMet,
            'alert_level' => $alertLevel,
            'remaining_days_in_year' => $this->getRemainingDaysInYear(),
        ];
    }

    /**
     * アラートレベルを取得
     */
    private function getAlertLevel(int $usedDays): string
    {
        if ($usedDays >= 5) {
            return 'none'; // 達成済み
        } elseif ($usedDays >= 3) {
            return 'low'; // 低リスク
        } elseif ($usedDays >= 1) {
            return 'medium'; // 中リスク
        } else {
            return 'high'; // 高リスク
        }
    }

    /**
     * 年度末までの残り日数を取得
     */
    private function getRemainingDaysInYear(): int
    {
        $currentDate = now();
        $yearEnd = $currentDate->copy()->endOfYear();
        return $currentDate->diffInDays($yearEnd);
    }

    /**
     * 全従業員の取得義務達成状況を取得（管理者用）
     */
    public function getAllUsersObligationStatus(int $fiscalYear = null): array
    {
        $fiscalYear = $fiscalYear ?? date('Y');
        
        $users = User::where('role', 'helper')->get();
        $statuses = [];

        foreach ($users as $user) {
            $statuses[] = $this->checkAnnualObligationStatus($user->id, $fiscalYear);
        }

        return $statuses;
    }

    /**
     * 未達成者アラートを取得
     */
    public function getUnmetObligationAlerts(int $fiscalYear = null): array
    {
        $statuses = $this->getAllUsersObligationStatus($fiscalYear);
        $alerts = [];

        foreach ($statuses as $status) {
            if (!$status['obligation_met']) {
                $user = User::find($status['user_id']);
                $alerts[] = [
                    'user_id' => $status['user_id'],
                    'user_name' => $user->name,
                    'used_days' => $status['used_days'],
                    'required_days' => $status['required_days'],
                    'alert_level' => $status['alert_level'],
                    'remaining_days_in_year' => $status['remaining_days_in_year'],
                ];
            }
        }

        return $alerts;
    }

    /**
     * freeeから従業員マスタを同期して有給休暇レコードを更新
     */
    public function syncEmployeesFromFreee(): array
    {
        try {
            $token = FreeeToken::first();
            if (!$token) {
                throw new \Exception('freee認証情報が見つかりません。');
            }

            try {
                $accessToken = $token->getValidAccessToken();
                if (!$accessToken) {
                    throw new \Exception('有効なアクセストークンがありません。freee認証を再実行してください。');
                }
            } catch (\Exception $tokenError) {
                throw new \Exception('アクセストークンの取得に失敗しました。freee認証を再実行してください。エラー: ' . $tokenError->getMessage());
            }

            // freeeから従業員情報を取得
            $freeeEmployees = $this->freeeApiService->getEmployeesForPaidLeave($accessToken);
            
            $syncedCount = 0;
            $errors = [];

            foreach ($freeeEmployees['employees'] as $freeeEmployee) {
                try {
                    // ローカルのユーザーを検索（employee_idでマッチング）
                    $user = User::where('employee_id', $freeeEmployee['id'])->first();
                    
                    if ($user) {
                        // 入社日を更新（freeeから取得）
                        if (isset($freeeEmployee['entry_date'])) {
                            $oldHireDate = $user->hire_date;
                            $user->update(['hire_date' => $freeeEmployee['entry_date']]);
                            
                            Log::info('Updated employee hire date from freee', [
                                'user_id' => $user->id,
                                'employee_id' => $freeeEmployee['id'],
                                'old_hire_date' => $oldHireDate,
                                'new_hire_date' => $freeeEmployee['entry_date']
                            ]);
                        }
                        
                        // 有給休暇レコードを更新
                        $this->updatePaidLeaveRecordFromFreee($user->id, $freeeEmployee);
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "従業員ID {$freeeEmployee['id']}: {$e->getMessage()}";
                }
            }

            return [
                'synced_count' => $syncedCount,
                'total_employees' => count($freeeEmployees['employees']),
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Sync employees from freee failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * freeeの勤怠実績から有給休暇の消化日数を取得
     */
    public function getUsedDaysFromFreee(int $userId, int $year): int
    {
        try {
            $user = User::findOrFail($userId);
            if (!$user->employee_id) {
                return 0;
            }

            $token = FreeeToken::first();
            if (!$token) {
                return 0;
            }

            $accessToken = $token->getValidAccessToken();
            if (!$accessToken) {
                return 0;
            }

            // freeeから勤怠記録を取得
            $workRecords = $this->freeeApiService->getWorkRecordsForPaidLeave($accessToken, $user->employee_id, $year);
            
            $usedDays = 0;
            foreach ($workRecords['work_records'] as $record) {
                if (isset($record['absence_reason']) && $record['absence_reason'] === 'PAID_HOLIDAY') {
                    $usedDays++;
                }
            }

            return $usedDays;
        } catch (\Exception $e) {
            Log::error('Get used days from freee failed', [
                'user_id' => $userId,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * 正確な残日数を計算（freeeの実績を含む）
     */
    public function getAccurateRemainingDays(int $userId, int $fiscalYear = null): array
    {
        $fiscalYear = $fiscalYear ?? date('Y');
        
        // アプリで計算した付与日数
        $record = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$record) {
            $record = $this->createPaidLeaveRecord($userId, $fiscalYear);
        }

        // freeeから実際の消化日数を取得
        $usedDaysFromFreee = $this->getUsedDaysFromFreee($userId, $fiscalYear);

        // 正確な残日数を計算
        $accurateRemainingDays = $record->granted_days - $usedDaysFromFreee;

        return [
            'user_id' => $userId,
            'fiscal_year' => $fiscalYear,
            'granted_days' => $record->granted_days,
            'used_days_from_freee' => $usedDaysFromFreee,
            'accurate_remaining_days' => max(0, $accurateRemainingDays),
            'last_synced_at' => now(),
        ];
    }

    /**
     * freeeの従業員情報から有給休暇レコードを更新
     */
    private function updatePaidLeaveRecordFromFreee(int $userId, array $freeeEmployee): void
    {
        $user = User::findOrFail($userId);
        
        // 入社日から勤続期間を計算（freee APIのentry_dateを優先使用）
        $employmentDate = isset($freeeEmployee['entry_date']) 
            ? \Carbon\Carbon::parse($freeeEmployee['entry_date'])
            : ($user->hire_date ?? $user->created_at);
        $currentDate = now();
        $tenureMonths = $employmentDate->diffInMonths($currentDate);
        
        $grantedDays = $this->getGrantedDaysByTenure($tenureMonths);
        
        Log::info('Updating paid leave record from freee', [
            'user_id' => $userId,
            'employment_date' => $employmentDate->format('Y-m-d'),
            'tenure_months' => $tenureMonths,
            'granted_days' => $grantedDays
        ]);
        $currentYear = date('Y');
        
        // 現在年度のレコードを取得または作成
        $record = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $currentYear)
            ->first();
            
        if ($record) {
            // 既存レコードの付与日数を更新
            $oldGrantedDays = $record->granted_days;
            $oldRemainingDays = $record->remaining_days;
            
            $record->update([
                'granted_days' => $grantedDays,
                'remaining_days' => $grantedDays - $record->used_days,
            ]);
            
            Log::info('Updated existing paid leave record', [
                'user_id' => $userId,
                'old_granted_days' => $oldGrantedDays,
                'new_granted_days' => $grantedDays,
                'old_remaining_days' => $oldRemainingDays,
                'new_remaining_days' => $grantedDays - $record->used_days,
                'used_days' => $record->used_days
            ]);
        } else {
            // 新規レコードを作成
            $grantDate = $employmentDate->copy()->addMonths(6);
            $expiryDate = $grantDate->copy()->addYear();
            
            PaidLeaveRecord::create([
                'user_id' => $userId,
                'granted_days' => $grantedDays,
                'used_days' => 0,
                'remaining_days' => $grantedDays,
                'grant_date' => $grantDate,
                'expiry_date' => $expiryDate,
                'fiscal_year' => $currentYear,
                'five_day_obligation_met' => false,
            ]);
        }
    }

    /**
     * 申請承認後の有給休暇記録を更新
     */
    public function updatePaidLeaveRecordAfterApproval(int $userId, int $usedDays): void
    {
        $currentYear = date('Y');
        
        $record = PaidLeaveRecord::where('user_id', $userId)
            ->where('fiscal_year', $currentYear)
            ->first();
            
        if ($record) {
            $record->increment('used_days', $usedDays);
            $record->decrement('remaining_days', $usedDays);
            
            // 年5日取得義務の達成状況を更新
            if ($record->used_days >= 5) {
                $record->update(['five_day_obligation_met' => true]);
            }
        }
    }

    /**
     * 承認された有給休暇をfreee勤怠APIに登録
     */
    public function registerPaidLeaveToFreee(PaidLeaveApplication $application): void
    {
        try {
            $user = $application->user;
            if (!$user->employee_id) {
                Log::warning('Cannot register paid leave to freee: employee_id not set', [
                    'user_id' => $user->id,
                    'application_id' => $application->id
                ]);
                return;
            }

            $token = FreeeToken::first();
            if (!$token) {
                Log::warning('Cannot register paid leave to freee: no token found');
                return;
            }

            $accessToken = $token->getValidAccessToken();
            if (!$accessToken) {
                Log::warning('Cannot register paid leave to freee: no valid access token');
                return;
            }

            // 申請期間の各日付に対してfreee勤怠APIに登録
            $startDate = \Carbon\Carbon::parse($application->start_date);
            $endDate = \Carbon\Carbon::parse($application->end_date);
            
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                // 土日をスキップ
                if ($currentDate->isWeekday()) {
                    $this->registerSingleDayToFreee($accessToken, $user->employee_id, $currentDate->format('Y-m-d'));
                }
                $currentDate->addDay();
            }

            Log::info('Paid leave registered to freee', [
                'application_id' => $application->id,
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'start_date' => $application->start_date,
                'end_date' => $application->end_date
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register paid leave to freee', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 単一日の有給休暇をfreee勤怠APIに登録
     */
    private function registerSingleDayToFreee(string $accessToken, int $employeeId, string $date): void
    {
        try {
            $this->freeeApiService->registerPaidLeaveToAttendance($accessToken, $employeeId, $date);
        } catch (\Exception $e) {
            Log::error('Failed to register single day to freee', [
                'employee_id' => $employeeId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
        }
    }
}