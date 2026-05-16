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
 * Главная страница: секции сверху вниз, как на публичном сайте (локали в табах).
 *
 * Карточки объектов и сценариев редактируются здесь же (отдельные записи БД).
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
                        '<p class="text-sm text-gray-600 dark:text-gray-400">Ниже блоки главной страницы — сверху вниз, как на сайте. '
                        .'Новости на главной правятся в разделе «Новости». Карту «Регионы» здесь не трогаем.</p>'
                        .'<p class="mt-2 text-sm"><a href="'.e($siteUrl).'" target="_blank" rel="noopener" class="text-primary-600 underline">Посмотреть главную на сайте</a></p>'
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

                ...self::objectCatalogSections(),

                Section::make('4. Блок фестиваля — показ на главной')
                    ->description('Когда фестиваль закончился, снимите галочку — блок исчезнет с сайта, тексты и фото останутся на следующий раз.')
                    ->schema([
                        Toggle::make('festival_visible')
                            ->label('Показывать блок фестиваля на главной')
                            ->helperText('Снятая галочка скрывает блок на сайте, ничего не удаляется.')
                            ->default(true),
                        LandingFormComponents::imagesGallery(
                            directory: 'landing-festival',
                            title: 'Фотографии фестиваля',
                            description: 'Одни и те же фото для всех языков. Первое — на карточке, остальные — в окне «Подробнее». Форматы: JPG, PNG или WEBP.',
                        ),
                    ])
                    ->columnSpanFull(),

                ...self::scenarioCatalogSections(),
            ]);
    }

    /**
     * Секции 1–2, заголовки 3 и 5, тексты фестиваля, SEO и FAQ внутри одной локали.
     *
     * @return array<int, mixed>
     */
    private static function localeStack(string $prefix): array
    {
        return [
            self::sectionHero($prefix),
            self::sectionAbout($prefix),
            self::sectionObjectsHeading($prefix),
            self::sectionFestivalTexts($prefix),
            self::sectionScenariosHeading($prefix),
            self::sectionSeoMisc($prefix),
        ];
    }

    private static function sectionHero(string $prefix): Section
    {
        return Section::make('1. Первый экран')
            ->description('Самый верх главной: крупный заголовок и текст под ним.')
            ->schema([
                TextInput::make("$prefix.heroBadge")
                    ->label('Строка над заголовком')
                    ->helperText('Небольшая подпись над главным заголовком, например регион или даты.')
                    ->placeholder('Республика Тыва • …'),
                TextInput::make("$prefix.introTitle")
                    ->label('Главный заголовок')
                    ->helperText('Крупный заголовок в самом верху страницы.'),
                Textarea::make("$prefix.introText")
                    ->label('Текст под заголовком')
                    ->rows(4)
                    ->helperText('Короткое описание сразу под главным заголовком.'),
                Fieldset::make('Для поиска в интернете')
                    ->schema([
                        TextInput::make("$prefix.title")
                            ->label('Заголовок во вкладке браузера')
                            ->helperText('То, что видно в Google, Яндексе и в названии вкладки.'),
                        Textarea::make("$prefix.description")
                            ->label('Краткое описание для поиска')
                            ->rows(3)
                            ->helperText('Короткий текст для результатов поиска.'),
                    ]),
            ])
            ->collapsible();
    }

    private static function sectionAbout(string $prefix): Section
    {
        $about = "$prefix.about";

        return Section::make('2. Блок «Что такое Өтүкен?»')
            ->description('Текст и карточки в блоке про суть комплекса.')
            ->schema([
                TextInput::make("$about.badge")
                    ->label('Подпись над заголовком')
                    ->placeholder('Этнокультурный комплекс • живая среда традиций'),
                TextInput::make("$about.title")
                    ->label('Заголовок')
                    ->placeholder('Что такое «Өтүкен»?'),
                Textarea::make("$about.lead")
                    ->label('Короткий смысл блока')
                    ->rows(3),

                LandingFormComponents::iconCards(
                    "$about.cards",
                    'Три карточки в ряд',
                    'Добавить карточку',
                    'Обычно три карточки. В поле «Иконка» можно вставить эмодзи: 🏛️ 🎭 📈',
                ),

                Fieldset::make('Развёрнутый блок с этапами')
                    ->schema([
                        TextInput::make("$about.feature.kicker")->label('Надзаголовок'),
                        TextInput::make("$about.feature.title")->label('Заголовок блока'),
                        LandingFormComponents::stringList("$about.feature.paragraphs", 'Абзацы', 'Один абзац — одна строка.'),
                        LandingFormComponents::iconCards("$about.feature.bullets", 'Пункты со значками', 'Добавить пункт'),
                        Fieldset::make('Этапы развития')
                            ->schema([
                                TextInput::make("$about.feature.phases.kicker")->label('Надзаголовок над этапами'),
                                LandingFormComponents::cards("$about.feature.phases.items")
                                    ->label('Этапы'),
                            ])->columns(1),
                    ])->columns(1),

                Fieldset::make('Итоговый блок с кнопками')
                    ->schema([
                        TextInput::make("$about.summary.kicker")->label('Надзаголовок'),
                        TextInput::make("$about.summary.title")->label('Заголовок'),
                        Textarea::make("$about.summary.text")->label('Текст')->rows(3),
                        LandingFormComponents::titleValueList("$about.summary.stats", 'Цифры и факты', 'Подпись', 'Значение'),
                        TextInput::make("$about.summary.primaryLabel")->label('Текст основной кнопки'),
                        TextInput::make("$about.summary.primaryTarget")
                            ->label('Куда ведёт основная кнопка')
                            ->helperText('Служебное поле: к какому блоку на странице прокрутить. Обычно не меняют.'),
                        TextInput::make("$about.summary.secondaryLabel")->label('Текст второй кнопки'),
                        TextInput::make("$about.summary.secondaryTarget")->label('Куда ведёт вторая кнопка'),
                    ])->columns(2),
            ])
            ->collapsible();
    }

    private static function sectionObjectsHeading(string $prefix): Section
    {
        $os = "$prefix.objects_section";

        return Section::make('3. Заголовок секции «Ключевые объекты»')
            ->description('Шапка над сеткой карточек. Сами карточки — в блоках ниже (каждая с галереей).')
            ->schema([
                TextInput::make("$os.badge")->label('Подпись над заголовком')->placeholder('Объекты комплекса'),
                TextInput::make("$os.title")->label('Заголовок')->placeholder('Ключевые объекты «Өтүкен»'),
                Textarea::make("$os.lead")->label('Короткий текст под заголовком')->rows(3),
            ])
            ->collapsible();
    }

    private static function sectionFestivalTexts(string $prefix): Section
    {
        $f = "$prefix.festival";

        return Section::make('4. Блок фестиваля (тексты)')
            ->description('Тексты блока на главной. Фотографии — в отдельном разделе ниже, они общие для всех языков.')
            ->schema([
                TextInput::make("$f.badge")->label('Подпись над заголовком')->placeholder('Первый этап реализации'),
                TextInput::make("$f.title")->label('Заголовок')->placeholder('Фестиваль «Мой род – моя гордость»'),
                TextInput::make("$f.dateText")->label('Даты')->placeholder('21–28 июня'),
                Textarea::make("$f.lead")->label('Короткий текст')->rows(3),
                TextInput::make("$f.panelTitle")->label('Заголовок списка «Что создаёт фестиваль»'),
                LandingFormComponents::cards("$f.features")
                    ->label('Пункты «Что создаёт фестиваль»'),
                Textarea::make("$f.summary")->label('Итоговая фраза под списком')->rows(3),
                TextInput::make("$f.detailButtonLabel")->label('Текст кнопки «Подробнее»')->placeholder('Подробнее о фестивале'),

                Fieldset::make('Окно «Подробнее о фестивале»')
                    ->schema([
                        Textarea::make("$f.detail.intro")->label('Вступительный абзац')->rows(3),
                        LandingFormComponents::sections("$f.detail.sections", 'Разделы внутри окна: заголовок, текст, при необходимости список.'),
                        LandingFormComponents::stringList("$f.detail.highlights", 'Ключевые моменты', 'Один пункт в строке'),
                        LandingFormComponents::faq("$f.detail.faq"),
                    ])->columns(1),
            ])
            ->collapsible();
    }

    private static function sectionScenariosHeading(string $prefix): Section
    {
        $ss = "$prefix.scenarios_section";

        return Section::make('5. Заголовок секции «Сценарии пространства»')
            ->description('Шапка над сеткой сценариев. Карточки сценариев — в блоках ниже.')
            ->schema([
                TextInput::make("$ss.badge")->label('Подпись над заголовком')->placeholder('Сценарии пространства'),
                TextInput::make("$ss.title")->label('Заголовок')->placeholder('Как раскрывается территория'),
                Textarea::make("$ss.lead")->label('Короткий текст под заголовком')->rows(3),
                Textarea::make("$ss.guideText")->label('Поясняющий текст под сеткой')->rows(3),
                LandingFormComponents::stringList(
                    "$ss.guideChips",
                    'Короткие подсказки под сеткой',
                    'Одна короткая фраза в строке',
                    'Например: «Фото и ракурсы», «Краткий смысл».',
                ),
            ])
            ->collapsible();
    }

    private static function sectionSeoMisc(string $prefix): Section
    {
        return Section::make('Дополнительно: текст, ссылки, FAQ')
            ->description('Ниже первого экрана: детальный текст и блок вопросов.')
            ->schema([
                Textarea::make("$prefix.detailText")
                    ->label('Дополнительный текст на главной')
                    ->rows(4)
                    ->helperText('Длинный поясняющий текст, если он есть на макете главной.'),
                LandingFormComponents::relatedLinks("$prefix.relatedLinks"),
                LandingFormComponents::faq("$prefix.faq"),
            ])
            ->collapsible()
            ->collapsed(true);
    }

    /**
     * @return array<int, Section>
     */
    private static function objectCatalogSections(): array
    {
        $sections = [];

        foreach (LandingContent::objectSlugs() as $slug => $label) {
            $sections[] = Section::make('3. Объект на главной: '.$label)
                ->description('Тексты и фото карточки в блоке «Ключевые объекты». По нажатию на сайте открывается окно с подробностями.')
                ->schema([
                    LandingFormComponents::imagesGalleryAtPath(
                        "catalog_objects.{$slug}.images",
                        fn (): string => 'landing-objects/'.$slug,
                        'Галерея объекта',
                        'Перетащите фото, чтобы поменять порядок. Первое — на карточке. JPG, PNG или WEBP, до 8 МБ.',
                    ),
                    Tabs::make('obj_tabs_'.$slug)
                        ->columnSpanFull()
                        ->tabs([
                            Tab::make('RU')->schema(LandingObjectForm::localeSchema("catalog_objects.{$slug}.content.ru", showInternalCode: false)),
                            Tab::make('TUV')->schema(LandingObjectForm::localeSchema("catalog_objects.{$slug}.content.tuv", showInternalCode: false)),
                            Tab::make('EN')->schema(LandingObjectForm::localeSchema("catalog_objects.{$slug}.content.en", showInternalCode: false)),
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

        foreach (LandingContent::scenarioSlugs() as $slug => $label) {
            $sections[] = Section::make('5. Сценарий: '.$label)
                ->description('Карточка в блоке «Как раскрывается территория». По нажатию — окно с текстом и фото.')
                ->schema([
                    LandingFormComponents::imagesGalleryAtPath(
                        "catalog_scenarios.{$slug}.images",
                        fn (): string => 'landing-scenarios/'.$slug,
                        'Фотографии сценария',
                        'Первое фото — на карточке, остальные — в окне с подробностями. JPG, PNG или WEBP, до 8 МБ.',
                    ),
                    Tabs::make('sc_tabs_'.$slug)
                        ->columnSpanFull()
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
