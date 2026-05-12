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

/**
 * Форма редактирования одного объекта комплекса.
 *
 * Поле slug захардкожено на фронте — поэтому в форме оно readonly.
 * Картинки/badge/galleries фронт держит у себя, в админке их нет.
 */
class LandingObjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это видно на сайте')
                    ->description('Эти тексты показываются на странице конкретного объекта (URL: /obekty/<slug>) и в карточке объекта на странице каталога. Slug зашит в коде фронта — его не получится изменить.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: static fn (?LandingContent $record): string => 'landing-objects/'.($record?->slug() ?? 'misc'),
                    title: 'Фотографии объекта (галерея)',
                    description: 'Первое фото становится главным (hero) на странице объекта, все вместе — галерея. Порядок можно менять перетаскиванием. Форматы: JPG / PNG / WEBP, до 8 МБ каждая.',
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
     * @return array<int, mixed>
     */
    private static function localeSchema(string $prefix): array
    {
        return [
            Section::make('Карточка и заголовки')
                ->description('Основные тексты: заголовок, надзаголовок (eyebrow), краткое и развернутое описание.')
                ->schema([
                    TextInput::make("$prefix.slug")
                        ->label('Slug (зашит в коде, поменять нельзя)')
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('URL-идентификатор. Меняется только разработчиком вместе с фронтом.'),
                    TextInput::make("$prefix.title")
                        ->label('Название объекта')
                        ->placeholder('Юрточный городок'),
                    TextInput::make("$prefix.eyebrow")
                        ->label('Надзаголовок (eyebrow)')
                        ->helperText('Короткая «шапка» над заголовком, например: «Гостевой формат».'),
                    Textarea::make("$prefix.short")
                        ->label('Короткое описание (short)')
                        ->rows(2)
                        ->helperText('Это видно в карточке объекта в каталоге.'),
                    Textarea::make("$prefix.full")
                        ->label('Полное описание (full)')
                        ->rows(5)
                        ->helperText('Большой текст в шапке страницы объекта.'),
                    Textarea::make("$prefix.intro")
                        ->label('Вступление к разделам (intro)')
                        ->rows(3)
                        ->helperText('Короткий вводный абзац перед содержательными разделами.'),
                ])
                ->collapsible(),

            Section::make('Теги и буллеты')
                ->schema([
                    LandingFormComponents::stringList("$prefix.tags", 'Теги (tags)', 'Например: «Культура», «Идентичность»', 'Короткие метки, выводятся как чипсы в карточке.'),
                    LandingFormComponents::stringList("$prefix.points", 'Ключевые буллеты (points)', 'Один тезис в строке', 'Список пунктов с галочками над описанием.'),
                ])
                ->collapsible(),

            Section::make('SEO')
                ->schema([
                    TextInput::make("$prefix.seoTitle")
                        ->label('SEO title')
                        ->helperText('Заголовок страницы во вкладке браузера и в выдаче поиска.'),
                    Textarea::make("$prefix.metaDescription")
                        ->label('SEO description (metaDescription)')
                        ->rows(3)
                        ->helperText('Описание страницы в превью соцсетей и поисковой выдаче.'),
                ])
                ->collapsible(),

            Section::make('Содержательные разделы')
                ->description('Большие блоки страницы: каждый = заголовок + абзацы + (опционально) список или карточки.')
                ->schema([
                    LandingFormComponents::sections("$prefix.sections"),
                ])
                ->collapsible(),

            LandingFormComponents::faq("$prefix.faq"),
        ];
    }
}
