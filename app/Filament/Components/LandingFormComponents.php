<?php

namespace App\Filament\Components;

use App\Models\LandingContent;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Переиспользуемые блоки форм для разделов «Тексты лендинга».
 *
 * Все методы возвращают компоненты Filament-схемы, привязанные к подпути
 * `<base>.<field>` — где `<base>` обычно вида `content.ru` или `content.tuv`,
 * чтобы сохранить вложение через spatie/laravel-translatable.
 *
 * Внутри каждого репитера используются простые ключи (`title`, `paragraphs`,
 * `list`, `cards`, `question`, `answer` и т. д.) — это естественные поля JSON,
 * с которыми работает фронт.
 */
class LandingFormComponents
{
    /**
     * Repeater строк: одно текстовое поле на строку.
     * Хранится как массив строк — Filament из коробки сериализует репитер в
     * массив объектов `[{value: ...}]`, поэтому используем mutate/dehydrate
     * пары для обмена с массивом строк.
     */
    public static function stringList(string $statePath, string $label, string $itemPlaceholder, string $helperText = ''): Repeater
    {
        $repeater = Repeater::make($statePath)
            ->label($label)
            ->schema([
                Textarea::make('value')
                    ->label('')
                    ->placeholder($itemPlaceholder)
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->addActionLabel('Добавить пункт')
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['value'] ?? '')) !== ''
                ? mb_substr((string) $state['value'], 0, 80)
                : null)
            ->mutateRelationshipDataBeforeFillUsing(fn ($data) => $data)
            ->afterStateHydrated(static function (Repeater $component, $state): void {
                if (! is_array($state)) {
                    $component->state([]);

                    return;
                }
                $normalized = [];
                foreach ($state as $entry) {
                    if (is_array($entry) && array_key_exists('value', $entry)) {
                        $normalized[] = ['value' => (string) $entry['value']];
                    } elseif (is_string($entry)) {
                        $normalized[] = ['value' => $entry];
                    }
                }
                $component->state($normalized);
            })
            ->dehydrateStateUsing(static function ($state): array {
                if (! is_array($state)) {
                    return [];
                }
                $out = [];
                foreach ($state as $row) {
                    if (is_array($row) && isset($row['value']) && trim((string) $row['value']) !== '') {
                        $out[] = (string) $row['value'];
                    } elseif (is_string($row) && trim($row) !== '') {
                        $out[] = $row;
                    }
                }

                return $out;
            });

        if ($helperText !== '') {
            $repeater->helperText($helperText);
        }

        return $repeater;
    }

    /**
     * Repeater для FAQ: вопрос + ответ.
     */
    public static function faq(string $statePath, string $helperText = ''): Repeater
    {
        $repeater = Repeater::make($statePath)
            ->label('Частые вопросы')
            ->schema([
                TextInput::make('question')
                    ->label('Вопрос')
                    ->placeholder('Что такое «Отукен»?'),
                Textarea::make('answer')
                    ->label('Ответ')
                    ->rows(3)
                    ->placeholder('Развёрнутый ответ для посетителя сайта'),
            ])
            ->addActionLabel('Добавить вопрос')
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['question'] ?? '')) !== ''
                ? mb_substr((string) $state['question'], 0, 80)
                : null);

        if ($helperText !== '') {
            $repeater->helperText($helperText);
        }

        return $repeater;
    }

    /**
     * Repeater карточек: title + text. Используется внутри section и в objects_page.
     */
    public static function cards(string $statePath = 'cards'): Repeater
    {
        return Repeater::make($statePath)
            ->label('Карточки')
            ->schema([
                TextInput::make('title')
                    ->label('Заголовок карточки')
                    ->placeholder('Культурный контур'),
                Textarea::make('text')
                    ->label('Текст карточки')
                    ->rows(3)
                    ->placeholder('Описание, 1-2 предложения'),
            ])
            ->addActionLabel('Добавить карточку')
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['title'] ?? '')) !== ''
                ? (string) $state['title']
                : null);
    }

    /**
     * Repeater разделов страницы (sections):
     * каждый — title + paragraphs[] + list[] + cards[].
     */
    public static function sections(string $statePath = 'sections', string $helperText = ''): Repeater
    {
        $repeater = Repeater::make($statePath)
            ->label('Разделы страницы')
            ->schema([
                TextInput::make('title')
                    ->label('Заголовок раздела')
                    ->placeholder('Что такое «Отукен»'),
                self::stringList('paragraphs', 'Абзацы', 'Один абзац — один блок текста.'),
                self::stringList('list', 'Маркированный список', 'Один пункт списка.'),
                self::cards('cards'),
            ])
            ->addActionLabel('Добавить раздел')
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['title'] ?? '')) !== ''
                ? (string) $state['title']
                : null);

        if ($helperText !== '') {
            $repeater->helperText($helperText);
        }

        return $repeater;
    }

    /**
     * Внутренние ссылки relatedLinks — title/description, путь to фронт фиксирует.
     */
    public static function relatedLinks(string $statePath = 'relatedLinks'): Repeater
    {
        return Repeater::make($statePath)
            ->label('Ссылки на другие разделы')
            ->schema([
                TextInput::make('title')
                    ->label('Подпись ссылки')
                    ->placeholder('О комплексе'),
                Textarea::make('description')
                    ->label('Описание под ссылкой')
                    ->rows(2),
                TextInput::make('to')
                    ->label('Адрес страницы')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Куда ведёт ссылка. Обычно не меняют.'),
            ])
            ->addActionLabel('Добавить ссылку')
            ->reorderable(true)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['title'] ?? '')) !== ''
                ? (string) $state['title']
                : null);
    }

    /**
     * Repeater карточек с иконкой: icon + title + text.
     * Используется в home.about.cards и home.about.feature.bullets.
     */
    public static function iconCards(string $statePath, string $label, string $addLabel = 'Добавить карточку', string $helperText = ''): Repeater
    {
        $repeater = Repeater::make($statePath)
            ->label($label)
            ->schema([
                TextInput::make('icon')
                    ->label('Иконка (эмодзи или короткий символ)')
                    ->maxLength(8)
                    ->placeholder('🏛️'),
                TextInput::make('title')
                    ->label('Заголовок')
                    ->placeholder('Культурное ядро'),
                Textarea::make('text')
                    ->label('Текст')
                    ->rows(3)
                    ->placeholder('Краткое описание, 1–2 предложения.'),
            ])
            ->addActionLabel($addLabel)
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['title'] ?? '')) !== ''
                ? (string) $state['title']
                : null);

        if ($helperText !== '') {
            $repeater->helperText($helperText);
        }

        return $repeater;
    }

    /**
     * Repeater пар title + value (статистика, "карточки фактов").
     * Используется в home.about.summary.stats.
     */
    public static function titleValueList(string $statePath, string $label, string $titleLabel = 'Заголовок', string $valueLabel = 'Значение', string $addLabel = 'Добавить пункт'): Repeater
    {
        return Repeater::make($statePath)
            ->label($label)
            ->schema([
                TextInput::make('title')->label($titleLabel),
                TextInput::make('value')->label($valueLabel),
            ])
            ->addActionLabel($addLabel)
            ->reorderable(true)
            ->cloneable()
            ->collapsible()
            ->columns(2)
            ->itemLabel(fn (array $state): ?string => trim((string) ($state['title'] ?? '')) !== ''
                ? (string) $state['title']
                : null);
    }

    /**
     * Поле «Фотографии (галерея)» с drag&drop и хранением на public-диске.
     *
     * @param  string|Closure  $directory  относительный путь (например,
     *                                     'landing-festival' или замыкание `fn (?LandingContent $record) => "landing-objects/{$record?->slug()}"`).
     */
    public static function imagesGallery(string|Closure $directory, string $title, string $description = ''): Section
    {
        $field = FileUpload::make('images')
            ->label('Фотографии')
            ->hiddenLabel()
            ->multiple()
            ->reorderable()
            ->appendFiles()
            ->image()
            ->imageEditor(false)
            ->openable()
            ->downloadable()
            ->disk('public')
            ->visibility('public')
            ->preserveFilenames(false)
            ->getUploadedFileNameForStorageUsing(static function (TemporaryUploadedFile $file): string {
                $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

                return (string) Str::ulid().'.'.$ext;
            })
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(8192)
            ->panelLayout('grid')
            ->columnSpanFull()
            ->helperText('Можно выбрать несколько файлов сразу. Удалённые фото пропадут с сайта.');

        $field->directory($directory);

        $section = Section::make($title)
            ->collapsible()
            ->collapsed(false)
            ->schema([$field]);

        if ($description !== '') {
            $section->description($description);
        }

        return $section;
    }

    /**
     * Галерея с произвольным путём состояния (вложенные формы главной).
     */
    public static function imagesGalleryAtPath(
        string $statePath,
        string|Closure $directory,
        string $title,
        string $description = '',
    ): Section {
        $field = FileUpload::make($statePath)
            ->label('Фотографии')
            ->hiddenLabel()
            ->multiple()
            ->reorderable()
            ->appendFiles()
            ->image()
            ->imageEditor(false)
            ->openable()
            ->downloadable()
            ->disk('public')
            ->visibility('public')
            ->preserveFilenames(false)
            ->getUploadedFileNameForStorageUsing(static function (TemporaryUploadedFile $file): string {
                $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

                return (string) Str::ulid().'.'.$ext;
            })
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(8192)
            ->panelLayout('grid')
            ->columnSpanFull()
            ->helperText('JPG, PNG или WEBP, до 8 МБ. Перетащите фото, чтобы поменять порядок.');

        $field->directory($directory);

        $section = Section::make($title)
            ->collapsible()
            ->collapsed(false)
            ->schema([$field]);

        if ($description !== '') {
            $section->description($description);
        }

        return $section;
    }

    /**
     * CTA-блок — title/text + кнопки primary/secondary (label).
     * Поля `to` отображаются как readonly — фронт всё равно держит маршруты у себя.
     */
    public static function cta(string $statePath = 'cta'): Section
    {
        return Section::make('Блок с кнопками внизу страницы')
            ->description('Заголовок, текст и подписи кнопок. Куда ведут кнопки, обычно не меняют.')
            ->schema([
                TextInput::make("$statePath.title")
                    ->label('Заголовок'),
                Textarea::make("$statePath.text")
                    ->label('Текст')
                    ->rows(2),
                Fieldset::make('Основная кнопка')->schema([
                    TextInput::make("$statePath.primary.label")
                        ->label('Текст на кнопке'),
                    TextInput::make("$statePath.primary.to")
                        ->label('Куда ведёт кнопка')
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(2),
                Fieldset::make('Вторая кнопка')->schema([
                    TextInput::make("$statePath.secondary.label")
                        ->label('Текст на кнопке'),
                    TextInput::make("$statePath.secondary.to")
                        ->label('Куда ведёт кнопка')
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(2),
            ])
            ->collapsible()
            ->collapsed(false);
    }
}
