<?php

namespace App\Filament\Resources\LandingAbout\Schemas;

use App\Filament\Components\LandingFormComponents;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
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
                        '<p class="text-sm">Страница «О нас» (/o-nas). Порядок блоков — как на сайте.</p>'
                        .'<p class="mt-1 text-sm"><a href="'.e(rtrim($site, '/').'/o-nas').'" target="_blank" class="text-primary-600 underline">Открыть на сайте</a></p>'
                    ))
                    ->schema([])
                    ->columnSpanFull(),

                Tabs::make('about_locales')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Русский (RU)')->icon('heroicon-o-language')->schema(self::localeStack('content.ru')),
                        Tab::make('Тувинский (TUV)')->icon('heroicon-o-language')->schema(self::localeStack('content.tuv')),
                        Tab::make('English (EN)')->icon('heroicon-o-language')->schema(self::localeStack('content.en')),
                    ]),

                LandingFormComponents::imagesGallery(
                    directory: 'landing-about',
                    title: 'Фото в шапке страницы',
                    description: 'По желанию. Если пусто — стандартное фото из сайта.',
                ),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function localeStack(string $prefix): array
    {
        return [
            Section::make('Поиск в интернете')
                ->schema([
                    TextInput::make("$prefix.title")->label('Заголовок во вкладке браузера'),
                    Textarea::make("$prefix.description")->label('Описание для поиска')->rows(3),
                ])
                ->collapsible(),

            Section::make('1. Шапка страницы')
                ->schema([
                    TextInput::make("$prefix.badge")->label('Подпись над заголовком'),
                    TextInput::make("$prefix.h1")->label('Главный заголовок'),
                    Textarea::make("$prefix.intro")->label('Первый абзац')->rows(3),
                    Textarea::make("$prefix.lead")->label('Второй абзац')->rows(3),
                    LandingFormComponents::stringList("$prefix.highlights", 'Короткие тезисы-чипы', 'Одна строка'),
                ])
                ->collapsible(),

            Section::make('2. Блок «Наш подход»')
                ->schema([
                    TextInput::make("$prefix.approach.kicker")->label('Подпись'),
                    TextInput::make("$prefix.approach.title")->label('Заголовок'),
                    LandingFormComponents::stringList("$prefix.approach.paragraphs", 'Абзацы', 'Один абзац'),
                ])
                ->collapsible(),

            Section::make('3. Блок «Что для нас принципиально»')
                ->schema([
                    TextInput::make("$prefix.principles.kicker")->label('Подпись'),
                    LandingFormComponents::cards("$prefix.principles.cards")->label('Четыре карточки'),
                ])
                ->collapsible(),

            Section::make('4. Текстовые разделы')
                ->description('Три блока под шапкой — как на странице.')
                ->schema([
                    LandingFormComponents::sections("$prefix.sections"),
                ])
                ->collapsible(),

            Section::make('5. Команда проекта')
                ->schema([
                    TextInput::make("$prefix.team.badge")->label('Подпись над заголовком'),
                    TextInput::make("$prefix.team.title")->label('Заголовок'),
                    Textarea::make("$prefix.team.lead")->label('Текст под заголовком')->rows(3),
                    Repeater::make("$prefix.people")
                        ->label('Участники команды')
                        ->schema([
                            TextInput::make('initials')->label('Инициалы')->maxLength(4),
                            TextInput::make('name')->label('ФИО'),
                            TextInput::make('role')->label('Роль'),
                            Textarea::make('text')->label('Описание')->rows(3),
                        ])
                        ->addActionLabel('Добавить человека')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->collapsible(),

            Section::make('6. Вопросы и ответы')
                ->schema([
                    Textarea::make("$prefix.faqLead")->label('Текст над блоком FAQ')->rows(2),
                    LandingFormComponents::faq("$prefix.faq"),
                ])
                ->collapsible(),

            LandingFormComponents::cta("$prefix.cta"),
        ];
    }
}
