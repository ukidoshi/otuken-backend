<?php

namespace App\Filament\Resources\LandingScenario\Schemas;

use App\Filament\Components\LandingFormComponents;
use App\Models\LandingContent;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Форма сценария территории (карточка в секции «Как раскрывается территория»).
 *
 * Slug захардкожен на фронте (6 значений), редактировать нельзя.
 * Галерея фото общая для всех локалей.
 */
class LandingScenarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это видно на сайте')
                    ->description('Эти тексты показываются в секции «Как раскрывается территория» на главной — превью-карточкой и в модалке с галереей. Slug зашит в коде фронта.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: static fn (?LandingContent $record): string => 'landing-scenarios/'.($record?->slug() ?? 'misc'),
                    title: 'Фотографии сценария (галерея)',
                    description: 'Первое фото = обложка карточки сценария, все вместе = галерея в модалке. Перетаскиванием можно менять порядок. JPG / PNG / WEBP, до 8 МБ каждая.',
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
            Section::make('Тексты сценария')
                ->schema([
                    TextInput::make("$prefix.slug")
                        ->label('Slug (зашит в коде, поменять нельзя)')
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('Идентификатор сценария на фронте.'),
                    TextInput::make("$prefix.eyebrow")
                        ->label('Надзаголовок (eyebrow)')
                        ->placeholder('Культурная ось'),
                    TextInput::make("$prefix.title")
                        ->label('Заголовок (title)')
                        ->placeholder('Аллея родовых групп Тувы'),
                    Textarea::make("$prefix.description")
                        ->label('Короткое описание (description)')
                        ->rows(2)
                        ->helperText('Это видно на превью-карточке сценария.'),
                    Textarea::make("$prefix.full")
                        ->label('Полное описание (full)')
                        ->rows(5)
                        ->helperText('Большой текст в модалке.'),
                    Textarea::make("$prefix.note")
                        ->label('Примечание (note)')
                        ->rows(2)
                        ->helperText('Короткий комментарий под текстом.'),
                ])
                ->collapsible(),

            Section::make('Хайлайты (highlights)')
                ->description('Маркированный список ключевых тезисов сценария.')
                ->schema([
                    LandingFormComponents::stringList("$prefix.highlights", 'Хайлайты', 'Один тезис в строке'),
                ])
                ->collapsible(),
        ];
    }
}
