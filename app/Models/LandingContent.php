<?php

namespace App\Models;

use App\Services\LandingContent\SiteContentCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Редактируемые тексты лендинга «Өтүкен».
 *
 * Каждая запись — это либо страница (site_pages.home, site_pages.complex, …),
 * либо объект каталога (object.<slug>), либо событие (event.<slug>).
 * Само наполнение хранится как JSON в `content` и translatable через
 * spatie/laravel-translatable: {"ru": {...}, "tuv": {...}, "en": {...}}.
 *
 * Картинки, slug и id фронт держит в bundle, через этот эндпоинт меняются
 * только тексты (см. /api/v1/site-content).
 */
class LandingContent extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = [
        'content',
    ];

    protected $fillable = [
        'section_key',
        'content',
        'images',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            // Если поле images поменялось — удаляем с диска файлы, которые
            // были в старом наборе и пропали в новом. Это закрывает кейс
            // «админ убрал фото из формы → файл на диске тоже исчезает».
            if (! $model->isDirty('images')) {
                return;
            }

            $previous = self::normalizePaths($model->getOriginal('images'));
            $current = self::normalizePaths($model->images);
            $removed = array_diff($previous, $current);

            foreach ($removed as $path) {
                if ($path !== '' && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });

        static::deleting(static function (self $model): void {
            // При удалении самой записи (бывает только из tinker/db:seed)
            // подчищаем все файлы галереи.
            foreach (self::normalizePaths($model->images) as $path) {
                if ($path !== '' && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });

        static::saved(static fn () => SiteContentCache::flush());
        static::deleted(static fn () => SiteContentCache::flush());
    }

    /**
     * Привести содержимое поля images к плоскому массиву строк-путей.
     * Поддерживает форматы: null, массив строк, JSON-строка, коллекция.
     *
     * @return array<int, string>
     */
    public static function normalizePaths(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if ($value === null) {
            return [];
        }

        if (! is_iterable($value)) {
            return [];
        }

        $paths = [];
        foreach ($value as $entry) {
            if (is_string($entry) && $entry !== '') {
                $paths[] = $entry;
            }
        }

        return $paths;
    }

    /**
     * Локали лендинга. ru — мастер-локаль, остальные — переводы.
     *
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return ['ru', 'tuv', 'en'];
    }

    /**
     * Список ключей страниц для сидеров и исторических данных.
     *
     * Публичный API отдаёт только подмножество (см. SiteContentCache).
     *
     * @return array<string, string>
     */
    public static function sitePageKeys(): array
    {
        return [
            'site_pages.home' => 'Главная страница',
            'site_pages.about_us' => 'О нас',
            'site_pages.complex' => 'О комплексе',
            'site_pages.location' => 'Локация',
            'site_pages.contacts' => 'Контакты',
            'site_pages.objects_page' => 'Страница «Объекты»',
            'site_pages.events_page' => 'Страница «События»',
        ];
    }

    /**
     * Slug'и событий.
     *
     * @return array<string, string>
     */
    public static function eventSlugs(): array
    {
        return [
            'moy-rod-moya-gordost' => 'Фестиваль «Мой род – моя гордость»',
        ];
    }

    /**
     * Slug'и объектов комплекса, которые поддерживает фронт.
     *
     * @return array<string, string>
     */
    public static function objectSlugs(): array
    {
        return [
            'alleya-rodovyh-grupp-tuvy' => 'Аллея родовых групп Тувы',
            'yurtochnyj-gorodok' => 'Юрточный городок',
            'ippodrom' => 'Ипподром',
            'restoran' => 'Ресторан',
            'mesto-sily' => 'Место силы',
            'gostinichnyj-kompleks' => 'Гостиничный комплекс',
            'muzej-kultury-kmns' => 'Музей культуры КМНС',
            'akvapark' => 'Аквапарк',
            'stadion-strelby-iz-luka' => 'Стадион стрельбы из лука',
        ];
    }

    /**
     * Slug'и сценариев пространства.
     *
     * @return array<string, string>
     */
    public static function scenarioSlugs(): array
    {
        return [
            'masterplan' => 'Общий образ территории',
            'cultural-axis' => 'Аллея родовых групп Тувы',
            'guest-contour' => 'Гостевой контур',
            'walking-routes' => 'Пешеходная среда',
            'public-leisure' => 'Общественные зоны',
            'first-impression' => 'Первое впечатление',
        ];
    }

    /**
     * Достать слаг из section_key вида object.<slug>, scenario.<slug>
     * или event.<slug>. Для site_pages.* возвращает null.
     */
    public function slug(): ?string
    {
        foreach (['object.', 'scenario.', 'event.'] as $prefix) {
            if (str_starts_with($this->section_key, $prefix)) {
                return substr($this->section_key, strlen($prefix));
            }
        }

        return null;
    }

    /**
     * Удобный аксессор: контент под конкретную локаль (без fallback).
     *
     * @return array<string, mixed>
     */
    public function localized(string $locale): array
    {
        $value = $this->getTranslation('content', $locale, false);

        return is_array($value) ? $value : [];
    }
}
