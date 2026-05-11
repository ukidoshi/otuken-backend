<?php

namespace App\Http\Support;

use Illuminate\Http\Request;

final class NewsApiLocale
{
    public static function fromRequest(Request $request): string
    {
        $locale = strtolower((string) $request->query('locale', 'ru'));

        return in_array($locale, ['ru', 'tuv', 'en'], true) ? $locale : 'ru';
    }
}
