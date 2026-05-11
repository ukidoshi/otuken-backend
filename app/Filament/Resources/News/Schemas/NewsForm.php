<?php

namespace App\Filament\Resources\News\Schemas;

use App\Enums\NewsStatus;
use App\Filament\Resources\News\Pages\EditNews;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Обложка и изображения')
                    ->description('Обложка для карточки и страницы; отдельное изображение для превью в соцсетях (Open Graph).')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cover_image')
                            ->label('Обложка новости')
                            ->collection('cover_image')
                            ->disk('public')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable()
                            ->saveRelationshipsWhenHidden(true)
                            ->helperText('Показывается в карточке и на странице новости. JPG/PNG/WEBP, до 5 МБ.'),
                        Actions::make([
                            Action::make('detach_cover_image')
                                ->label('Отвязать обложку')
                                ->color('danger')
                                ->link()
                                ->visible(function ($livewire): bool {
                                    if (! $livewire instanceof EditNews || ! $livewire->record->exists) {
                                        return false;
                                    }

                                    $record = $livewire->record;
                                    $state = $livewire->data['cover_image'] ?? null;

                                    return $record->getMedia('cover_image')->isNotEmpty()
                                        || (is_array($state) ? $state !== [] : filled($state));
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Отвязать обложку?')
                                ->modalDescription('Запись о файле будет удалена из базы. Если файл отсутствует на диске, так можно убрать «зависшую» привязку.')
                                ->modalSubmitActionLabel('Отвязать')
                                ->action(function ($livewire): void {
                                    if ($livewire instanceof EditNews) {
                                        $livewire->detachCoverImage();
                                    }
                                }),
                        ]),
                        SpatieMediaLibraryFileUpload::make('seo_image')
                            ->label('SEO изображение')
                            ->collection('seo_image')
                            ->disk('public')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable()
                            ->saveRelationshipsWhenHidden(true)
                            ->helperText('OpenGraph. JPG/PNG/WEBP, до 5 МБ.'),
                        Actions::make([
                            Action::make('detach_seo_image')
                                ->label('Отвязать SEO-изображение')
                                ->color('danger')
                                ->link()
                                ->visible(function ($livewire): bool {
                                    if (! $livewire instanceof EditNews || ! $livewire->record->exists) {
                                        return false;
                                    }

                                    $record = $livewire->record;
                                    $state = $livewire->data['seo_image'] ?? null;

                                    return $record->getMedia('seo_image')->isNotEmpty()
                                        || (is_array($state) ? $state !== [] : filled($state));
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Отвязать SEO-изображение?')
                                ->modalDescription('Запись о файле будет удалена из базы. Если файл отсутствует на диске, так можно убрать «зависшую» привязку.')
                                ->modalSubmitActionLabel('Отвязать')
                                ->action(function ($livewire): void {
                                    if ($livewire instanceof EditNews) {
                                        $livewire->detachSeoImage();
                                    }
                                }),
                        ]),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(false),
                Tabs::make('news_tabs')->tabs([
                    Tabs\Tab::make('Основное')->schema([
                        Section::make('Базовая информация')
                            ->description('Заполните минимум русский заголовок, slug и язык публикации.')
                            ->schema([
                                TextInput::make('title.ru')
                                    ->label('Заголовок (RU)')
                                    ->placeholder('Например: В Кызыле открыли новый культурный центр')
                                    ->helperText('Обязательное поле для публикации.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('slug', Str::slug((string) $state));
                                    })
                                    ->required(),
                                Toggle::make('auto_translate_en_with_ai')
                                    ->label('Авто-перевод на английский (AI)')
                                    ->default(true)
                                    ->helperText('Если включено, пустые EN-поля будут заполнены автоматически при сохранении. Не распространяется на тувинский (TUV).')
                                    ->dehydrated(),
                                TextInput::make('title.tuv')
                                    ->label('Заголовок (TUV)')
                                    ->placeholder('Перевод заголовка на тувинский'),
                                TextInput::make('title.en')
                                    ->label('Заголовок (EN)')
                                    ->placeholder('English translation of the title'),
                                TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->placeholder('otkrytie-kulturnogo-centra')
                                    ->helperText('Генерируется автоматически из RU-заголовка, но можно отредактировать вручную.')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Textarea::make('excerpt.ru')
                                    ->label('Краткое описание (RU)')
                                    ->placeholder('2-3 предложения для карточки новости в списке.')
                                    ->rows(3),
                                Select::make('locale')
                                    ->label('Основной язык записи')
                                    ->options(['ru' => 'Русский', 'tuv' => 'Тувинский', 'en' => 'English'])
                                    ->default('ru')
                                    ->helperText('Фильтрация публичного API идет по этому полю.')
                                    ->required(),
                            ]),
                    ]),
                    Tabs\Tab::make('Контент')->schema([
                        Section::make('Контентные блоки')
                            ->description('Редактирование в контент-студии: текст, списки, изображения и видео (YouTube / Vimeo через блок Embed).')
                            ->schema([
                                Placeholder::make('content_studio_link')
                                    ->label('')
                                    ->content(fn ($record): HtmlString => new HtmlString(
                                        $record
                                            ? '<a href="'.route('admin.news.studio.edit', ['news' => $record, 'locale' => 'ru']).'" style="display:inline-flex;align-items:center;gap:.5rem;border-radius:.75rem;padding:.65rem 1rem;background:#f59e0b;color:#111827;font-weight:700;text-decoration:none;">Открыть контент-студию</a>'
                                            : '<span style="color:#9ca3af;">Сначала сохраните новость, затем откройте контент-студию.</span>'
                                    )),
                            ]),
                    ]),
                    Tabs\Tab::make('SEO')->schema([
                        Section::make('SEO метаданные')
                            ->description('Если не заполнено, фронт может использовать обычный заголовок и описание.')
                            ->schema([
                                Toggle::make('generate_seo_with_ai')
                                    ->label('Сгенерировать SEO автоматически (AI)')
                                    ->default(true)
                                    ->helperText('Если включено, пустые SEO поля заполнятся автоматически при сохранении.')
                                    ->dehydrated(),
                                TextInput::make('seo_title.ru')->label('SEO title (RU)'),
                                TextInput::make('seo_title.tuv')->label('SEO title (TUV)'),
                                TextInput::make('seo_title.en')->label('SEO title (EN)'),
                                Textarea::make('seo_description.ru')->label('SEO description (RU)')->rows(3),
                                Textarea::make('seo_description.tuv')->label('SEO description (TUV)')->rows(3),
                                Textarea::make('seo_description.en')->label('SEO description (EN)')->rows(3),
                                TextInput::make('seo_image_alt')
                                    ->label('Alt текст SEO-изображения')
                                    ->placeholder('Короткое описание изображения для accessibility'),
                                TextInput::make('canonical')
                                    ->label('Canonical URL')
                                    ->placeholder('https://example.com/news/slug')
                                    ->url(),
                            ]),
                    ]),
                    Tabs\Tab::make('Публикация')->schema([
                        Section::make('Статус и даты')
                            ->description('Для отображения в публичном API статус должен быть "Опубликовано".')
                            ->schema([
                                ToggleButtons::make('status')
                                    ->label('Статус')
                                    ->options(self::statusOptions())
                                    ->default(NewsStatus::Draft->value)
                                    ->inline()
                                    ->required(),
                                DateTimePicker::make('publish_at')
                                    ->label('Дата публикации')
                                    ->helperText('Если оставить пустым и статус "Опубликовано", новость видна сразу.'),
                                DateTimePicker::make('unpublish_at')
                                    ->label('Снять с публикации')
                                    ->helperText('После этой даты новость исчезнет из публичного API.'),
                                DateTimePicker::make('approved_at')
                                    ->label('Дата утверждения')
                                    ->helperText('Обычно заполняется редактором/руководителем.'),
                            ]),
                    ]),
                ]),
            ]);
    }

    private static function statusOptions(): array
    {
        return [
            NewsStatus::Draft->value => 'Черновик',
            NewsStatus::Scheduled->value => 'Запланировано',
            NewsStatus::Published->value => 'Опубликовано',
            NewsStatus::Hidden->value => 'Скрыто',
            NewsStatus::Archived->value => 'Архив',
        ];
    }
}
