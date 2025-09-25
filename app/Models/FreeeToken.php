<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class FreeeToken extends Model
{
    protected $fillable = [
        'company_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * トークンが有効かチェック
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * アクセストークンを取得（期限切れの場合は自動更新）
     */
    public function getValidAccessToken(): ?string
    {
        if ($this->isExpired() && $this->refresh_token) {
            $this->refreshAccessToken();
        }
        
        return $this->access_token;
    }

    /**
     * アクセストークンを更新
     */
    public function refreshAccessToken(): void
    {
        if (!$this->refresh_token) {
            throw new \Exception('リフレッシュトークンがありません。');
        }

        try {
            $freeeApiService = app(\App\Services\FreeeApiService::class);
            $newTokenData = $freeeApiService->refreshAccessToken($this->refresh_token);
            
            if (!$newTokenData || !isset($newTokenData['access_token'])) {
                throw new \Exception('トークン更新に失敗しました。');
            }
            
            $this->update([
                'access_token' => $newTokenData['access_token'],
                'refresh_token' => $newTokenData['refresh_token'] ?? $this->refresh_token,
                'expires_at' => now()->addSeconds($newTokenData['expires_in'] ?? 3600),
            ]);
            
            Log::info('FreeeToken refreshed successfully', [
                'company_id' => $this->company_id,
                'expires_at' => now()->addSeconds($newTokenData['expires_in'] ?? 3600)
            ]);
        } catch (\Exception $e) {
            Log::error('FreeeToken refresh failed', [
                'company_id' => $this->company_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
