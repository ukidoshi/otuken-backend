<?php

namespace Database\Seeders;

use App\Enums\NewsStatus;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', (string) env('ADMIN_EMAIL', 'admin@example.com'))->first()
            ?? User::query()->first();

        if (! $author) {
            return;
        }

        News::query()->updateOrCreate(
            ['title->ru' => 'Демо-новость: запуск редакции'],
            [
                'title' => [
                    'ru' => 'Демо-новость: запуск редакции',
                    'tuv' => 'Демо-медээ: редакцияның эгелээши',
                    'en' => 'Demo news: editorial launch',
                ],
                'excerpt' => [
                    'ru' => 'Это автоматически созданная новость для проверки админки, API и preview.',
                    'tuv' => 'Бо админка, API болгаш preview шынчыылаар дээш автомат-тургузулган медээ.',
                    'en' => 'This auto-generated article is for checking admin UI, API, and preview.',
                ],
                'content_blocks' => [
                    'ru' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'О проекте', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Эта запись создана сидером и показывает базовый формат контента.']],
                            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['Проверка публикации', 'Проверка preview', 'Проверка локалей']]],
                        ],
                    ],
                    'tuv' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'Проект дугайында', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Бо бичээни сидер тургузупкан болгаш контент форматын көргүзер.']],
                        ],
                    ],
                    'en' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'About this project', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'This seeded article demonstrates the baseline EditorJS content structure.']],
                        ],
                    ],
                ],
                'status' => NewsStatus::Published->value,
                'publish_at' => now()->subMinutes(5),
                'unpublish_at' => null,
                'locale' => 'ru',
                'seo_title' => [
                    'ru' => 'Демо-новость для проверки CMS',
                    'tuv' => 'CMS шынчыылаар демо-медээ',
                    'en' => 'Demo article for CMS verification',
                ],
                'seo_description' => [
                    'ru' => 'Системная демо-новость для тестирования административного интерфейса и API.',
                    'tuv' => 'Админ интерфейс болгаш API шынчыылаар дээш системалыг демо-медээ.',
                    'en' => 'System demo article for testing the admin interface and API.',
                ],
                'seo_image_alt' => 'Демо обложка новости',
                'canonical' => null,
                'author_id' => $author->id,
                'published_by_id' => $author->id,
                'approved_at' => now()->subMinutes(10),
            ]
        );

        News::query()->updateOrCreate(
            ['title->ru' => 'Демо: интеграция с внешним фронтендом'],
            [
                'title' => [
                    'ru' => 'Демо: интеграция с внешним фронтендом',
                    'tuv' => 'Демо: дышкан фронтенд-биле интеграция',
                    'en' => 'Demo: integration with external frontend',
                ],
                'excerpt' => [
                    'ru' => 'Кратко о REST API, локалях и preview-токенах для Vue-приложения.',
                    'tuv' => 'REST API, локальлар болгаш Vue-дээш preview-токеннар дугайында кыска.',
                    'en' => 'REST API, locales, and preview tokens for a Vue client.',
                ],
                'content_blocks' => [
                    'ru' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'Что доступно из API', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Публичные эндпоинты отдают список и деталь новости с учётом <code>locale</code>. Для черновиков можно использовать preview по токену.']],
                            ['type' => 'quote', 'data' => ['text' => 'Контент хранится в формате Editor.js — тот же JSON удобно рендерить на фронте.', 'caption' => 'Заметка для разработчиков']],
                            ['type' => 'list', 'data' => ['style' => 'ordered', 'items' => ['GET /api/v1/news', 'GET /api/v1/news/{slug}', 'Preview: токен в query или Sanctum']]],
                        ],
                    ],
                    'tuv' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'API дугайында', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Публичный API медээлери көргүзер. Preview-токеннар черновиктерге.']],
                        ],
                    ],
                    'en' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'API surface', 'level' => 2]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Public routes return list and detail; drafts use preview tokens or Sanctum.']],
                        ],
                    ],
                ],
                'status' => NewsStatus::Published->value,
                'publish_at' => now()->subHours(2),
                'unpublish_at' => null,
                'locale' => 'ru',
                'seo_title' => [
                    'ru' => 'API новостей и фронтенд',
                    'tuv' => 'Медээ API болгаш фронтенд',
                    'en' => 'News API and frontend',
                ],
                'seo_description' => [
                    'ru' => 'Обзор эндпоинтов и формата контента для подключения Vue.',
                    'tuv' => 'Vue-биле холбаар болгаш контент форматын көргүзүү.',
                    'en' => 'Endpoints and Editor.js payload notes for Vue integration.',
                ],
                'seo_image_alt' => 'Схема взаимодействия API и клиента',
                'canonical' => null,
                'author_id' => $author->id,
                'published_by_id' => $author->id,
                'approved_at' => now()->subHours(3),
            ]
        );

        News::query()->updateOrCreate(
            ['title->ru' => 'Черновик: открытая лекция о региональной культуре'],
            [
                'title' => [
                    'ru' => 'Черновик: открытая лекция о региональной культуре',
                    'tuv' => 'Черновик: регион культуразы дугайында ажыг лекция',
                    'en' => 'Draft: open lecture on regional culture',
                ],
                'excerpt' => [
                    'ru' => 'Материал в подготовке: дата и площадка будут объявлены позже.',
                    'tuv' => 'Бичээни бээр эш-түрүүнү болдуруп турар: оглуг болгаш чери кийин чарлыыр.',
                    'en' => 'Work in progress: date and venue to be announced.',
                ],
                'content_blocks' => [
                    'ru' => [
                        'blocks' => [
                            ['type' => 'header', 'data' => ['text' => 'Анонс', 'level' => 3]],
                            ['type' => 'paragraph', 'data' => ['text' => 'Планируется встреча с <strong>историками</strong> и <em>краеведами</em>. Текст будет дополнен после согласования программы.']],
                            ['type' => 'checklist', 'data' => ['items' => [
                                ['text' => 'Согласовать спикеров', 'checked' => false],
                                ['text' => 'Подготовить обложку', 'checked' => true],
                                ['text' => 'Настроить публикацию', 'checked' => false],
                            ]]],
                            ['type' => 'delimiter', 'data' => new \stdClass],
                        ],
                    ],
                    'tuv' => [
                        'blocks' => [
                            ['type' => 'paragraph', 'data' => ['text' => 'Лекция дугайында медээ кийин толтурулур.']],
                        ],
                    ],
                    'en' => [
                        'blocks' => [
                            ['type' => 'paragraph', 'data' => ['text' => 'Placeholder for the English version after translation from RU.']],
                        ],
                    ],
                ],
                'status' => NewsStatus::Draft->value,
                'publish_at' => null,
                'unpublish_at' => null,
                'locale' => 'ru',
                'seo_title' => [
                    'ru' => 'Лекция о региональной культуре (черновик)',
                    'tuv' => 'Регион культуразы дугайында лекция (черновик)',
                    'en' => 'Lecture on regional culture (draft)',
                ],
                'seo_description' => [
                    'ru' => 'Черновик анонса; не отображается в публичной ленте до публикации.',
                    'tuv' => 'Анонс черновигы; медээ чарылганча публикага көрүнмес.',
                    'en' => 'Draft announcement; hidden from public listing until published.',
                ],
                'seo_image_alt' => null,
                'canonical' => null,
                'author_id' => $author->id,
                'published_by_id' => null,
                'approved_at' => null,
            ]
        );
    }
}

