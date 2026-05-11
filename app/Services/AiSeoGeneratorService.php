<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiSeoGeneratorService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function healthCheck(): array
    {
        if (! config('services.openrouter.api_key')) {
            return [
                'ok' => false,
                'message' => 'OPENROUTER_API_KEY не задан в .env',
            ];
        }

        try {
            $this->sendWithModelFallback([
                'temperature' => 0,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Reply with exactly: OK',
                    ],
                ],
                'max_tokens' => 5,
            ], 15);

            return [
                'ok' => true,
                'message' => 'AI подключен: OpenRouter отвечает корректно.',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'AI недоступен: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, string>  $titles
     * @param  array<string, string|null>  $excerpts
     * @return array{seo_title: array<string, string>, seo_description: array<string, string>}
     */
    public function generate(array $titles, array $excerpts = []): array
    {
        $locales = ['ru', 'tuv', 'en'];

        if (! config('services.openrouter.api_key')) {
            return $this->fallback($titles, $excerpts, $locales);
        }

        try {
            $response = $this->sendWithModelFallback([
                'temperature' => 0.4,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert SEO editor for a news portal. Return ONLY valid JSON without markdown and without comments.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($titles, $excerpts),
                    ],
                ],
            ], 20);

            $content = Arr::get($response, 'choices.0.message.content');
            $parsed = is_string($content) ? $this->decodeJsonContent($content) : null;

            if (! is_array($parsed)) {
                return $this->fallback($titles, $excerpts, $locales);
            }

            $seoTitle = (array) ($parsed['seo_title'] ?? []);
            $seoDescription = (array) ($parsed['seo_description'] ?? []);

            $result = ['seo_title' => [], 'seo_description' => []];

            foreach ($locales as $locale) {
                $result['seo_title'][$locale] = $this->sanitizeSeoTitle(
                    (string) ($seoTitle[$locale] ?? ''),
                    (string) ($titles[$locale] ?? $titles['ru'] ?? '')
                );
                $result['seo_description'][$locale] = $this->sanitizeSeoDescription(
                    (string) ($seoDescription[$locale] ?? ''),
                    (string) ($excerpts[$locale] ?? $excerpts['ru'] ?? ''),
                    (string) ($titles[$locale] ?? $titles['ru'] ?? '')
                );
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::warning('AI SEO generation failed, fallback will be used.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->fallback($titles, $excerpts, $locales);
        }
    }

    /**
     * @param  array<string, string>  $titles
     * @param  array<string, string|null>  $excerpts
     * @param  array<int, string>  $locales
     * @return array{seo_title: array<string, string>, seo_description: array<string, string>}
     */
    private function fallback(array $titles, array $excerpts, array $locales): array
    {
        $result = ['seo_title' => [], 'seo_description' => []];

        foreach ($locales as $locale) {
            $title = trim((string) ($titles[$locale] ?? ''));
            $excerpt = trim((string) ($excerpts[$locale] ?? ''));
            $fallbackTitle = $title !== '' ? $title : (string) ($titles['ru'] ?? '');
            $fallbackExcerpt = $excerpt !== '' ? $excerpt : ((string) ($excerpts['ru'] ?? $title));

            $result['seo_title'][$locale] = $this->sanitizeSeoTitle($fallbackTitle, $fallbackTitle);
            $result['seo_description'][$locale] = $this->sanitizeSeoDescription($fallbackExcerpt, $fallbackExcerpt, $fallbackTitle);
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $titles
     * @param  array<string, string|null>  $excerpts
     */
    private function buildPrompt(array $titles, array $excerpts): string
    {
        return <<<PROMPT
Generate SEO for a news article.

Rules:
1) Return STRICT JSON object with this schema:
{
  "seo_title": {"ru":"", "tuv":"", "en":""},
  "seo_description": {"ru":"", "tuv":"", "en":""}
}
2) Each locale text should be written in that locale language and should not be a raw copy of the original title/excerpt.
3) Keep meaning of the article but improve clickability and clarity.
4) Limits:
   - seo_title: max 60 chars
   - seo_description: max 160 chars
5) No markdown, no explanations, no extra keys.

Input titles:
{$this->toJson($titles)}

Input excerpts:
{$this->toJson($excerpts)}
PROMPT;
    }

    private function toJson(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonContent(string $content): ?array
    {
        $decoded = json_decode(trim($content), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function sanitizeSeoTitle(string $value, string $fallback): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($normalized === '') {
            $normalized = $fallback;
        }

        return Str::limit($normalized, 60, '');
    }

    private function sanitizeSeoDescription(string $value, string $fallbackExcerpt, string $fallbackTitle): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($normalized === '') {
            $normalized = $fallbackExcerpt !== '' ? $fallbackExcerpt : $fallbackTitle;
        }

        return Str::limit($normalized, 160, '');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendWithModelFallback(array $payload, int $timeout = 20): array
    {
        $lastError = null;

        foreach ($this->candidateModels() as $model) {
            try {
                return Http::timeout($timeout)
                    ->acceptJson()
                    ->withToken((string) config('services.openrouter.api_key'))
                    ->post(rtrim((string) config('services.openrouter.base_url'), '/').'/chat/completions', [
                        ...$payload,
                        'model' => $model,
                    ])
                    ->throw()
                    ->json();
            } catch (\Throwable $exception) {
                $lastError = $exception;
            }
        }

        throw $lastError ?? new \RuntimeException('OpenRouter request failed with unknown error.');
    }

    /**
     * @return array<int, string>
     */
    private function candidateModels(): array
    {
        $models = (array) config('services.openrouter.models', []);
        $primary = (string) config('services.openrouter.model');

        if ($primary !== '' && ! in_array($primary, $models, true)) {
            array_unshift($models, $primary);
        }

        return array_values(array_filter($models));
    }
}

