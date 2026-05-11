<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTranslationService
{
    /**
     * @param  array<string, string|null>  $source
     * @return array<string, string>
     */
    public function translateRuToEn(array $source): array
    {
        $result = [];

        foreach ($source as $key => $value) {
            $result[$key] = trim((string) $value);
        }

        if (! config('services.openrouter.api_key')) {
            return $result;
        }

        try {
            $response = $this->sendWithModelFallback([
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional translator. Translate Russian news content into natural English. Keep structure and meaning. Return ONLY JSON object with same keys as input.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate values from ru to en and return JSON only.\nInput:\n".json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ]);

            $content = Arr::get($response, 'choices.0.message.content');
            $parsed = is_string($content) ? $this->decodeJsonContent($content) : null;

            if (! is_array($parsed)) {
                return $result;
            }

            foreach (array_keys($result) as $key) {
                $translated = trim((string) ($parsed[$key] ?? ''));
                if ($translated !== '') {
                    $result[$key] = $translated;
                }
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::warning('AI translation failed, using source content.', [
                'error' => $exception->getMessage(),
            ]);

            return $result;
        }
    }

    /**
     * Перевод RU → тувинский (кириллица). Качество сильно зависит от модели; проверяйте artisan news:test-tuv-translation.
     *
     * @param  array<string, string|null>  $source
     * @return array<string, string>
     */
    public function translateRuToTuv(array $source): array
    {
        $result = [];

        foreach ($source as $key => $value) {
            $result[$key] = trim((string) $value);
        }

        if (! config('services.openrouter.api_key')) {
            return $result;
        }

        try {
            $response = $this->sendWithModelFallback([
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional translator into Tuvan (Tyvan, тувинский язык). '
                            .'Translate Russian news text into natural Tuvan using Cyrillic script (standard Tuvan orthography). '
                            .'Keep proper names recognizable; do not add commentary. Return ONLY a JSON object with the same keys as the input.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate each value from Russian into Tuvan. Return JSON only, same keys.\nInput:\n"
                            .json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ]);

            $content = Arr::get($response, 'choices.0.message.content');
            $parsed = is_string($content) ? $this->decodeJsonContent($content) : null;

            if (! is_array($parsed)) {
                return $result;
            }

            foreach (array_keys($result) as $key) {
                $translated = trim((string) ($parsed[$key] ?? ''));
                if ($translated !== '') {
                    $result[$key] = $translated;
                }
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::warning('AI Tuvan translation failed, using source content.', [
                'error' => $exception->getMessage(),
            ]);

            return $result;
        }
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendWithModelFallback(array $payload, int $timeout = 90): array
    {
        $lastError = null;
        $attemptsPerModel = 3;

        foreach ($this->candidateModels() as $model) {
            for ($attempt = 1; $attempt <= $attemptsPerModel; $attempt++) {
                try {
                    return Http::timeout($timeout)
                        ->connectTimeout(20)
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
                    Log::warning('OpenRouter translation request failed', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'error' => $exception->getMessage(),
                    ]);
                    if ($attempt < $attemptsPerModel) {
                        usleep(200_000 * ($attempt ** 2));

                        continue;
                    }
                }
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

