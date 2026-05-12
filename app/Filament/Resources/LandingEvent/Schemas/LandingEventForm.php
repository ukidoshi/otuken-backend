<?php

namespace App\Filament\Resources\LandingEvent\Schemas;

use App\Filament\Components\LandingFormComponents;
use App\Models\LandingContent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Форма редактирования события комплекса (страница /sobytiya/<slug>).
 *
 * Slug захардкожен на фронте, поэтому в форме readonly. Галерея фото общая
 * для всех локалей (общая колонка images записи `event.<slug>`).
 */
class LandingEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это видно на сайте')
                    ->description('Эти тексты показываются на странице события /sobytiya/<slug> и в карточке события в каталоге. Slug зашит в коде фронта.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: static fn (?LandingContent $record): string => 'landing-events/'.($record?->slug() ?? 'misc'),
                    title: 'Фотографии события (галерея)',
                    description: 'Первое фото = обложка карточки события, все вместе = галерея в модалке. Перетаскиванием можно менять порядок. JPG / PNG / WEBP, до 8 МБ каждая.',
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
            Section::make('Карточка и SEO')
                ->schema([
                    TextInput::make("$prefix.slug")
                        ->label('Slug (зашит в коде, поменять нельзя)')
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('URL-идентификатор. Меняется только разработчиком вместе с фронтом.'),
                    TextInput::make("$prefix.title")
                        ->label('Название события')
                        ->placeholder('Фестиваль «Мой род – моя гордость»'),
                    Textarea::make("$prefix.short")
                        ->label('Краткое описание (short)')
                        ->rows(2)
                        ->helperText('Это видно в карточке события в каталоге.'),
                    Textarea::make("$prefix.intro")
                        ->label('Вступление (intro)')
                        ->rows(3),
                    TextInput::make("$prefix.seoTitle")
                        ->label('SEO title'),
                    Textarea::make("$prefix.metaDescription")
                        ->label('SEO description (metaDescription)')
                        ->rows(3),
                ])
                ->collapsible(),

            Section::make('Когда и где')
                ->schema([
                    TextInput::make("$prefix.location")
                        ->label('Место (location)')
                        ->placeholder('Республика Тыва, рядом с Кызылом'),
                    TextInput::make("$prefix.dateText")
                        ->label('Подпись с датами (dateText)')
                        ->placeholder('21–28 июня'),
                    DatePicker::make("$prefix.startDate")
                        ->label('Начало')
                        ->displayFormat('d.m.Y')
                        ->native(false),
                    DatePicker::make("$prefix.endDate")
                        ->label('Окончание')
                        ->displayFormat('d.m.Y')
                        ->native(false),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Содержательные разделы (sections)')
                ->description('Большие блоки страницы события: каждый = заголовок + абзацы + (опционально) список или карточки.')
                ->schema([
                    LandingFormComponents::sections("$prefix.sections"),
                ])
                ->collapsible(),

            LandingFormComponents::faq("$prefix.faq"),
        ];
    }
}
