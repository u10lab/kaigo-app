<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PaidLeaveApplication;
use App\Services\PaidLeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaidLeaveController extends Controller
{
    protected $paidLeaveService;

    public function __construct(PaidLeaveService $paidLeaveService)
    {
        $this->paidLeaveService = $paidLeaveService;
    }

    /**
     * 有給休暇状況を取得
     */
    public function getStatus(Request $request)
    {
        try {
            $userId = $request->get('user_id', Auth::id());
            $fiscalYear = $request->get('fiscal_year');
            
            // 管理者以外は自分の情報のみアクセス可能
            if (Auth::user()->role !== 'admin' && $userId != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'アクセス権限がありません。'
                ], 403);
            }

            $status = $this->paidLeaveService->getPaidLeaveStatus($userId, $fiscalYear);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get paid leave status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '有給休暇状況の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 有給休暇申請
     */
    public function apply(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $userId = Auth::id();
            
            // 申請日数の計算
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
            $requestedDays = $startDate->diffInDays($endDate) + 1;

            // 有給休暇残日数の確認
            $status = $this->paidLeaveService->getPaidLeaveStatus($userId);
            if ($status['remaining_days'] < $requestedDays) {
                return response()->json([
                    'success' => false,
                    'message' => '有給休暇の残日数が不足しています。',
                    'remaining_days' => $status['remaining_days'],
                    'requested_days' => $requestedDays
                ], 400);
            }

            // 期間の重複チェック
            if (PaidLeaveApplication::hasOverlap($userId, $request->start_date, $request->end_date)) {
                return response()->json([
                    'success' => false,
                    'message' => '指定された期間に既に承認済みの有給休暇があります。',
                ], 400);
            }

            // 申請データをデータベースに保存
            $application = PaidLeaveApplication::create([
                'user_id' => $userId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'requested_days' => $requestedDays,
                'reason' => $request->reason,
                'leave_type' => 'paid_leave',
                'status' => 'pending',
            ]);

            Log::info('Paid leave application submitted', [
                'application_id' => $application->id,
                'user_id' => $userId,
                'requested_days' => $requestedDays,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]);

            return response()->json([
                'success' => true,
                'message' => '有給休暇申請が完了しました。',
                'data' => $application->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply paid leave', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '有給休暇申請に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 有給休暇申請を承認
     */
    public function approve(Request $request)
    {
        // 管理者のみ実行可能
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です。'
            ], 403);
        }

        $request->validate([
            'application_id' => 'required|integer',
            'approver_comment' => 'nullable|string|max:500',
        ]);

        try {
            $application = PaidLeaveApplication::findOrFail($request->application_id);
            
            if ($application->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'この申請は既に処理済みです。'
                ], 400);
            }

            // 申請を承認
            $application->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'approver_comment' => $request->approver_comment,
                'approved_at' => now(),
            ]);

            // 有給休暇レコードを更新
            $this->paidLeaveService->updatePaidLeaveRecordAfterApproval($application->user_id, $application->requested_days);
            
            // freee勤怠APIに有給休暇を登録
            $this->paidLeaveService->registerPaidLeaveToFreee($application);
            
            Log::info('Paid leave application approved', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'approver_id' => Auth::id(),
                'comment' => $request->approver_comment
            ]);

            return response()->json([
                'success' => true,
                'message' => '有給休暇申請を承認しました。',
                'data' => $application->load(['user', 'approver'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve paid leave application', [
                'application_id' => $request->application_id,
                'approver_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '申請承認に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 有給休暇申請を却下
     */
    public function reject(Request $request)
    {
        // 管理者のみ実行可能
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です。'
            ], 403);
        }

        $request->validate([
            'application_id' => 'required|integer',
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $application = PaidLeaveApplication::findOrFail($request->application_id);
            
            if ($application->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'この申請は既に処理済みです。'
                ], 400);
            }

            // 申請を却下
            $application->update([
                'status' => 'rejected',
                'approver_id' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
            ]);
            
            Log::info('Paid leave application rejected', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'rejector_id' => Auth::id(),
                'rejection_reason' => $request->rejection_reason
            ]);

            return response()->json([
                'success' => true,
                'message' => '有給休暇申請を却下しました。',
                'data' => $application->load(['user', 'approver'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject paid leave application', [
                'application_id' => $request->application_id,
                'rejector_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '申請却下に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 年次有給休暇取得義務の達成状況をチェック
     */
    public function getAnnualLeaveCheck(Request $request)
    {
        try {
            $fiscalYear = $request->get('fiscal_year');
            
            // 管理者のみ全従業員の状況を確認可能
            if (Auth::user()->role === 'admin') {
                $statuses = $this->paidLeaveService->getAllUsersObligationStatus($fiscalYear);
                $alerts = $this->paidLeaveService->getUnmetObligationAlerts($fiscalYear);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'all_statuses' => $statuses,
                        'unmet_alerts' => $alerts,
                        'summary' => [
                            'total_employees' => count($statuses),
                            'obligation_met' => count($statuses) - count($alerts),
                            'obligation_unmet' => count($alerts)
                        ]
                    ]
                ]);
            } else {
                // 一般従業員は自分の状況のみ確認可能
                $userId = Auth::id();
                $status = $this->paidLeaveService->checkAnnualObligationStatus($userId, $fiscalYear);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'personal_status' => $status
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get annual leave check', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '年次有給休暇取得状況の確認に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 全従業員の有給休暇状況を取得
     */
    public function getAllEmployeesStatus(Request $request)
    {
        try {
            $users = User::where('role', 'helper')->where('id', '!=', 1)->get(); // 管理者を除外
            $employeeStatuses = [];

            foreach ($users as $user) {
                $status = $this->paidLeaveService->getPaidLeaveStatus($user->id);
                // ユーザー名を追加
                $status['user_name'] = $user->name;
                $employeeStatuses[] = $status;
            }

            return response()->json([
                'success' => true,
                'data' => $employeeStatuses
            ]);
        } catch (\Exception $e) {
            \Log::error('Get all employees status error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '全従業員の有給休暇状況の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 申請一覧を取得
     */
    public function getApplications(Request $request)
    {
        try {
            $query = PaidLeaveApplication::with(['user', 'approver'])
                ->orderBy('created_at', 'desc');

            // 管理者以外は自分の申請のみ
            if (Auth::user()->role !== 'admin') {
                $query->where('user_id', Auth::id());
            }

            // ステータスフィルタ
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // ユーザーフィルタ（管理者のみ）
            if (Auth::user()->role === 'admin' && $request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $applications = $query->get();

            return response()->json([
                'success' => true,
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            Log::error('Get applications error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '申請一覧の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * freeeから従業員マスタを同期
     */
    public function syncEmployeesFromFreee(Request $request)
    {
        try {
            $result = $this->paidLeaveService->syncEmployeesFromFreee();
            
            return response()->json([
                'success' => true,
                'message' => '従業員マスタの同期が完了しました。',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Sync employees from freee failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '従業員マスタの同期に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 正確な残日数を取得（freee実績を含む）
     */
    public function getAccurateRemainingDays(Request $request)
    {
        $userId = $request->input('user_id', Auth::id());
        $fiscalYear = $request->input('fiscal_year', date('Y'));

        try {
            $result = $this->paidLeaveService->getAccurateRemainingDays($userId, $fiscalYear);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Get accurate remaining days failed', [
                'user_id' => $userId,
                'fiscal_year' => $fiscalYear,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '残日数の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}