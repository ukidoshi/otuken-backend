<?php

namespace App\Services\LandingContent;

use App\Models\LandingContent;

/**
 * Копирует content.ru в content.tuv и content.en (полная глубокая копия массива).
 */
class LandingContentLocaleSync
{
    /**
     * @return array<string, mixed>
     */
    public static function copyOfRussian(array $russian): array
    {
        /** @var array<string, mixed> $copy */
        $copy = json_decode(json_encode($russian), true);

        return $copy;
    }

    /**
     * Дополнить массив переводов: tuv и en = копия ru.
     *
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    public static function mirrorIntoTranslations(array $translations): array
    {
        $ru = $translations['ru'] ?? null;
        if (! is_array($ru) || $ru === []) {
            return $translations;
        }

        $copy = self::copyOfRussian($ru);
        $translations['tuv'] = $copy;
        $translations['en'] = $copy;

        return $translations;
    }

    /**
     * Перезаписать tuv/en из ru для одной записи landing_contents.
     */
    public static function mirrorRecord(LandingContent $record): bool
    {
        $ru = $record->getTranslation('content', 'ru', false);
        if (! is_array($ru) || $ru === []) {
            return false;
        }

        $copy = self::copyOfRussian($ru);
        $record->setTranslation('content', 'tuv', $copy);
        $record->setTranslation('content', 'en', $copy);
        $record->save();

        return true;
    }

    /**
     * Прогнать по всем записям лендинга.
     */
    public static function mirrorAll(): int
    {
        $updated = 0;

        foreach (LandingContent::query()->orderBy('section_key')->cursor() as $record) {
            if (self::mirrorRecord($record)) {
                $updated++;
            }
        }

        return $updated;
    }
}
