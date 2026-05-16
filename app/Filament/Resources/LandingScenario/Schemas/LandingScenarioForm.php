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

class LandingScenarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это на сайте')
                    ->description('Карточка в блоке «Как раскрывается территория» на главной. По нажатию открывается окно с текстом и фото.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: static fn (?LandingContent $record): string => 'landing-scenarios/'.($record?->slug() ?? 'misc'),
                    title: 'Фотографии сценария',
                    description: 'Первое фото — на карточке, остальные — в окне с подробностями. Порядок — перетаскиванием. JPG, PNG или WEBP, до 8 МБ.',
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
    public static function localeSchema(string $prefix, bool $showInternalCode = true): array
    {
        $leading = [];

        if ($showInternalCode) {
            $leading[] = TextInput::make("$prefix.slug")
                ->label('Код сценария')
                ->disabled()
                ->dehydrated(true)
                ->helperText('Не меняйте — привязан к этой карточке на сайте.');
        }

        return [
            Section::make('Тексты сценария')
                ->schema([
                    ...$leading,
                    TextInput::make("$prefix.eyebrow")
                        ->label('Короткая подпись над заголовком')
                        ->placeholder('Культурная ось'),
                    TextInput::make("$prefix.title")
                        ->label('Заголовок')
                        ->placeholder('Аллея родовых групп Тувы'),
                    Textarea::make("$prefix.description")
                        ->label('Краткое описание')
                        ->rows(2)
                        ->helperText('На карточке в сетке сценариев.'),
                    Textarea::make("$prefix.full")
                        ->label('Полный текст')
                        ->rows(5)
                        ->helperText('В окне, которое открывается по нажатию на карточку.'),
                    Textarea::make("$prefix.note")
                        ->label('Примечание под текстом')
                        ->rows(2),
                ])
                ->collapsible(),

            Section::make('Ключевые моменты')
                ->description('Список коротких тезисов в окне с подробностями.')
                ->schema([
                    LandingFormComponents::stringList("$prefix.highlights", 'Пункты списка', 'Один тезис в строке'),
                ])
                ->collapsible(),
        ];
    }
}
