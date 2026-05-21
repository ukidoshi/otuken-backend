<?php

namespace App\Filament\Resources\LandingObject\Schemas;

use App\Filament\Components\LandingFormComponents;
use App\Models\LandingContent;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LandingObjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это на сайте')
                    ->description('Тексты карточки объекта на главной и на странице каталога «Объекты комплекса».')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: static fn (?LandingContent $record): string => 'landing-objects/'.($record?->slug() ?? 'misc'),
                    title: 'Фотографии объекта',
                    description: 'Первое фото — на карточке и в шапке страницы объекта, остальные — в галерее. Порядок меняется перетаскиванием. JPG, PNG или WEBP, до 8 МБ.',
                ),

                Tabs::make('locales')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Русский (RU)')->icon('heroicon-o-language')->schema(self::localeSchema('content.ru')),
                        Tab::make('Тувинский (TUV)')->icon('heroicon-o-language')->schema(self::localeSchema('content.tuv')),
                        Tab::make('English (EN)')->icon('heroicon-o-language')->schema(self::localeSchema('content.en')),
                    ]),
            ]);
    }

    /**
     * Поля объекта на главной (карточка + окно «Подробнее»).
     *
     * @return array<int, mixed>
     */
    public static function homepageLocaleSchema(string $prefix): array
    {
        return [
            TextInput::make("$prefix.title")
                ->label('Название объекта')
                ->placeholder('Юрточный городок'),
            TextInput::make("$prefix.badge")
                ->label('Короткий код на карточке')
                ->helperText('Две–три буквы, если нет фото — показываются вместо изображения.'),
            Textarea::make("$prefix.short")
                ->label('Краткое описание на карточке')
                ->rows(2),
            Textarea::make("$prefix.full")
                ->label('Полное описание в окне')
                ->rows(5),
            LandingFormComponents::stringList("$prefix.tags", 'Теги', 'Например: «Культура»'),
            LandingFormComponents::stringList("$prefix.points", 'Ключевые функции', 'Один пункт в строке'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function localeSchema(string $prefix, bool $showInternalCode = true): array
    {
        $schema = [];

        if ($showInternalCode) {
            $schema[] = TextInput::make("$prefix.slug")
                ->label('Код карточки')
                ->disabled()
                ->dehydrated(true)
                ->helperText('Не меняйте — привязан к этой карточке на сайте.');
        }

        return [
            Section::make('Карточка и заголовки')
                ->description('Название и тексты, которые видит посетитель.')
                ->schema([
                    ...$schema,
                    TextInput::make("$prefix.title")
                        ->label('Название объекта')
                        ->placeholder('Юрточный городок'),
                    TextInput::make("$prefix.eyebrow")
                        ->label('Короткая подпись над названием')
                        ->helperText('Например: «Гостевой формат».'),
                    Textarea::make("$prefix.short")
                        ->label('Краткое описание')
                        ->rows(2)
                        ->helperText('На карточке в списке объектов на главной.'),
                    Textarea::make("$prefix.full")
                        ->label('Полное описание')
                        ->rows(5)
                        ->helperText('В окне с подробностями и в шапке страницы объекта.'),
                    Textarea::make("$prefix.intro")
                        ->label('Вступление перед разделами')
                        ->rows(3)
                        ->helperText('Короткий абзац перед основным текстом.'),
                ])
                ->collapsible(),

            Section::make('Теги и список')
                ->schema([
                    LandingFormComponents::stringList("$prefix.tags", 'Теги', 'Например: «Культура», «Идентичность»', 'Короткие метки на карточке.'),
                    LandingFormComponents::stringList("$prefix.points", 'Пункты со значком «галочка»', 'Один тезис в строке', 'Список над основным текстом.'),
                ])
                ->collapsible(),

            Section::make('Для поиска в интернете')
                ->schema([
                    TextInput::make("$prefix.seoTitle")
                        ->label('Заголовок во вкладке браузера')
                        ->helperText('Для Google, Яндекса и названия вкладки.'),
                    Textarea::make("$prefix.metaDescription")
                        ->label('Краткое описание для поиска')
                        ->rows(3)
                        ->helperText('Текст в результатах поиска и при расшаривании ссылки.'),
                ])
                ->collapsible(),

            Section::make('Разделы страницы')
                ->description('Блоки с заголовком, абзацами, списком или карточками.')
                ->schema([
                    LandingFormComponents::sections("$prefix.sections"),
                ])
                ->collapsible(),

            LandingFormComponents::faq("$prefix.faq"),
        ];
    }
}
