<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreeeToken;
use App\Services\FreeeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FreeeController extends Controller
{
    private FreeeApiService $freeeApiService;

    public function __construct(FreeeApiService $freeeApiService)
    {
        $this->freeeApiService = $freeeApiService;
    }

    /**
     * freee OAuth認証URLを取得
     */
    public function getAuthUrl()
    {
        $authUrl = $this->freeeApiService->getAuthorizationUrl();
        
        return response()->json([
            'auth_url' => $authUrl,
        ]);
    }

    /**
     * freee認証状態を取得
     */
    public function getAuthStatus()
    {
        try {
            $token = FreeeToken::first();
            
            if (!$token) {
                return response()->json([
                    'authenticated' => false,
                    'message' => 'freee認証情報が見つかりません。',
                    'auth_url' => $this->freeeApiService->getAuthorizationUrl()
                ]);
            }

            // トークンの有効性をチェック
            $isValid = false;
            $accessToken = null;
            
            if (!$token->isExpired()) {
                $isValid = true;
                $accessToken = $token->access_token;
            } else if ($token->refresh_token) {
                // リフレッシュトークンで更新を試行
                try {
                    $accessToken = $token->getValidAccessToken();
                    $isValid = true;
                } catch (\Exception $e) {
                    Log::error('Token refresh failed in getAuthStatus: ' . $e->getMessage());
                }
            }

            return response()->json([
                'authenticated' => $isValid,
                'has_access_token' => !is_null($accessToken),
                'expires_at' => $token->expires_at?->toISOString(),
                'company_id' => $token->company_id,
                'auth_url' => $isValid ? null : $this->freeeApiService->getAuthorizationUrl()
            ]);
        } catch (\Exception $e) {
            Log::error('Get auth status error: ' . $e->getMessage());
            return response()->json([
                'authenticated' => false,
                'message' => '認証状態の確認に失敗しました。',
                'auth_url' => $this->freeeApiService->getAuthorizationUrl()
            ], 500);
        }
    }

    /**
     * freee OAuth認証コールバック
     */
    public function callback(Request $request)
    {
        // デバッグ用：全リクエストパラメータをログ出力
        Log::info('Freee callback received', [
            'all_params' => $request->all(),
            'query_params' => $request->query(),
            'full_url' => $request->fullUrl(),
            'timestamp' => now()
        ]);
        
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');
        
        // エラーパラメータのチェック
        if ($error) {
            Log::error('Freee callback error parameter received', ['error' => $error]);
            return redirect('/admin?freee_auth=error&message=' . urlencode($error));
        }
        
        if (!$code) {
            Log::error('Freee callback: No authorization code received', [
                'all_params' => $request->all()
            ]);
            return redirect('/admin?freee_auth=error&message=' . urlencode('認証コードが取得できませんでした'));
        }

        try {
            // 許可コードをログに記録（デバッグ用）
            Log::info('Freee authorization code received', [
                'code' => $code,
                'state' => $state,
                'timestamp' => now()
            ]);
            
            // デバッグ用：許可コード取得確認
            Log::info('Authorization code successfully extracted', [
                'code_length' => strlen($code),
                'code_preview' => substr($code, 0, 10) . '...',
                'state' => $state
            ]);
            
            // 許可コードをアクセストークンに交換
            $tokenData = $this->freeeApiService->getAccessToken($code);
            
            // 認証成功後、管理者画面にリダイレクト
            return redirect('/admin?freee_auth=success&company_id=' . $tokenData['company_id']);
        } catch (\Exception $e) {
            // エラー時、管理者画面にリダイレクト（エラーメッセージ付き）
            Log::error('Freee callback error', [
                'error' => $e->getMessage(),
                'code' => $code,
                'state' => $state
            ]);
            return redirect('/admin?freee_auth=error&message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * 勤務開始打刻
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $token = FreeeToken::first();

        if (!$token) {
            return response()->json([
                'error' => 'freee認証が完了していません。',
            ], 400);
        }

        if (!$user->employee_id) {
            return response()->json([
                'error' => '従業員IDが設定されていません。管理者画面で従業員IDを更新してください。',
            ], 400);
        }

        try {
            $accessToken = $token->getValidAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'error' => '有効なアクセストークンがありません。管理者画面でfreee認証を再実行してください。',
                ], 400);
            }

            $result = $this->freeeApiService->clockIn($accessToken, (int)$user->employee_id);

            return response()->json([
                'message' => '勤務開始打刻が完了しました。',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Freee API clock in error', [
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 勤務終了打刻
     */
    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $token = FreeeToken::first();

        if (!$token) {
            return response()->json([
                'error' => 'freee認証が完了していません。',
            ], 400);
        }

        if (!$user->employee_id) {
            return response()->json([
                'error' => '従業員IDが設定されていません。管理者画面で従業員IDを更新してください。',
            ], 400);
        }

        try {
            $accessToken = $token->getValidAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'error' => '有効なアクセストークンがありません。管理者画面でfreee認証を再実行してください。',
                ], 400);
            }

            $result = $this->freeeApiService->clockOut($accessToken, (int)$user->employee_id);
            
            return response()->json([
                'message' => '勤務終了打刻が完了しました。',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Freee API clock out error', [
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * 従業員一覧取得
     */
    public function getEmployees(Request $request)
    {
        $token = FreeeToken::first();

        if (!$token) {
            return response()->json([
                'error' => 'freee認証が完了していません。',
            ], 400);
        }

        try {
            $accessToken = $token->getValidAccessToken();
            $result = $this->freeeApiService->getEmployees($accessToken, $token->company_id);
            
            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 従業員IDをfreee APIから取得して更新
     */
    public function updateEmployeeIds(Request $request)
    {
        $token = FreeeToken::first();

        if (!$token) {
            return response()->json([
                'error' => 'freee認証が完了していません。',
            ], 400);
        }

        try {
            $accessToken = $token->getValidAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'error' => '有効なアクセストークンがありません。',
                ], 400);
            }
            
            Log::info('Starting employee ID update process', [
                'company_id' => $token->company_id,
                'user_id' => Auth::id()
            ]);
            
            $employeesData = $this->freeeApiService->getEmployees($accessToken, $token->company_id);
            
            Log::info('Updating employee IDs from freee API', [
                'company_id' => $token->company_id,
                'employees_count' => count($employeesData['employees'] ?? [])
            ]);
            
            $updatedCount = 0;
            $notMatchedEmployees = [];
            $employees = $employeesData['employees'] ?? [];
            
            foreach ($employees as $employee) {
                $freeeEmployeeId = $employee['id'];
                $displayName = $employee['display_name'] ?? null;
                $employeeNum = $employee['num'] ?? null;
                
                Log::info('Processing freee employee', [
                    'freee_id' => $freeeEmployeeId,
                    'display_name' => $displayName,
                    'num' => $employeeNum
                ]);
                
                $user = null;
                
                // 方法1: display_nameでマッチング
                if ($displayName) {
                    $user = \App\Models\User::where('name', $displayName)->first();
                    if ($user) {
                        Log::info('Matched by display_name', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'freee_display_name' => $displayName
                        ]);
                    }
                }
                
                // 方法2: num（従業員番号）でマッチング（display_nameで見つからない場合）
                if (!$user && $employeeNum) {
                    $user = \App\Models\User::where('employee_id', $employeeNum)->first();
                    if ($user) {
                        Log::info('Matched by employee_num', [
                            'user_id' => $user->id,
                            'user_employee_id' => $user->employee_id,
                            'freee_num' => $employeeNum
                        ]);
                    }
                }
                
                if ($user) {
                    $oldEmployeeId = $user->employee_id;
                    $user->employee_id = $freeeEmployeeId;
                    $user->save();
                    $updatedCount++;
                    
                    Log::info('Updated user employee_id', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'old_employee_id' => $oldEmployeeId,
                        'new_employee_id' => $freeeEmployeeId,
                        'freee_display_name' => $displayName
                    ]);
                } else {
                    $notMatchedEmployees[] = [
                        'freee_id' => $freeeEmployeeId,
                        'display_name' => $displayName,
                        'num' => $employeeNum
                    ];
                    
                    Log::warning('Could not match freee employee to local user', [
                        'freee_id' => $freeeEmployeeId,
                        'display_name' => $displayName,
                        'num' => $employeeNum
                    ]);
                }
            }
            
            return response()->json([
                'message' => '従業員IDの更新が完了しました。',
                'updated_count' => $updatedCount,
                'total_employees' => count($employees),
                'not_matched_employees' => $notMatchedEmployees
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update employee IDs', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 手動で従業員IDをマッピング
     */
    public function mapEmployeeId(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'freee_employee_id' => 'required|integer'
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $oldEmployeeId = $user->employee_id;
            $user->employee_id = $request->freee_employee_id;
            $user->save();

            Log::info('Manually mapped employee ID', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'old_employee_id' => $oldEmployeeId,
                'new_employee_id' => $request->freee_employee_id
            ]);

            return response()->json([
                'message' => '従業員IDの手動マッピングが完了しました。',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to manually map employee ID', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'freee_employee_id' => $request->freee_employee_id
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 給与データ取得
     */
    public function getPayroll(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', now()->format('Y-m'));
        $token = FreeeToken::first();

        if (!$token) {
            return response()->json([
                'error' => 'freee認証が完了していません。',
            ], 400);
        }

        try {
            $accessToken = $token->getValidAccessToken();
            $result = $this->freeeApiService->getPayroll($accessToken, (int)$user->employee_id, $month);
            
            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
