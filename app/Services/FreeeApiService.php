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
     */
    public function getAuthorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16)); // CSRF対策のランダム文字列

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'prompt' => 'select_company'
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
            $response = $this->httpClient->post("{$this->baseUrl}/oauth/token", [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // トークンを更新
            $this->updateToken($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Freee API refresh token error: ' . $e->getMessage());
            throw new \Exception('アクセストークンの更新に失敗しました。');
        }
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
            Log::error('Freee API clock in error: ' . $e->getMessage());
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
     * 勤務記録取得
     */
    public function getWorkRecords(string $accessToken, int $employeeId, string $month): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/work_records", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => [
                    'employee_id' => $employeeId,
                    'year_month' => $month,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API work records error: ' . $e->getMessage());
            throw new \Exception('勤務記録の取得に失敗しました。');
        }
    }

    /**
     * 給与データ取得
     */
    public function getPayroll(string $accessToken, int $employeeId, string $month): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/payrolls", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => [
                    'employee_id' => $employeeId,
                    'year_month' => $month,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API payroll error: ' . $e->getMessage());
            throw new \Exception('給与データの取得に失敗しました。');
        }
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
        $token = FreeeToken::where('company_id', $tokenData['company_id'])->first();
        
        if ($token) {
            $token->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);
        }
    }
}
