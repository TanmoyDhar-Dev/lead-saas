<?php

namespace App\Services\LeadImport;

class ContactFieldSanitizer
{
    /**
     * @return array{emails: list<string>, invalid: list<string>}
     */
    public function extractEmailsDetailed(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['emails' => [], 'invalid' => []];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[\s,;|\/]+/u', $normalized) ?: [];

        $emails = [];
        $invalid = [];

        foreach ($parts as $part) {
            $email = strtolower(trim($part, " \t\n\r\0\x0B\"'<>"));
            $email = rtrim($email, '.,;');
            // Strip common invisible / zero-width characters
            $email = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $email) ?? $email;

            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[$email] = $email;
                continue;
            }

            $emails[$email] = $email;
        }

        return [
            'emails' => array_values($emails),
            'invalid' => array_values($invalid),
        ];
    }

    /**
     * @return list<string>
     */
    public function extractEmails(?string $raw): array
    {
        return $this->extractEmailsDetailed($raw)['emails'];
    }

    /**
     * @return list<string>
     */
    public function extractPhones(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[;\n]+/u', $normalized) ?: [];

        $phones = [];
        foreach ($parts as $part) {
            $phone = trim($part);
            $phone = preg_replace('/\s+/', ' ', $phone) ?? $phone;
            $phone = trim($phone, " \t,\"'");

            if ($phone === '') {
                continue;
            }

            $phones[$phone] = $phone;
        }

        return array_values($phones);
    }

    public function cleanText(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim(preg_replace("/[ \t]+/u", ' ', str_replace(["\r\n", "\r", "\n"], ' ', $raw)) ?? '');

        return $value === '' ? null : $value;
    }
}
