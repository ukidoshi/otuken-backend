<?php

namespace App\Services\Leads;

use App\Models\SiteSetting;

/**
 * Список e-mail для уведомлений о заявках с лендинга.
 *
 * Приоритет: админка (site_settings) → LEADS_MANAGER_EMAIL в .env (можно через запятую).
 */
class LeadRecipientList
{
    public const SETTING_KEY = 'district_interest.recipient_emails';

    /**
     * @return list<string>
     */
    public static function resolve(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_KEY);

        if (is_array($stored) && $stored !== []) {
            return self::normalize($stored);
        }

        $legacy = config('landing.leads_manager_email');

        if (! is_string($legacy) || trim($legacy) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $legacy) ?: [];

        return self::normalize($parts);
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<string>
     */
    public static function normalize(array $raw): array
    {
        $emails = [];
        $seen = [];

        foreach ($raw as $item) {
            $email = is_array($item) ? ($item['email'] ?? '') : $item;
            $email = trim((string) $email);

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $key = mb_strtolower($email);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $emails[] = $email;
        }

        return $emails;
    }

    /**
     * @param  list<string>  $emails
     */
    public static function store(array $emails): void
    {
        SiteSetting::setValue(self::SETTING_KEY, self::normalize($emails));
    }
}
