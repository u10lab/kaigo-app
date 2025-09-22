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

            $data = json_decode($response->getBody()->getContents(), true);
            
            // トークンをデータベースに保存
            $this->saveToken($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Freee API token error: ' . $e->getMessage());
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
            $response = $this->httpClient->post("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/work_records", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'clock_in_at' => now()->toISOString(),
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
            $response = $this->httpClient->post("{$this->baseUrl}/hr/api/v1/employees/{$employeeId}/work_records", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'clock_out_at' => now()->toISOString(),
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
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    }

    /**
     * 従業員一覧を取得
     */
    public function getEmployees(string $accessToken): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/hr/api/v1/employees", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'query' => [
                    'limit' => 100, // 最大100件取得
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Freee API employees error: ' . $e->getMessage());
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
