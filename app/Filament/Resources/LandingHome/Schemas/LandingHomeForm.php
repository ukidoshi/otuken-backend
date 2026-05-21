<?php

namespace App\Filament\Resources\LandingHome\Schemas;

use App\Filament\Components\LandingFormComponents;
use App\Filament\Resources\LandingObject\Schemas\LandingObjectForm;
use App\Filament\Resources\LandingScenario\Schemas\LandingScenarioForm;
use App\Models\LandingContent;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Главная страница — порядок секций как на http://localhost:5173/
 */
class LandingHomeForm
{
    public static function configure(Schema $schema): Schema
    {
        $siteUrl = config('landing.site_url', config('app.url'));

        return $schema
            ->components([
                Section::make('Подсказка')
                    ->description(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400">Блоки ниже идут сверху вниз, как на главной странице сайта. '
                        .'<strong>Новости</strong> — в разделе «Новости». <strong>Карта регионов</strong> — в коде сайта, здесь не редактируется.</p>'
                        .'<p class="mt-2 text-sm"><a href="'.e($siteUrl).'" target="_blank" rel="noopener" class="text-primary-600 underline">Открыть главную на сайте</a></p>'
                    ))
                    ->schema([])
                    ->columnSpanFull(),

                Tabs::make('home_locales')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Русский (RU)')->icon('heroicon-o-language')->schema(self::localeStack('content.ru')),
                        Tab::make('Тувинский (TUV)')->icon('heroicon-o-language')->schema(self::localeStack('content.tuv')),
                        Tab::make('English (EN)')->icon('heroicon-o-language')->schema(self::localeStack('content.en')),
                    ]),

                self::heroEventSection(),

                ...self::objectCatalogSections(),

                Section::make('6. Блок фестиваля — показ на главной')
                    ->description('Галочка скрывает блок после окончания фестиваля. Фото общие для всех языков.')
                    ->schema([
                        Toggle::make('festival_visible')
                            ->label('Показывать блок фестиваля на главной')
                            ->default(true),
                        LandingFormComponents::imagesGallery(
                            directory: 'landing-festival',
                            title: 'Фотографии фестиваля',
                            description: 'Первое — на карточке, остальные — в окне «Подробнее». JPG, PNG или WEBP.',
                        ),
                    ])
                    ->columnSpanFull(),

                ...self::scenarioCatalogSections(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function localeStack(string $prefix): array
    {
        return [
            self::sectionSeo($prefix),
            self::sectionHero($prefix),
            self::sectionAbout($prefix),
            self::sectionNewsNote(),
            self::sectionObjectsHeading($prefix),
            self::sectionMapNote(),
            self::sectionFestivalTexts($prefix),
            self::sectionScenariosHeading($prefix),
            self::sectionCta($prefix),
        ];
    }

    private static function sectionSeo(string $prefix): Section
    {
        return Section::make('Поиск в интернете (вкладка браузера и FAQ)')
            ->description('Заголовок вкладки, описание для Google/Яндекса и вопросы для разметки страницы.')
            ->schema([
                TextInput::make("$prefix.title")
                    ->label('Заголовок во вкладке браузера'),
                Textarea::make("$prefix.description")
                    ->label('Краткое описание для поиска')
                    ->rows(3),
                LandingFormComponents::faq("$prefix.faq"),
            ])
            ->collapsible();
    }

    private static function sectionHero(string $prefix): Section
    {
        $h = "$prefix.hero";

        return Section::make('1. Первый экран (шапка)')
            ->description('Логотип и слайды фона — в коде сайта. Здесь — подпись, заголовок и текст.')
            ->schema([
                TextInput::make("$h.badge")
                    ->label('Строка над заголовком')
                    ->placeholder('Республика Тыва • проект в стадии строительства'),
                TextInput::make("$h.title")
                    ->label('Главный заголовок')
                    ->placeholder('Этнокультурный комплекс «Өтүкен»'),
                Textarea::make("$h.lead")
                    ->label('Текст под заголовком')
                    ->rows(4),
            ])
            ->collapsible();
    }

    private static function sectionAbout(string $prefix): Section
    {
        $about = "$prefix.about";

        return Section::make('2. Блок «Что такое Өтүкен?»')
            ->schema([
                TextInput::make("$about.badge")->label('Подпись над заголовком'),
                TextInput::make("$about.title")->label('Заголовок'),
                Textarea::make("$about.lead")->label('Короткий смысл блока')->rows(3),
                LandingFormComponents::iconCards("$about.cards", 'Три карточки в ряд', 'Добавить карточку', 'Эмодзи в поле «Иконка»: 🏛️ 🎭 📈'),
                Fieldset::make('Развёрнутый блок с этапами')
                    ->schema([
                        TextInput::make("$about.feature.kicker")->label('Надзаголовок'),
                        TextInput::make("$about.feature.title")->label('Заголовок блока'),
                        LandingFormComponents::stringList("$about.feature.paragraphs", 'Абзацы', 'Один абзац — одна строка'),
                        LandingFormComponents::iconCards("$about.feature.bullets", 'Пункты со значками', 'Добавить пункт'),
                        Fieldset::make('Этапы развития')
                            ->schema([
                                TextInput::make("$about.feature.phases.kicker")->label('Надзаголовок над этапами'),
                                LandingFormComponents::cards("$about.feature.phases.items")->label('Этапы'),
                            ]),
                    ]),
                Fieldset::make('Итоговый блок с кнопками')
                    ->schema([
                        TextInput::make("$about.summary.kicker")->label('Надзаголовок'),
                        TextInput::make("$about.summary.title")->label('Заголовок'),
                        Textarea::make("$about.summary.text")->label('Текст')->rows(3),
                        LandingFormComponents::titleValueList("$about.summary.stats", 'Цифры и факты', 'Подпись', 'Значение'),
                        TextInput::make("$about.summary.primaryLabel")->label('Текст основной кнопки'),
                        TextInput::make("$about.summary.primaryTarget")
                            ->label('Куда ведёт основная кнопка')
                            ->helperText('Якорь на странице: objects, map, about…'),
                        TextInput::make("$about.summary.secondaryLabel")->label('Текст второй кнопки'),
                        TextInput::make("$about.summary.secondaryTarget")->label('Якорь второй кнопки'),
                    ])->columns(2),
            ])
            ->collapsible();
    }

    private static function sectionNewsNote(): Section
    {
        return Section::make('3. Новости на главной')
            ->description('Блок «Актуальная новость» и лента — в разделе админки «Новости», не здесь.')
            ->schema([])
            ->collapsed()
            ->collapsible();
    }

    private static function sectionObjectsHeading(string $prefix): Section
    {
        $os = "$prefix.objects_section";

        return Section::make('4. Заголовок «Ключевые объекты»')
            ->description('Карточки объектов — в блоках ниже (с фото).')
            ->schema([
                TextInput::make("$os.badge")->label('Подпись над заголовком'),
                TextInput::make("$os.title")->label('Заголовок'),
                Textarea::make("$os.lead")->label('Текст под заголовком')->rows(3),
            ])
            ->collapsible();
    }

    private static function sectionMapNote(): Section
    {
        return Section::make('5. Карта «Регионы Тувы»')
            ->description('Интерактивная карта на главной — в коде сайта, здесь не редактируется.')
            ->schema([])
            ->collapsed()
            ->collapsible();
    }

    private static function sectionFestivalTexts(string $prefix): Section
    {
        $f = "$prefix.festival";

        return Section::make('6. Блок фестиваля (тексты)')
            ->description('Показ блока — галочкой ниже. Фото — в отдельном разделе после объектов.')
            ->schema([
                TextInput::make("$f.badge")->label('Подпись над заголовком'),
                TextInput::make("$f.title")->label('Заголовок'),
                TextInput::make("$f.dateText")->label('Даты'),
                Textarea::make("$f.lead")->label('Короткий текст')->rows(3),
                TextInput::make("$f.panelTitle")->label('Заголовок списка преимуществ'),
                LandingFormComponents::cards("$f.features")->label('Пункты списка'),
                Textarea::make("$f.summary")->label('Итоговая фраза')->rows(3),
                TextInput::make("$f.detailButtonLabel")->label('Текст кнопки «Подробнее»'),
                Fieldset::make('Окно «Подробнее о фестивале»')
                    ->schema([
                        Textarea::make("$f.detail.intro")->label('Вступление')->rows(3),
                        LandingFormComponents::sections("$f.detail.sections", 'Разделы внутри окна'),
                        LandingFormComponents::stringList("$f.detail.highlights", 'Ключевые моменты', 'Один пункт'),
                        LandingFormComponents::faq("$f.detail.faq"),
                    ]),
            ])
            ->collapsible();
    }

    private static function sectionScenariosHeading(string $prefix): Section
    {
        $ss = "$prefix.scenarios_section";

        return Section::make('7. Заголовок «Сценарии пространства»')
            ->description('Карточки сценариев — в блоках в самом низу формы.')
            ->schema([
                TextInput::make("$ss.badge")->label('Подпись над заголовком'),
                TextInput::make("$ss.title")->label('Заголовок'),
                Textarea::make("$ss.lead")->label('Текст под заголовком')->rows(3),
                Textarea::make("$ss.guideText")->label('Пояснение под сеткой')->rows(3),
                LandingFormComponents::stringList("$ss.guideChips", 'Короткие подсказки', 'Одна фраза'),
            ])
            ->collapsible();
    }

    private static function sectionCta(string $prefix): Section
    {
        return Section::make('8. Блок «Следующий шаг» внизу страницы')
            ->schema([
                TextInput::make("$prefix.cta.title")->label('Заголовок'),
                Textarea::make("$prefix.cta.text")->label('Текст')->rows(3),
                TextInput::make("$prefix.cta.primary.label")->label('Основная кнопка — текст'),
                TextInput::make("$prefix.cta.primary.to")
                    ->label('Основная кнопка — ссылка')
                    ->helperText('Например / или #contact'),
            ])
            ->collapsible();
    }

    private static function heroEventSection(): Section
    {
        $slug = 'moy-rod-moya-gordost';

        return Section::make('Плашка «Предстоящее событие» на первом экране')
            ->description('Текст в жёлтой плашке под описанием на главной. Кнопка ведёт к блоку фестиваля.')
            ->schema([
                Tabs::make('hero_event_tabs')
                    ->tabs([
                        Tab::make('RU')->schema([
                            TextInput::make("catalog_events.{$slug}.content.ru.title")->label('Название события'),
                            TextInput::make("catalog_events.{$slug}.content.ru.dateText")->label('Даты'),
                        ]),
                        Tab::make('TUV')->schema([
                            TextInput::make("catalog_events.{$slug}.content.tuv.title")->label('Название события'),
                            TextInput::make("catalog_events.{$slug}.content.tuv.dateText")->label('Даты'),
                        ]),
                        Tab::make('EN')->schema([
                            TextInput::make("catalog_events.{$slug}.content.en.title")->label('Название события'),
                            TextInput::make("catalog_events.{$slug}.content.en.dateText")->label('Даты'),
                        ]),
                    ]),
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    /**
     * @return array<int, Section>
     */
    private static function objectCatalogSections(): array
    {
        $sections = [];
        $n = 0;

        foreach (LandingContent::objectSlugs() as $slug => $label) {
            $n++;
            $sections[] = Section::make("4.{$n}. Объект: {$label}")
                ->description('Карточка в блоке «Ключевые объекты» и окно «Подробнее».')
                ->schema([
                    LandingFormComponents::imagesGalleryAtPath(
                        "catalog_objects.{$slug}.images",
                        fn (): string => 'landing-objects/'.$slug,
                        'Фотографии',
                        'Первое — на карточке. Порядок — перетаскиванием.',
                    ),
                    Tabs::make('obj_tabs_'.$slug)
                        ->tabs([
                            Tab::make('RU')->schema(LandingObjectForm::homepageLocaleSchema("catalog_objects.{$slug}.content.ru")),
                            Tab::make('TUV')->schema(LandingObjectForm::homepageLocaleSchema("catalog_objects.{$slug}.content.tuv")),
                            Tab::make('EN')->schema(LandingObjectForm::homepageLocaleSchema("catalog_objects.{$slug}.content.en")),
                        ]),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        return $sections;
    }

    /**
     * @return array<int, Section>
     */
    private static function scenarioCatalogSections(): array
    {
        $sections = [];
        $n = 0;

        foreach (LandingContent::scenarioSlugs() as $slug => $label) {
            $n++;
            $sections[] = Section::make("7.{$n}. Сценарий: {$label}")
                ->schema([
                    LandingFormComponents::imagesGalleryAtPath(
                        "catalog_scenarios.{$slug}.images",
                        fn (): string => 'landing-scenarios/'.$slug,
                        'Фотографии',
                        'Первое — на карточке.',
                    ),
                    Tabs::make('sc_tabs_'.$slug)
                        ->tabs([
                            Tab::make('RU')->schema(LandingScenarioForm::localeSchema("catalog_scenarios.{$slug}.content.ru", showInternalCode: false)),
                            Tab::make('TUV')->schema(LandingScenarioForm::localeSchema("catalog_scenarios.{$slug}.content.tuv", showInternalCode: false)),
                            Tab::make('EN')->schema(LandingScenarioForm::localeSchema("catalog_scenarios.{$slug}.content.en", showInternalCode: false)),
                        ]),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        return $sections;
    }
}
