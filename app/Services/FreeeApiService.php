<?php

namespace App\Services;

use App\Models\FreeeToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class FreeeApiService
{
    private Client $httpClient;
    private string $baseUrl = 'https://api.freee.co.jp';
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->clientId = config('freee.client_id');
        $this->clientSecret = config('freee.client_secret');
        $this->redirectUri = config('freee.redirect_uri');
    }

    /**
     * OAuth認証URLを生成（freee API3仕様準拠）
     * 有給申請API用に必要なスコープを追加
     */
    public function getAuthorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16)); // CSRF対策のランダム文字列

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'prompt' => 'select_company',
            'scope' => 'read write hr' // 有給申請API用スコープ
        ]);

        // freee API3の正しい認証エンドポイント
        return "https://accounts.secure.freee.co.jp/public_api/authorize?{$params}";
    }

    /**
     * 認証コードからアクセストークンを取得
     */
    public function getAccessToken(string $code): array
    {
        try {
            // デバッグ用ログ出力
            Log::info('Freee API token request started', [
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'code_length' => strlen($code)
            ]);

            $response = $this->httpClient->post('https://accounts.secure.freee.co.jp/public_api/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);
            
            // デバッグ用ログ出力 - freee公式レスポンス形式
            Log::info('Freee API token response - Full Details', [
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'access_token' => $data['access_token'] ?? 'NOT_FOUND',
                'token_type' => $data['token_type'] ?? 'NOT_FOUND',
                'expires_in' => $data['expires_in'] ?? 'NOT_FOUND',
                'refresh_token' => $data['refresh_token'] ?? 'NOT_FOUND',
                'scope' => $data['scope'] ?? 'NOT_FOUND',
                'created_at' => $data['created_at'] ?? 'NOT_FOUND',
                'company_id' => $data['company_id'] ?? 'NOT_FOUND',
                'external_cid' => $data['external_cid'] ?? 'NOT_FOUND',
                'parsed_data' => $data
            ]);
            
            // トークンをデータベースに保存
            $this->saveToken($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Freee API token error: ' . $e->getMessage());
            Log::error('Freee API token error details', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            throw new \Exception('freee API認証に失敗しました。');
        }
    }

    /**
     * リフレッシュトークンでアクセストークンを更新
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = $this->httpClient->post('https://accounts.secure.freee.co.jp/public_api/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // レスポンスデータの検証
            if (!$data || !isset($data['access_token'])) {
                Log::error('Invalid token refresh response', ['response' => $data]);
                throw new \Exception('トークン更新のレスポンスが無効です。');
            }

            // トークンを更新
            $this->updateToken($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Freee API refresh token error: ' . $e->getMessage());
            throw new \Exception('アクセストークンの更新に失敗しました。');
        }
    }

    /**
     * 有効なアクセストークンを取得（期限切れの場合は自動更新）
     */
    private function getValidAccessToken(): string
    {
        $token = FreeeToken::first();
        if (!$token) {
            throw new \Exception('freee認証情報が見つかりません。');
        }

        if ($token->isExpired() && $token->refresh_token) {
            Log::info('Access token expired, refreshing...', ['company_id' => $token->company_id]);
            $token->refreshAccessToken();
            $token->refresh(); // Reload from database
        }

        return $token->access_token;
    }

    private function getValidTokenAndCompany(): array
    {
        $token = FreeeToken::first();
        if (!$token) {
            throw new \Exception('freee認証情報が見つかりません。');
        }

        $accessToken = $token->getValidAccessToken();
        if (!$accessToken) {
            throw new \Exception('有効なfreee APIアクセストークンがありません。');
        }

        return [
            'access_token' => $accessToken,
            'company_id' => $token->company_id,
        ];
    }

    /**
     * 勤務開始打刻
     */
    public function clockIn(string $accessToken, int $employeeId): array
    {
        try {
            $token = FreeeToken::first();
            if (!$token) {
                throw new \Exception('freee認証情報が見つかりません。');
            }

            $response = $this->httpClient->post("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/time_clocks", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'company_id' => (int)$token->company_id,
                    'type' => 'clock_in',
                    'base_date' => now()->format('Y-m-d'),
                    'datetime' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $errorResponse = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Freee API clock in error: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'error_response' => $errorResponse,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            throw new \Exception('勤務開始打刻に失敗しました。');
        }
    }

    /**
     * 勤務終了打刻
     */
    public function clockOut(string $accessToken, int $employeeId): array
    {
        try {
            $token = FreeeToken::first();
            if (!$token) {
                throw new \Exception('freee認証情報が見つかりません。');
            }

            $response = $this->httpClient->post("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/time_clocks", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'company_id' => (int)$token->company_id,
                    'type' => 'clock_out',
                    'base_date' => now()->format('Y-m-d'),
                    'datetime' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API clock out error: ' . $e->getMessage());
            throw new \Exception('勤務終了打刻に失敗しました。');
        }
    }



    /**
     * 給与明細取得（時給制対応）
     */
    public function getEmployeePayrollStatement(int $employeeId, int $year, int $month): array
    {
        try {
            $tokenData = $this->getValidTokenAndCompany();

            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/salaries/employee_payroll_statements/{$employeeId}", [
                'headers' => [
                    'Authorization' => "Bearer {$tokenData['access_token']}",
                    'FREEE-VERSION' => '2022-02-01',
                ],
                'query' => [
                    'company_id' => $tokenData['company_id'],
                    'year' => $year,
                    'month' => $month,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $responseBody = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            
            Log::error('Freee API payroll statement error: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'year' => $year,
                'month' => $month,
                'response' => $responseBody,
            ]);
            throw new \Exception('給与明細の取得に失敗しました。');
        }
    }

    /**
     * @deprecated 基本給ルールAPIは時給制には適用できないため、給与明細APIに移行
     */
    public function getEmployeeBasicPayRule(int $employeeId, int $year, int $month): array
    {
        // 給与明細APIにフォールバック
        return $this->getEmployeePayrollStatement($employeeId, $year, $month);
    }

    /**
     * トークンをデータベースに保存
     */
    private function saveToken(array $tokenData): void
    {
        FreeeToken::updateOrCreate(
            ['company_id' => $tokenData['company_id']],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'scope' => $tokenData['scope'] ?? '',
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    }

    /**
     * 従業員一覧を取得（company_id指定）
     */
    public function getEmployees(string $accessToken, string $companyId): array
    {
        try {
            Log::info('Fetching employees from freee API', [
                'company_id' => $companyId,
                'endpoint' => "{$this->baseUrl}/hr/api/v1/companies/{$companyId}/employees"
            ]);

            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/companies/{$companyId}/employees", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => [
                    'limit' => 100, // 最大100件取得
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);
            
            // レスポンスが配列形式の場合は、employeesキーでラップ
            if (is_array($data) && isset($data[0])) {
                $data = ['employees' => $data];
            }
            
            Log::info('Freee employees API response', [
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'employees_count' => count($data['employees'] ?? []),
                'is_array_response' => is_array($data) && isset($data[0])
            ]);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Freee API employees error: ' . $e->getMessage());
            Log::error('Freee API employees error details', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            throw new \Exception('従業員一覧の取得に失敗しました。');
        }
    }

    /**
     * トークンを更新
     */
    private function updateToken(array $tokenData): void
    {
        // 必要なデータの存在確認
        if (!isset($tokenData['company_id']) || !isset($tokenData['access_token'])) {
            Log::error('Missing required token data', ['tokenData' => $tokenData]);
            throw new \Exception('トークンデータが不完全です。');
        }

        $token = FreeeToken::where('company_id', $tokenData['company_id'])->first();
        
        if ($token) {
            $token->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $token->refresh_token,
                'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            ]);
            
            Log::info('Token updated successfully', [
                'company_id' => $tokenData['company_id'],
                'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600)
            ]);
        } else {
            Log::error('Token not found for company', ['company_id' => $tokenData['company_id']]);
            throw new \Exception('該当するトークンが見つかりません。');
        }
    }

    /**
     * 従業員マスタ情報を取得（有給休暇管理用）
     */
    public function getEmployeesForPaidLeave(string $accessToken): array
    {
        $token = FreeeToken::first();
        if (!$token) {
            throw new \Exception('freee認証情報が見つかりません。');
        }
        
        return $this->getEmployees($accessToken, $token->company_id);
    }

    /**
     * 従業員の勤怠記録を取得（有給休暇実績確認用）
     */
    public function getWorkRecordsForPaidLeave(string $accessToken, int $employeeId, string $year): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/work_records", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => [
                    'year' => $year,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API work records for paid leave error: ' . $e->getMessage());
            throw new \Exception('勤怠記録の取得に失敗しました。');
        }
    }

    /**
     * 有給休暇をfreeeに登録
     */
    public function registerPaidLeave(string $accessToken, int $employeeId, string $date, float $days = 1.0): array
    {
        try {
            $token = FreeeToken::first();
            if (!$token) {
                throw new \Exception('freee認証情報が見つかりません。');
            }

            $requestData = [
                'company_id' => (int)$token->company_id,
                'paid_holidays' => $days == 0.5 ? [
                    [
                        'type' => 'half',
                        'mins' => 240  // 半日は分単位で指定
                    ]
                ] : [
                    [
                        'type' => 'full'  // 全日は分単位不要
                    ]
                ]
            ];

            // デバッグ用ログ
            Log::info('Freee API paid leave request', [
                'employee_id' => $employeeId,
                'date' => $date,
                'days' => $days,
                'request_data' => $requestData
            ]);

            $response = $this->httpClient->put("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/work_records/{$date}", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API paid leave registration error: ' . $e->getMessage());
            throw new \Exception('有給休暇の登録に失敗しました。');
        }
    }

    /**
     * 勤怠記録を更新
     * freee API仕様に合わせてcompany_id、work_record_segments、break_recordsを送信
     */
    public function updateWorkRecord(string $accessToken, int $employeeId, string $date, array $workRecordSegments = [], array $breakRecords = [], string $companyId = null): array
    {
        try {
            $token = FreeeToken::first();
            if (!$token && !$companyId) {
                throw new \Exception('freee認証情報が見つかりません。');
            }

            $requestData = [
                'company_id' => (int)($companyId ?? $token->company_id),
            ];

            if (!empty($workRecordSegments)) {
                $requestData['work_record_segments'] = $workRecordSegments;
            }

            if (!empty($breakRecords)) {
                $requestData['break_records'] = $breakRecords;
            }

            Log::info('Freee API work record update request', [
                'employee_id' => $employeeId,
                'date' => $date,
                'request_data' => $requestData
            ]);

            $response = $this->httpClient->put("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/work_records/{$date}", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $errorResponse = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Freee API work record update error: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'date' => $date,
                'error_response' => $errorResponse,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            throw new \Exception('勤怠記録の更新に失敗しました。');
        }
    }









}
