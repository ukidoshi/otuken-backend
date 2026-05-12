<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use App\Services\LandingContent\SiteContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Публичный эндпоинт текстов лендинга «Өтүкен».
 *
 * GET /api/v1/site-content?locale=ru|tuv|en
 *
 * Возвращает «слой» правок для одной локали. Фронт сначала загружает
 * bundled-дефолты, затем накладывает поверх ответ этого эндпоинта
 * (см. applySitePagesOverrides / applyObjectCatalogOverrides /
 * applyEventCatalogOverrides на стороне Vue).
 */
class SiteContentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request->query('locale'));

        $payload = SiteContentCache::get($locale);

        // По контракту site_pages и home всегда объекты (минимум {}).
        // PHP сериализует пустой ассоциативный массив в [], поэтому форсируем тип.
        $sitePages = $payload['site_pages'] ?? [];
        $payload['site_pages'] = $sitePages === [] ? (object) [] : $sitePages;

        $home = $payload['home'] ?? [];
        $payload['home'] = $home === [] ? (object) [] : $home;

        // objects/scenarios/events — всегда массивы (можно пустые).
        $payload['objects'] = $payload['objects'] ?? [];
        $payload['scenarios'] = $payload['scenarios'] ?? [];
        $payload['events'] = $payload['events'] ?? [];

        return response()->json(
            data: ['data' => $payload],
            options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    private function resolveLocale(mixed $raw): string
    {
        $value = is_string($raw) ? strtolower(trim($raw)) : '';

        return in_array($value, LandingContent::locales(), true) ? $value : 'ru';
    }
}
