<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRecord extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'service_type_id',
        'service_start_time',
        'service_end_time',
        'service_minutes',
        'service_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'service_start_time' => 'datetime',
            'service_end_time' => 'datetime',
            'service_date' => 'date',
            'service_minutes' => 'integer',
        ];
    }

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * クライアントとのリレーション
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * サービス種別とのリレーション
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * サービス時間の計算（分）
     */
    public function getServiceMinutesAttribute(): ?int
    {
        if ($this->service_start_time && $this->service_end_time) {
            return $this->service_start_time->diffInMinutes($this->service_end_time);
        }
        return $this->attributes['service_minutes'];
    }

    /**
     * サービス時間の表示用フォーマット
     */
    public function getFormattedServiceTimeAttribute(): string
    {
        if (!$this->service_start_time || !$this->service_end_time) {
            return '未完了';
        }

        $minutes = $this->service_minutes;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}時間{$remainingMinutes}分";
        }
        return "{$remainingMinutes}分";
    }

    /**
     * サービス料金の計算
     */
    public function getServiceAmountAttribute(): int
    {
        if (!$this->service_minutes || !$this->serviceType) {
            return 0;
        }

        $hourlyRate = $this->serviceType->hourly_rate;
        $hours = $this->service_minutes / 60;
        
        return (int) round($hourlyRate * $hours);
    }

    /**
     * スコープ: 指定期間のサービス記録
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('service_date', [$startDate, $endDate]);
    }

    /**
     * スコープ: 完了済みのサービス記録
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('service_end_time');
    }

    /**
     * スコープ: 進行中のサービス記録
     */
    public function scopeInProgress($query)
    {
        return $query->whereNull('service_end_time')
                    ->where('status', '実施済み');
    }
}
