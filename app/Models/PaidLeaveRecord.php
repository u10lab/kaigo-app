<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'granted_days',
        'used_days',
        'remaining_days',
        'grant_date',
        'expiry_date',
        'fiscal_year',
        'five_day_obligation_met',
    ];

    protected function casts(): array
    {
        return [
            'grant_date' => 'date',
            'expiry_date' => 'date',
            'five_day_obligation_met' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
