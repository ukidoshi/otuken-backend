<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Расширяет блоки Editor.js (image / video) метаданными файлов:
 *  - image:  width / height / mime / size
 *  - video:  width / height / mime / size / duration / poster_url
 *
 * Поле data.file может быть строкой или содержать data.url — приводим к объекту.
 * Постер для видео создаётся рядом с файлом как <basename>.poster.jpg, если есть ffmpeg/ffprobe.
 */
class EditorJsMediaMetadata
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    public function __construct(
        private readonly string $disk = 'public',
        private readonly string $publicPathPrefix = '/storage/',
    ) {}

    /**
     * Обработать массив блоков content_blocks.
     *
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function enrichBlocks(array $blocks, bool $regeneratePosters = false): array
    {
        $out = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                $out[] = $block;

                continue;
            }

            $type = (string) ($block['type'] ?? '');

            if ($type === 'image') {
                $block = $this->enrichImageBlock($block);
            } elseif ($type === 'video') {
                $block = $this->enrichVideoBlock($block, $regeneratePosters);
            }

            $out[] = $block;
        }

        return $out;
    }

    /**
     * Обработать payload content_blocks ({"blocks": [...]} или [...]).
     *
     * @param  array<string, mixed>|array<int, mixed>  $content
     * @return array<string, mixed>
     */
    public function enrichContent(array $content, bool $regeneratePosters = false): array
    {
        if (array_key_exists('blocks', $content) && is_array($content['blocks'])) {
            $content['blocks'] = $this->enrichBlocks($content['blocks'], $regeneratePosters);

            return $content;
        }

        return ['blocks' => $this->enrichBlocks(array_values($content), $regeneratePosters)];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function enrichImageBlock(array $block): array
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $file = $this->coerceFile($data);
        $url = (string) ($file['url'] ?? '');

        if ($url === '') {
            $data['file'] = $file;
            $block['data'] = $data;

            return $block;
        }

        $relative = $this->relativeFromPublicUrl($url);

        if ($relative !== null) {
            $abs = $this->absolutePath($relative);

            $needSize = ! isset($file['width']) || ! isset($file['height']);
            if ($needSize && $abs !== null && is_file($abs)) {
                $info = @getimagesize($abs);
                if (is_array($info)) {
                    $file['width'] = $file['width'] ?? (int) $info[0];
                    $file['height'] = $file['height'] ?? (int) $info[1];
                    if (! isset($file['mime']) && isset($info['mime'])) {
                        $file['mime'] = (string) $info['mime'];
                    }
                }
            }

            if (! isset($file['mime'])) {
                $mime = $this->detectMime($relative);
                if ($mime !== null) {
                    $file['mime'] = $mime;
                }
            }

            if (! isset($file['size'])) {
                $size = $this->detectSize($relative);
                if ($size !== null) {
                    $file['size'] = $size;
                }
            }
        }

        $data['file'] = $file;
        $block['data'] = $data;

        return $block;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function enrichVideoBlock(array $block, bool $regeneratePosters): array
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $file = $this->coerceFile($data);
        $url = (string) ($file['url'] ?? '');

        if ($url === '') {
            $data['file'] = $file;
            $block['data'] = $data;

            return $block;
        }

        $relative = $this->relativeFromPublicUrl($url);
        $abs = $relative !== null ? $this->absolutePath($relative) : null;

        if (! isset($file['mime'])) {
            $mime = $relative !== null ? $this->detectMime($relative) : null;
            if ($mime !== null) {
                $file['mime'] = $mime;
            }
        }

        if (! isset($file['size']) && $relative !== null) {
            $size = $this->detectSize($relative);
            if ($size !== null) {
                $file['size'] = $size;
            }
        }

        if ($abs !== null && is_file($abs)) {
            $probe = $this->ffprobeMeta($abs);
            if ($probe !== null) {
                if (! isset($file['width']) && isset($probe['width'])) {
                    $file['width'] = (int) $probe['width'];
                }
                if (! isset($file['height']) && isset($probe['height'])) {
                    $file['height'] = (int) $probe['height'];
                }
                if (! isset($file['duration']) && isset($probe['duration'])) {
                    $file['duration'] = round((float) $probe['duration'], 2);
                }
            }

            $posterRel = $this->posterRelativePath($relative);
            $posterAbs = $this->absolutePath($posterRel);
            $needPoster = $regeneratePosters || empty($file['poster_url']) || ! is_file($posterAbs ?? '');

            if ($needPoster) {
                $generated = $this->generateVideoPoster($abs, $posterAbs ?? '');
                if ($generated) {
                    $file['poster_url'] = $this->publicUrlFromRelative($posterRel);
                }
            } elseif (empty($file['poster_url']) && is_file($posterAbs ?? '')) {
                $file['poster_url'] = $this->publicUrlFromRelative($posterRel);
            }
        }

        $data['file'] = $file;
        $block['data'] = $data;

        return $block;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function coerceFile(array $data): array
    {
        $file = $data['file'] ?? null;
        if (is_string($file)) {
            return ['url' => $file];
        }
        if (! is_array($file)) {
            $file = [];
        }
        if (! isset($file['url']) && isset($data['url']) && is_string($data['url'])) {
            $file['url'] = $data['url'];
        }

        return $file;
    }

    private function relativeFromPublicUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Поддерживаем абсолютный URL c хостом или просто путь /storage/...
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        if (str_starts_with($path, $this->publicPathPrefix)) {
            return ltrim(substr($path, strlen($this->publicPathPrefix)), '/');
        }

        return null;
    }

    private function publicUrlFromRelative(string $relative): string
    {
        return $this->publicPathPrefix.ltrim($relative, '/');
    }

    private function posterRelativePath(string $relative): string
    {
        $dir = trim(dirname($relative), '.');
        $base = pathinfo($relative, PATHINFO_FILENAME);

        return ($dir === '' ? '' : $dir.'/').$base.'.poster.jpg';
    }

    private function absolutePath(string $relative): ?string
    {
        try {
            return Storage::disk($this->disk)->path($relative);
        } catch (Throwable) {
            return null;
        }
    }

    private function detectMime(string $relative): ?string
    {
        try {
            $mime = Storage::disk($this->disk)->mimeType($relative);

            return is_string($mime) && $mime !== '' ? $mime : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function detectSize(string $relative): ?int
    {
        try {
            $size = Storage::disk($this->disk)->size($relative);

            return is_int($size) ? $size : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{width:int,height:int,duration?:float}|null
     */
    private function ffprobeMeta(string $absPath): ?array
    {
        $key = 'probe:'.$absPath;
        if (array_key_exists($key, $this->cache)) {
            /** @var array{width:int,height:int,duration?:float}|null $cached */
            $cached = $this->cache[$key];

            return $cached;
        }

        $ffprobe = (new ExecutableFinder)->find('ffprobe');
        if ($ffprobe === null) {
            return $this->cache[$key] = null;
        }

        try {
            $process = new Process([
                $ffprobe,
                '-v', 'error',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height:format=duration',
                '-of', 'json',
                $absPath,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                return $this->cache[$key] = null;
            }

            $raw = $process->getOutput();
            $data = json_decode($raw, true);
            if (! is_array($data)) {
                return $this->cache[$key] = null;
            }

            $stream = $data['streams'][0] ?? null;
            $format = $data['format'] ?? null;

            if (! is_array($stream)) {
                return $this->cache[$key] = null;
            }

            $result = [
                'width' => isset($stream['width']) ? (int) $stream['width'] : null,
                'height' => isset($stream['height']) ? (int) $stream['height'] : null,
            ];
            if (is_array($format) && isset($format['duration'])) {
                $result['duration'] = (float) $format['duration'];
            }

            $result = array_filter($result, static fn ($v): bool => $v !== null);

            /** @var array{width:int,height:int,duration?:float} $result */
            return $this->cache[$key] = $result;
        } catch (ProcessFailedException|Throwable) {
            return $this->cache[$key] = null;
        }
    }

    private function generateVideoPoster(string $videoAbsPath, string $posterAbsPath): bool
    {
        $ffmpeg = (new ExecutableFinder)->find('ffmpeg');
        if ($ffmpeg === null || $posterAbsPath === '') {
            return false;
        }

        $dir = dirname($posterAbsPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        try {
            $process = new Process([
                $ffmpeg,
                '-y',
                '-ss', '1',
                '-i', $videoAbsPath,
                '-frames:v', '1',
                // длинная сторона до 1280 с сохранением пропорций
                '-vf', "scale='if(gt(iw,ih),min(1280,iw),-2)':'if(gt(iw,ih),-2,min(1280,ih))'",
                '-q:v', '4',
                $posterAbsPath,
            ]);
            $process->setTimeout(60);
            $process->run();

            return $process->isSuccessful() && is_file($posterAbsPath);
        } catch (Throwable) {
            return false;
        }
    }
}
