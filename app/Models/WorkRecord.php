<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkRecord extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_time',
        'clock_out_time',
        'status',
        'notes'
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime'
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
