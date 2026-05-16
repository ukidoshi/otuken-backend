<?php

namespace App\Services\LandingContent;

use App\Models\LandingContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Кэш для GET /api/v1/site-content?locale=...
 *
 * Каждая локаль кэшируется отдельным ключом `site-content:{locale}`, TTL 10 минут.
 * Сброс — `::flush()`, вызывается observer'ом LandingContent при сохранении,
 * удалении, переупорядочивании галерей и удалении фотографий объектов/
 * сценариев/фестиваля.
 *
 * Контракт ответа:
 *   data.site_pages   — home (SEO и Hero), about_page («О нас»).
 *                       Старые страницы complex/location/… в этот ответ не входят.
 *   data.home         — about, festival (с visible и images), objects_section,
 *                       scenarios_section.
 *   data.objects      — объекты для главной и карточек.
 *   data.scenarios    — сценарии.
 *   data.events       — зарезервировано; не заполняется (пустой массив в ответе).
 *
 * Все строковые поля, пустые после trim(), не возвращаются. Пустые массивы
 * (и сирые объекты) тоже не возвращаются. Это переводит контракт «нет
 * перевода — фронт применяет дефолт» в правило «отсутствие ключа = дефолт».
 */
class SiteContentCache
{
    public const TTL_SECONDS = 600;

    /** Поля верхнего уровня записи site_pages.home: SEO и первый экран (Hero). */
    private const HOME_SEO_KEYS = [
        'title',
        'description',
        'heroBadge',
        'introTitle',
        'introText',
        'detailText',
        'relatedLinks',
        'faq',
    ];

    /** Подразделы home (data.home.*) — белый список. */
    private const HOME_BLOCK_KEYS = [
        'about',
        'festival',
        'objects_section',
        'scenarios_section',
    ];

    /** Разрешённые section_key для data.site_pages.{slug}. */
    private const SITE_PAGE_SECTIONS = [
        'site_pages.home' => 'home',
        'site_pages.about_us' => 'about_page',
    ];

    /**
     * @return array{site_pages: array<string, mixed>, home: array<string, mixed>, objects: array<int, array<string, mixed>>, scenarios: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>}
     */
    public static function get(string $locale): array
    {
        $locale = self::normalizeLocale($locale);

        return Cache::remember(self::key($locale), self::TTL_SECONDS, fn (): array => self::build($locale));
    }

    public static function flush(): void
    {
        foreach (LandingContent::locales() as $locale) {
            Cache::forget(self::key($locale));
        }
    }

    public static function key(string $locale): string
    {
        return 'site-content:'.self::normalizeLocale($locale);
    }

    private static function normalizeLocale(string $locale): string
    {
        $locale = strtolower($locale);

        return in_array($locale, LandingContent::locales(), true) ? $locale : 'ru';
    }

    /**
     * @return array{site_pages: array<string, mixed>, home: array<string, mixed>, objects: array<int, array<string, mixed>>, scenarios: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>}
     */
    private static function build(string $locale): array
    {
        $records = LandingContent::query()
            ->orderBy('section_key')
            ->get();

        $sitePages = [];
        $home = [];
        $objects = [];
        $scenarios = [];

        foreach ($records as $record) {
            $key = $record->section_key;
            $rawLocalized = $record->localized($locale);

            if (str_starts_with($key, 'event.')) {
                continue;
            }

            // === Главная: разнести SEO (site_pages.home) + блоки (data.home) ===
            if ($key === 'site_pages.home') {
                $cleaned = self::pruneEmpty($rawLocalized);
                $cleaned = is_array($cleaned) ? $cleaned : [];

                $seo = array_intersect_key($cleaned, array_flip(self::HOME_SEO_KEYS));
                if ($seo !== []) {
                    $sitePages['home'] = $seo;
                }

                foreach (self::HOME_BLOCK_KEYS as $blockKey) {
                    if (isset($cleaned[$blockKey])) {
                        $home[$blockKey] = $cleaned[$blockKey];
                    }
                }

                $festivalVisible = (bool) ($record->localized('ru')['festival']['visible'] ?? true);

                // Фотографии фестиваля — общие для всех локалей, лежат в
                // колонке `images` записи site_pages.home. Прикрепляются
                // даже если text-локаль не заполнена (фронт всегда сможет
                // показать галерею фестиваля).
                $festivalImages = self::imagesPayload($record);
                if ($festivalImages !== []) {
                    $festival = $home['festival'] ?? [];
                    $festival['images'] = $festivalImages;
                    $home['festival'] = $festival;
                }

                if (isset($home['festival']) && is_array($home['festival'])) {
                    $home['festival']['visible'] = $festivalVisible;
                } elseif (! $festivalVisible) {
                    $home['festival'] = [
                        'visible' => false,
                    ];
                }

                continue;
            }

            // === Остальные страницы: whitelist site_pages.* (сейчас только «О нас») ===
            if (isset(self::SITE_PAGE_SECTIONS[$key])) {
                $cleaned = self::pruneEmpty($rawLocalized);
                if (is_array($cleaned) && $cleaned !== []) {
                    $sitePages[self::SITE_PAGE_SECTIONS[$key]] = $cleaned;
                }

                continue;
            }

            // === Объекты ===
            if (str_starts_with($key, 'object.')) {
                $objects[] = self::buildSluggedEntry($record, $rawLocalized);

                continue;
            }

            // === Сценарии ===
            if (str_starts_with($key, 'scenario.')) {
                $scenarios[] = self::buildSluggedEntry($record, $rawLocalized);

                continue;
            }

            // Любые посторонние section_key игнорируем
        }

        return [
            'site_pages' => $sitePages,
            'home' => $home,
            'objects' => $objects,
            'scenarios' => $scenarios,
            'events' => [],
        ];
    }

    /**
     * Собрать запись объекта/сценария: текстовые поля + slug + images.
     *
     * Текстовые поля проходят pruneEmpty. Поле slug всегда восстанавливается
     * из section_key (в content его не храним, см. EditLandingObject).
     * Поле images — массив (возможно, пустой), фронт всегда ожидает его.
     *
     * @param  array<string, mixed>  $rawLocalized
     * @return array<string, mixed>
     */
    private static function buildSluggedEntry(LandingContent $record, array $rawLocalized): array
    {
        $cleaned = self::pruneEmpty($rawLocalized);
        $entry = is_array($cleaned) ? $cleaned : [];

        // slug — техническое поле, в content его не должно быть, но если
        // от старых данных он там остался — выбросим, чтобы не дублировать.
        unset($entry['slug']);

        $entry = ['slug' => $record->slug()] + $entry;
        $entry['images'] = self::imagesPayload($record);

        return $entry;
    }

    /**
     * Превратить хранящиеся пути в абсолютные URL для фронта.
     * Битые ссылки (файла нет на диске) отбрасываются.
     *
     * @return array<int, array{url: string, alt: string}>
     */
    private static function imagesPayload(LandingContent $record): array
    {
        $disk = Storage::disk('public');
        $out = [];

        foreach (LandingContent::normalizePaths($record->images) as $path) {
            if (! $disk->exists($path)) {
                continue;
            }

            $url = $disk->url($path);
            // На локалке disk->url может вернуть относительный путь /storage/...
            // — приводим к абсолюту через APP_URL (фронт деплоится отдельным
            // доменом и не сможет резолвить относительный путь).
            if (str_starts_with($url, '/')) {
                $url = rtrim((string) config('app.url'), '/').$url;
            }

            $out[] = [
                'url' => $url,
                'alt' => '',
            ];
        }

        return $out;
    }

    /**
     * Рекурсивно убрать «пустые» значения:
     *   - null
     *   - строки, пустые после trim()
     *   - пустые ассоциативные и список-массивы (после рекурсивной чистки)
     *
     * Возвращает null, если после чистки от значения ничего не осталось.
     * Числа, bool и непустые строки сохраняются как есть.
     *
     * Зачем нужно: фронт интерпретирует только отсутствие ключа как сигнал
     * «использовать bundled-дефолт». Пустая строка и пустой массив будут
     * восприняты как явное значение «нет данных» и затрут дефолт.
     */
    private static function pruneEmpty(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value) === '' ? null : $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $out = [];

        foreach ($value as $k => $v) {
            $cleaned = self::pruneEmpty($v);
            if ($cleaned === null) {
                continue;
            }
            if ($isList) {
                $out[] = $cleaned;
            } else {
                $out[$k] = $cleaned;
            }
        }

        return $out === [] ? null : $out;
    }
}
