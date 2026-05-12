<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\AiTranslationService;
use App\Services\Media\EditorJsMediaMetadata;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsStudioController extends Controller
{
    public function edit(Request $request, News $news)
    {
        $this->authorize('update', $news);

        $locale = in_array($request->query('locale', 'ru'), ['ru', 'tuv', 'en'], true)
            ? (string) $request->query('locale', 'ru')
            : 'ru';

        $content = $news->getTranslation('content_blocks', $locale, true);
        $content = [
            'blocks' => $this->normalizeBlocksForEditorJs($this->normalizeBlocks($content)),
        ];

        return view('admin.news.studio', [
            'news' => $news,
            'locale' => $locale,
            'initialContent' => $content,
            'enableTuvStudioTranslation' => (bool) config('services.openrouter.enable_tuv_studio_translation'),
        ]);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $this->authorize('update', $news);

        $validated = $request->validate([
            'locale' => ['required', 'in:ru,tuv,en'],
            'content' => ['required', 'array'],
        ]);

        $content = $validated['content'];
        if (is_array($content)) {
            $content = app(EditorJsMediaMetadata::class)->enrichContent($content);
        }

        $news->setTranslation('content_blocks', $validated['locale'], $content);
        $news->save();

        return response()->json([
            'message' => 'Контент сохранён.',
        ]);
    }

    public function uploadImage(Request $request, News $news): JsonResponse
    {
        $this->authorize('update', $news);

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        $path = $validated['image']->store('news-studio', 'public');
        $url = '/storage/'.$path;

        $file = ['url' => $url];
        $abs = Storage::disk('public')->path($path);
        $info = @getimagesize($abs);
        if (is_array($info)) {
            $file['width'] = (int) $info[0];
            $file['height'] = (int) $info[1];
            if (isset($info['mime'])) {
                $file['mime'] = (string) $info['mime'];
            }
        }
        $file['mime'] ??= $validated['image']->getMimeType() ?: 'application/octet-stream';
        $file['size'] = (int) $validated['image']->getSize();

        return response()->json([
            'success' => 1,
            'file' => $file,
        ]);
    }

    public function uploadVideo(Request $request, News $news, EditorJsMediaMetadata $metadata): JsonResponse
    {
        $this->authorize('update', $news);

        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime',
                'max:102400',
            ],
        ]);

        $path = $validated['video']->store('news-studio', 'public');
        $url = '/storage/'.$path;

        $block = $metadata->enrichBlocks([
            ['type' => 'video', 'data' => ['file' => ['url' => $url]]],
        ])[0] ?? null;

        $file = is_array($block['data']['file'] ?? null) ? $block['data']['file'] : ['url' => $url];
        $file['mime'] ??= $validated['video']->getMimeType() ?: 'application/octet-stream';
        $file['size'] = (int) $validated['video']->getSize();

        return response()->json([
            'success' => 1,
            'file' => $file,
        ]);
    }

    public function translateToEn(News $news, AiTranslationService $translationService): JsonResponse
    {
        $this->authorize('update', $news);

        @set_time_limit(300);

        $ru = $news->getTranslation('content_blocks', 'ru', true);
        $ruBlocks = $this->normalizeBlocks($ru);

        $translatedBlocks = [];

        foreach ($ruBlocks as $block) {
            $translatedBlocks[] = $this->translateBlock($block, fn (array $payload): array => $translationService->translateRuToEn($payload));
        }

        $news->setTranslation('content_blocks', 'en', [
            'blocks' => $translatedBlocks,
        ]);
        $news->save();
        $news->refresh();

        $enContent = $news->getTranslation('content_blocks', 'en', true);

        return response()->json([
            'message' => 'EN версия обновлена из RU. Можно продолжить редактирование.',
            'content' => [
                'blocks' => $this->normalizeBlocksForEditorJs($this->normalizeBlocks($enContent)),
            ],
        ]);
    }

    public function translateToTuv(News $news, AiTranslationService $translationService): JsonResponse
    {
        $this->authorize('update', $news);

        @set_time_limit(300);

        $ru = $news->getTranslation('content_blocks', 'ru', true);
        $ruBlocks = $this->normalizeBlocks($ru);

        $translatedBlocks = [];

        foreach ($ruBlocks as $block) {
            $translatedBlocks[] = $this->translateBlock($block, fn (array $payload): array => $translationService->translateRuToTuv($payload));
        }

        $news->setTranslation('content_blocks', 'tuv', [
            'blocks' => $translatedBlocks,
        ]);
        $news->save();
        $news->refresh();

        $tuvContent = $news->getTranslation('content_blocks', 'tuv', true);

        return response()->json([
            'message' => 'TUV версия обновлена из RU. Можно продолжить редактирование.',
            'content' => [
                'blocks' => $this->normalizeBlocksForEditorJs($this->normalizeBlocks($tuvContent)),
            ],
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeBlocks(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : ['blocks' => []];
        }

        if (! is_array($value)) {
            return [];
        }

        if (array_key_exists('blocks', $value) && is_array($value['blocks'])) {
            return $value['blocks'];
        }

        return array_values($value);
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  Closure(array<string, string|null>): array<string, string>  $translate
     * @return array<string, mixed>
     */
    private function translateBlock(array $block, Closure $translate): array
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $type = (string) ($block['type'] ?? '');

        if (in_array($type, ['paragraph', 'header', 'heading', 'quote'], true)) {
            $text = (string) ($data['text'] ?? '');
            if ($text !== '') {
                $translated = $translate(['text' => $text]);
                $data['text'] = $translated['text'] ?? $text;
            }

            if (isset($data['caption']) && is_string($data['caption']) && $data['caption'] !== '') {
                $translated = $translate(['caption' => $data['caption']]);
                $data['caption'] = $translated['caption'] ?? $data['caption'];
            }
        }

        if ($type === 'list' && is_array($data['items'] ?? null)) {
            $translatedItems = [];

            foreach ($data['items'] as $item) {
                if (is_array($item)) {
                    $textKey = array_key_exists('content', $item) ? 'content' : 'text';
                    $itemText = (string) ($item[$textKey] ?? '');
                } else {
                    $textKey = null;
                    $itemText = (string) $item;
                }
                $translated = $translate(['item' => $itemText]);
                $itemValue = $translated['item'] ?? $itemText;

                if (is_array($item)) {
                    $item[$textKey] = $itemValue;
                    $translatedItems[] = $item;
                } else {
                    $translatedItems[] = $itemValue;
                }
            }

            $data['items'] = $translatedItems;
        }

        if ($type === 'embed' && isset($data['caption']) && is_string($data['caption']) && $data['caption'] !== '') {
            $translated = $translate(['caption' => $data['caption']]);
            $data['caption'] = $translated['caption'] ?? $data['caption'];
        }

        if ($type === 'video' && isset($data['caption']) && is_string($data['caption']) && $data['caption'] !== '') {
            $translated = $translate(['caption' => $data['caption']]);
            $data['caption'] = $translated['caption'] ?? $data['caption'];
        }

        $block['data'] = $data;

        return $block;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBlocksForEditorJs(array $blocks): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            // Legacy mapping from old builder/preview format
            if ($type === 'heading') {
                $type = 'header';
            }

            if ($type === '') {
                continue;
            }

            switch ($type) {
                case 'paragraph':
                    $text = trim((string) ($data['text'] ?? ''));
                    if ($text === '') {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'paragraph',
                        'data' => ['text' => $text],
                    ];
                    break;

                case 'header':
                    $text = trim((string) ($data['text'] ?? ''));
                    if ($text === '') {
                        continue 2;
                    }
                    $level = (int) ($data['level'] ?? 2);
                    if (! in_array($level, [2, 3, 4], true)) {
                        $level = 2;
                    }
                    $normalized[] = [
                        'type' => 'header',
                        'data' => [
                            'text' => $text,
                            'level' => $level,
                        ],
                    ];
                    break;

                case 'quote':
                    $text = trim((string) ($data['text'] ?? ''));
                    if ($text === '') {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'quote',
                        'data' => [
                            'text' => $text,
                            'caption' => trim((string) ($data['caption'] ?? $data['author'] ?? '')),
                        ],
                    ];
                    break;

                case 'list':
                    $style = (string) ($data['style'] ?? 'unordered');
                    if (! in_array($style, ['ordered', 'unordered', 'checklist'], true)) {
                        $style = 'unordered';
                    }

                    $items = $data['items'] ?? [];
                    if (is_string($items)) {
                        $items = array_values(array_filter(array_map('trim', preg_split('/\R/u', $items) ?: [])));
                    }
                    if (! is_array($items)) {
                        $items = [];
                    }

                    if ($items !== [] && is_array($items[0] ?? null) && array_key_exists('content', $items[0])) {
                        $normalized[] = [
                            'type' => 'list',
                            'data' => array_merge($data, [
                                'style' => $style,
                                'items' => $items,
                            ]),
                        ];
                        break;
                    }

                    $items = array_values(array_filter(array_map(static function ($item): string {
                        if (is_array($item)) {
                            return trim((string) ($item['text'] ?? $item['content'] ?? ''));
                        }

                        return trim((string) $item);
                    }, $items), static fn (string $text): bool => $text !== ''));

                    if ($items === []) {
                        continue 2;
                    }

                    $normalized[] = [
                        'type' => 'list',
                        'data' => [
                            'style' => $style,
                            'items' => $items,
                        ],
                    ];
                    break;

                case 'checklist':
                    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
                    $clean = [];
                    foreach ($items as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $text = trim((string) ($item['text'] ?? ''));
                        if ($text === '') {
                            continue;
                        }
                        $clean[] = [
                            'text' => $text,
                            'checked' => (bool) ($item['checked'] ?? false),
                        ];
                    }
                    if ($clean === []) {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'checklist',
                        'data' => ['items' => $clean],
                    ];
                    break;

                case 'image':
                    $url = trim((string) ($data['file']['url'] ?? $data['url'] ?? ''));
                    if ($url === '') {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'image',
                        'data' => [
                            'file' => $this->keepFileMeta(
                                is_array($data['file'] ?? null) ? $data['file'] : [],
                                $url,
                                ['width', 'height', 'mime', 'size'],
                            ),
                            'caption' => trim((string) ($data['caption'] ?? '')),
                        ],
                    ];
                    break;

                case 'video':
                    $videoUrl = trim((string) ($data['file']['url'] ?? $data['url'] ?? ''));
                    if ($videoUrl === '') {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'video',
                        'data' => [
                            'file' => $this->keepFileMeta(
                                is_array($data['file'] ?? null) ? $data['file'] : [],
                                $videoUrl,
                                ['width', 'height', 'mime', 'size', 'duration', 'poster_url'],
                            ),
                            'caption' => trim((string) ($data['caption'] ?? '')),
                            'withBorder' => (bool) ($data['withBorder'] ?? false),
                            'withBackground' => (bool) ($data['withBackground'] ?? false),
                            'stretched' => (bool) ($data['stretched'] ?? false),
                        ],
                    ];
                    break;

                case 'delimiter':
                    $normalized[] = ['type' => 'delimiter', 'data' => new \stdClass];
                    break;

                case 'embed':
                    $embed = trim((string) ($data['embed'] ?? ''));
                    if ($embed === '') {
                        continue 2;
                    }
                    $normalized[] = [
                        'type' => 'embed',
                        'data' => [
                            'embed' => $embed,
                            'service' => (string) ($data['service'] ?? ''),
                            'source' => (string) ($data['source'] ?? $embed),
                            'caption' => (string) ($data['caption'] ?? ''),
                        ],
                    ];
                    break;
            }
        }

        return $normalized;
    }

    /**
     * Сохраняет известные метаполя файла (width/height/mime/size/duration/poster_url),
     * приводя структуру file к виду {url, ...meta}.
     *
     * @param  array<string, mixed>  $file
     * @param  array<int, string>  $allowedMetaKeys
     * @return array<string, mixed>
     */
    private function keepFileMeta(array $file, string $url, array $allowedMetaKeys): array
    {
        $result = ['url' => $url];

        foreach ($allowedMetaKeys as $key) {
            if (! array_key_exists($key, $file)) {
                continue;
            }

            $value = $file[$key];
            if ($value === null || $value === '') {
                continue;
            }

            $result[$key] = match ($key) {
                'width', 'height', 'size' => (int) $value,
                'duration' => round((float) $value, 2),
                default => (string) $value,
            };
        }

        return $result;
    }
}
