<?php

namespace App\Filament\Resources\LandingHome\Schemas;

use App\Filament\Components\LandingFormComponents;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Форма главной страницы лендинга «Өтүкен» (запись site_pages.home).
 *
 * content по локалям:
 *   - SEO: title, description, introTitle, introText, detailText, relatedLinks, faq
 *   - about, festival (тексты + модалка; фото фестиваля — в общей галерее)
 *   - objects_section, scenarios_section
 *
 * Фотогалерея фестиваля — общая для всех локалей, колонка `images`, FileUpload
 * вне табов RU/TUV/EN.
 */
class LandingHomeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Где это видно на сайте')
                    ->description('Главная страница «/»: SEO, вступление, FAQ, блоки «Что такое Өтүкен», фестиваль, заголовки секций объектов и сценариев.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([]),

                LandingFormComponents::imagesGallery(
                    directory: 'landing-festival',
                    title: 'Фотографии фестиваля «Мой род – моя гордость»',
                    description: 'Галерея фестиваля — одна на сайт. Первая фотография становится preview-обложкой, все вместе — содержимое модалки «Подробнее о фестивале». Перетаскиванием можно менять порядок.',
                ),

                Tabs::make('locales')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Русский (RU)')->icon('heroicon-o-language')->schema(self::homeSchema('content.ru')),
                        Tab::make('Тувинский (TUV)')->icon('heroicon-o-language')->schema(self::homeSchema('content.tuv')),
                        Tab::make('English (EN)')->icon('heroicon-o-language')->schema(self::homeSchema('content.en')),
                    ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function homeSchema(string $prefix): array
    {
        return [
            self::seoSection($prefix),
            self::aboutSection($prefix),
            self::festivalSection($prefix),
            self::objectsSectionHeading($prefix),
            self::scenariosSectionHeading($prefix),
        ];
    }

    /**
     * SEO главной + вступление + связанные ссылки + FAQ (попадают в data.site_pages.home в API).
     */
    private static function seoSection(string $prefix): Section
    {
        return Section::make('Главная — SEO, вступление и FAQ')
            ->description('Поля для title/description страницы, вступительный блок и FAQ. Пути в relatedLinks зашиты на фронте — поля to только для просмотра.')
            ->collapsible()
            ->collapsed(true)
            ->schema([
                TextInput::make("$prefix.title")
                    ->label('SEO-заголовок (title)')
                    ->helperText('Показывается во вкладке браузера и в выдаче поиска.'),
                Textarea::make("$prefix.description")
                    ->label('SEO-описание (description)')
                    ->rows(3),
                TextInput::make("$prefix.introTitle")->label('Заголовок вступления (introTitle)'),
                Textarea::make("$prefix.introText")->label('Текст вступления (introText)')->rows(4),
                Textarea::make("$prefix.detailText")->label('Дополнительный текст (detailText)')->rows(4),
                LandingFormComponents::relatedLinks("$prefix.relatedLinks"),
                LandingFormComponents::faq("$prefix.faq"),
            ]);
    }

    private static function aboutSection(string $prefix): Section
    {
        $about = "$prefix.about";

        return Section::make('Блок «Что такое Өтүкен» (about)')
            ->description('Лид-блок главной: бейдж, заголовок, краткий смысл, карточки, развёрнутая часть и summary.')
            ->collapsible()
            ->collapsed(true)
            ->schema([
                TextInput::make("$about.badge")
                    ->label('Бейдж')
                    ->placeholder('Этнокультурный комплекс • живая среда традиций'),
                TextInput::make("$about.title")
                    ->label('Заголовок')
                    ->placeholder('Что такое «Өтүкен»?'),
                Textarea::make("$about.lead")
                    ->label('Лид (краткий смысл)')
                    ->rows(3),

                LandingFormComponents::iconCards(
                    "$about.cards",
                    'Карточки (3 шт: культура, события, инвест-логика)',
                    'Добавить карточку',
                    'По исходному дизайну ровно три карточки. Иконка — эмодзи, например 🏛️ / 🎭 / 📈.',
                ),

                Fieldset::make('Featured-блок (about.feature)')
                    ->schema([
                        TextInput::make("$about.feature.kicker")->label('Kicker (надзаголовок)'),
                        TextInput::make("$about.feature.title")->label('Заголовок featured-блока'),
                        LandingFormComponents::stringList("$about.feature.paragraphs", 'Абзацы', 'Один абзац — одна строка.'),
                        LandingFormComponents::iconCards("$about.feature.bullets", 'Буллеты (icon + title + text)', 'Добавить буллет'),
                        Fieldset::make('Этапы развития (about.feature.phases)')
                            ->schema([
                                TextInput::make("$about.feature.phases.kicker")->label('Kicker блока этапов'),
                                LandingFormComponents::cards("$about.feature.phases.items")
                                    ->label('Этапы (title + text)'),
                            ])->columns(1),
                    ])->columns(1),

                Fieldset::make('Summary-блок (about.summary)')
                    ->schema([
                        TextInput::make("$about.summary.kicker")->label('Kicker'),
                        TextInput::make("$about.summary.title")->label('Заголовок summary'),
                        Textarea::make("$about.summary.text")->label('Текст summary')->rows(3),
                        LandingFormComponents::titleValueList("$about.summary.stats", 'Статы (title + value)', 'Подпись', 'Значение'),
                        TextInput::make("$about.summary.primaryLabel")->label('Подпись основной CTA'),
                        TextInput::make("$about.summary.primaryTarget")
                            ->label('Цель основной CTA')
                            ->helperText('Логический id блока на странице: например, "objects" / "map".'),
                        TextInput::make("$about.summary.secondaryLabel")->label('Подпись вторичной CTA'),
                        TextInput::make("$about.summary.secondaryTarget")->label('Цель вторичной CTA'),
                    ])->columns(2),
            ]);
    }

    private static function festivalSection(string $prefix): Section
    {
        $f = "$prefix.festival";

        return Section::make('Блок «Фестиваль» (festival)')
            ->description('Карточка фестиваля на главной и содержимое модалки «Подробнее о фестивале». Фото — выше, в общей галерее (одна для всех локалей).')
            ->collapsible()
            ->collapsed(true)
            ->schema([
                TextInput::make("$f.badge")->label('Бейдж')->placeholder('Первый этап реализации'),
                TextInput::make("$f.title")->label('Заголовок')->placeholder('Фестиваль «Мой род – моя гордость»'),
                TextInput::make("$f.dateText")->label('Подпись с датами')->placeholder('21–28 июня'),
                Textarea::make("$f.lead")->label('Лид')->rows(3),
                TextInput::make("$f.panelTitle")->label('Заголовок блока «Что создаёт фестиваль»'),
                LandingFormComponents::cards("$f.features")
                    ->label('Что создаёт фестиваль (title + text)'),
                Textarea::make("$f.summary")->label('Summary (краткий итог под features)')->rows(3),
                TextInput::make("$f.detailButtonLabel")->label('Подпись кнопки модалки')->placeholder('Подробнее о фестивале'),

                Fieldset::make('Содержимое модалки (festival.detail)')
                    ->schema([
                        Textarea::make("$f.detail.intro")->label('Вводный абзац модалки')->rows(3),
                        LandingFormComponents::sections("$f.detail.sections", 'Разделы модалки: каждый — заголовок + абзацы + (опционально) список.'),
                        LandingFormComponents::stringList("$f.detail.highlights", 'Хайлайты', 'Один пункт-выжимка'),
                        LandingFormComponents::faq("$f.detail.faq"),
                    ])->columns(1),
            ]);
    }

    private static function objectsSectionHeading(string $prefix): Section
    {
        $os = "$prefix.objects_section";

        return Section::make('Заголовок секции «Ключевые объекты» (objects_section)')
            ->description('Шапка над сеткой карточек объектов на главной.')
            ->collapsible()
            ->collapsed(true)
            ->schema([
                TextInput::make("$os.badge")->label('Бейдж')->placeholder('Объекты комплекса'),
                TextInput::make("$os.title")->label('Заголовок')->placeholder('Ключевые объекты «Өтүкен»'),
                Textarea::make("$os.lead")->label('Лид')->rows(3),
            ]);
    }

    private static function scenariosSectionHeading(string $prefix): Section
    {
        $ss = "$prefix.scenarios_section";

        return Section::make('Заголовок секции «Сценарии территории» (scenarios_section)')
            ->description('Шапка над сеткой сценариев на главной. Дополнительные поля guideText / guideChips дают подсказку гостю под сеткой.')
            ->collapsible()
            ->collapsed(true)
            ->schema([
                TextInput::make("$ss.badge")->label('Бейдж')->placeholder('Сценарии пространства'),
                TextInput::make("$ss.title")->label('Заголовок')->placeholder('Как раскрывается территория'),
                Textarea::make("$ss.lead")->label('Лид')->rows(3),
                Textarea::make("$ss.guideText")->label('Поясняющий текст (guideText)')->rows(3),
                LandingFormComponents::stringList(
                    "$ss.guideChips",
                    'Чипсы-подсказки (guideChips)',
                    'Одна короткая фраза в строке',
                    'Например: «Фото и ракурсы», «Краткий смысл», «Понятный контекст».',
                ),
            ]);
    }
}
