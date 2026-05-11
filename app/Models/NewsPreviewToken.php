<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPreviewToken extends Model
{
    protected $fillable = [
        'news_id',
        'created_by_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
