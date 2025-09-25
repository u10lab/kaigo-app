<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportHistory extends Model
{
    protected $fillable = [
        'export_type',
        'period',
        'record_count',
        'file_name',
        'exported_by',
        'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'exported_at' => 'datetime',
            'record_count' => 'integer',
        ];
    }

    /**
     * エクスポート実行者とのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
