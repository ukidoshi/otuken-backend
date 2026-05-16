<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LandingEvent\LandingEventResource;
use App\Filament\Resources\LandingHome\LandingHomeResource;
use App\Filament\Resources\LandingObject\LandingObjectResource;
use App\Filament\Resources\LandingScenario\LandingScenarioResource;
use App\Models\LandingContent;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Точка входа для редакции лендинга: карточки в порядке, близком к структуре сайта.
 */
class LandingContentHub extends Page
{
    protected string $view = 'filament.pages.landing-content-hub';

    protected static ?string $title = 'Контент лендинга';

    protected static ?string $navigationLabel = 'Обзор';

    protected static string|UnitEnum|null $navigationGroup = 'Лендинг (как на сайте)';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'landing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('landing.read') ?? false;
    }

    /**
     * Карточки разделов: подписи совпадают с пунктами меню в админке; «на сайте» — публичный URL из вёрстки.
     *
     * @return list<array{title: string, admin_hint: string, site_path: string, href: string, action: string}>
     */
    public function getSectionsProperty(): array
    {
        $homeId = LandingContent::query()
            ->where('section_key', 'site_pages.home')
            ->value('id');

        $homeHref = $homeId
            ? LandingHomeResource::getUrl('edit', ['record' => $homeId])
            : LandingHomeResource::getUrl('index');

        return [
            [
                'title' => 'Главная страница',
                'admin_hint' => 'SEO, первый экран, FAQ и связанные ссылки, блоки «О проекте», фестиваль, секции объектов и сценариев на главной.',
                'site_path' => '/',
                'href' => $homeHref,
                'action' => 'Редактировать',
            ],
            [
                'title' => 'Объекты комплекса',
                'admin_hint' => 'Тексты и медиа по каждому объекту; то, что показывается в каталоге и карточках.',
                'site_path' => '/obekty',
                'href' => LandingObjectResource::getUrl('index'),
                'action' => 'Список объектов',
            ],
            [
                'title' => 'Сценарии территории',
                'admin_hint' => 'Шесть фиксированных сценариев — slug совпадают с маршрутами на фронте.',
                'site_path' => 'раздел «Сценарии» (навигация сайта)',
                'href' => LandingScenarioResource::getUrl('index'),
                'action' => 'Список сценариев',
            ],
            [
                'title' => 'События',
                'admin_hint' => 'Фестиваль и другие события: описания и галереи.',
                'site_path' => '/sobytiya',
                'href' => LandingEventResource::getUrl('index'),
                'action' => 'Список событий',
            ],
        ];
    }
}
