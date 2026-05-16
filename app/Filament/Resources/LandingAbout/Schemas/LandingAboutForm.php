<?php

namespace App\Filament\Resources\LandingAbout\Schemas;

use App\Filament\Components\LandingFormComponents;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LandingAboutForm
{
    public static function configure(Schema $schema): Schema
    {
        $site = config('landing.site_url', config('app.url'));

        return $schema
            ->components([
                Section::make('Подсказка')
                    ->description(new HtmlString(
                        '<p class="text-sm">Это отдельная страница «О нас», не главная. Тексты — во вкладках по языкам, фотографии — в блоке ниже.</p>'
                        .'<p class="mt-1 text-sm"><a href="'.e(rtrim($site, '/').'/o-nas').'" target="_blank" class="text-primary-600 underline">Посмотреть страницу на сайте</a></p>'
                    ))
                    ->schema([])
                    ->columnSpanFull(),

                Tabs::make('about_locales')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Русский (RU)')->icon('heroicon-o-language')->schema(self::localeSchema('content.ru')),
                        Tab::make('Тувинский (TUV)')->icon('heroicon-o-language')->schema(self::localeSchema('content.tuv')),
                        Tab::make('English (EN)')->icon('heroicon-o-language')->schema(self::localeSchema('content.en')),
                    ]),

                LandingFormComponents::imagesGallery(
                    directory: 'landing-about',
                    title: 'Фотографии страницы «О нас»',
                    description: 'По желанию. JPG, PNG или WEBP, до 8 МБ. Порядок — перетаскиванием.',
                ),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function localeSchema(string $prefix): array
    {
        return [
            Section::make('Для поиска в интернете')
                ->schema([
                    TextInput::make("$prefix.title")
                        ->label('Заголовок во вкладке браузера')
                        ->helperText('Для Google, Яндекса и названия вкладки.'),
                    Textarea::make("$prefix.description")
                        ->label('Краткое описание для поиска')
                        ->rows(3),
                ])
                ->collapsible(),

            Section::make('Шапка страницы')
                ->schema([
                    TextInput::make("$prefix.badge")
                        ->label('Подпись над заголовком')
                        ->helperText('Небольшая подпись над заголовком.'),
                    TextInput::make("$prefix.h1")
                        ->label('Главный заголовок страницы')
                        ->helperText('Самый крупный заголовок вверху.'),
                    Textarea::make("$prefix.intro")
                        ->label('Вступление')
                        ->rows(3)
                        ->helperText('Первый абзац под заголовком.'),
                    Textarea::make("$prefix.lead")
                        ->label('Акцентный абзац')
                        ->rows(3)
                        ->helperText('Выделенный текст под вступлением.'),
                ]),

            Section::make('Содержание')
                ->schema([
                    LandingFormComponents::stringList("$prefix.highlights", 'Ключевые тезисы', 'Короткая строка', 'Список под вступлением.'),
                    LandingFormComponents::sections("$prefix.sections"),
                ])
                ->collapsible(),

            LandingFormComponents::faq("$prefix.faq"),

            LandingFormComponents::cta("$prefix.cta"),
        ];
    }
}
