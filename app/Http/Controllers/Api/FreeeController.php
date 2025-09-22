<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreeeToken;
use App\Services\FreeeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * freee OAuth認証コールバック
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        
        if (!$code) {
            return redirect('/admin?error=no_code');
        }

        try {
            $tokenData = $this->freeeApiService->getAccessToken($code);
            
            // 認証成功後、管理者画面にリダイレクト
            return redirect('/admin?freee_auth=success&company_id=' . $tokenData['company_id']);
        } catch (\Exception $e) {
            // エラー時、管理者画面にリダイレクト（エラーメッセージ付き）
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

        try {
            $accessToken = $token->getValidAccessToken();
            $result = $this->freeeApiService->clockIn($accessToken, (int)$user->employee_id);

            return response()->json([
                'message' => '勤務開始打刻が完了しました。',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
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

        try {
            $accessToken = $token->getValidAccessToken();
            $result = $this->freeeApiService->clockOut($accessToken, (int)$user->employee_id);
            
            return response()->json([
                'message' => '勤務終了打刻が完了しました。',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 勤務記録取得
     */
    public function getWorkRecords(Request $request)
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
            $result = $this->freeeApiService->getWorkRecords($accessToken, (int)$user->employee_id, $month);
            
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
            $result = $this->freeeApiService->getEmployees($accessToken);
            
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
