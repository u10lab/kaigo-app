<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'requested_days',
        'reason',
        'leave_type',
        'status',
        'approver_comment',
        'rejection_reason',
        'approver_id',
        'approved_at',
        'rejected_at',
        'freee_registered',
        'freee_absence_id',
        'half_day',
        'half_day_period',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'freee_registered' => 'boolean',
            'half_day' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * 申請が承認済みかどうか
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * 申請が却下済みかどうか
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * 申請が承認待ちかどうか
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * 申請期間の重複チェック
     */
    public static function hasOverlap(int $userId, string $startDate, string $endDate, int $excludeId = null): bool
    {
        $query = self::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * freee APIで登録されているかチェック
     */
    public function isRegisteredInFreee(): bool
    {
        return $this->freee_registered && !empty($this->freee_absence_id);
    }

    /**
     * freee API IDで検索
     */
    public static function findByFreeeId(int $freeeAbsenceId): ?self
    {
        return self::where('freee_absence_id', $freeeAbsenceId)->first();
    }
}
