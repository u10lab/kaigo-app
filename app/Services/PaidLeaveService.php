<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaidLeaveRecord;
use Illuminate\Support\Facades\Log;

class PaidLeaveService
{
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
        
        // 入社日から勤続期間を計算（仮に現在日を基準とする）
        $employmentDate = $user->created_at; // 仮の入社日としてcreated_atを使用
        $currentDate = now();
        $tenureMonths = $currentDate->diffInMonths($employmentDate);
        
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
        $currentMonth = (int)date('n');
        
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
}
