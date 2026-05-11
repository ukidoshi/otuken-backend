<?php

namespace App\Services;

use App\Models\News;
use App\Models\NewsPreviewToken;
use App\Models\User;
use Illuminate\Support\Str;

class NewsPreviewTokenService
{
    public function generate(News $news, ?User $user = null, int $minutes = 60): string
    {
        $token = Str::random(64);

        NewsPreviewToken::query()->create([
            'news_id' => $news->id,
            'created_by_id' => $user?->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        return $token;
    }

    public function isValid(News $news, ?string $token): bool
    {
        if (! $token) {
            return false;
        }

        return NewsPreviewToken::query()
            ->where('news_id', $news->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }
}
