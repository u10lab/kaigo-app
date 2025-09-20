<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function getValidAccessToken(): string
    {
        if ($this->isExpired()) {
            $this->refreshAccessToken();
        }
        
        return $this->access_token;
    }

    /**
     * アクセストークンを更新
     */
    public function refreshAccessToken(): void
    {
        // リフレッシュトークンを使用して新しいアクセストークンを取得
        // 実装は後でFreeeApiServiceで行う
    }
}
