<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Sensitive Data Masker
 *
 * Masks PII (Personally Identifiable Information) and sensitive data
 * in logs and audit trails to comply with 152-FZ, FZ-323, and FSTEC requirements.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Compliance
 * - Medical data (symptoms, diagnoses, health scores) MUST be anonymized
 * - Personal data (phone, email, passport, SNILS) MUST be masked
 * - IP addresses, tokens, API keys MUST be partially masked
 * - Credit card numbers MUST be PCI-DSS compliant masking
 */
final readonly class SensitiveDataMasker
{
    private const MASK_PATTERN = '***';
    private const EMAIL_MASK_PATTERN = '***@***.***';
    private const PHONE_MASK_PATTERN = '+7 (***) ***-**-**';
    private const CREDIT_CARD_MASK_PATTERN = '**** **** **** ****';
    private const TOKEN_MASK_PATTERN = '***...***';

    /**
     * Mask sensitive data in an array or string
     *
     * @param  array|string  $data
     * @return array|string
     */
    public function mask(array|string $data): array|string
    {
        if (is_string($data)) {
            return $this->maskString($data);
        }

        return $this->maskArray($data);
    }

    /**
     * Mask sensitive data in an array recursively
     *
     * @param  array  $data
     * @return array
     */
    private function maskArray(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskArray($value);
            } elseif (is_string($value)) {
                $masked[$key] = $this->maskByKey($key, $value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Mask string based on key context
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    private function maskByKey(string $key, string $value): string
    {
        $lowerKey = strtolower($key);

        // Email addresses
        if (str_contains($lowerKey, 'email') || str_contains($lowerKey, 'mail')) {
            return $this->maskEmail($value);
        }

        // Phone numbers
        if (str_contains($lowerKey, 'phone') || str_contains($lowerKey, 'mobile') || str_contains($lowerKey, 'tel')) {
            return $this->maskPhone($value);
        }

        // Passwords
        if (str_contains($lowerKey, 'password') || str_contains($lowerKey, 'pass') || str_contains($lowerKey, 'pwd')) {
            return self::MASK_PATTERN;
        }

        // Tokens, API keys, secrets
        if (str_contains($lowerKey, 'token') || str_contains($lowerKey, 'api_key') || str_contains($lowerKey, 'secret') || str_contains($lowerKey, 'key')) {
            return $this->maskToken($value);
        }

        // Credit cards
        if (str_contains($lowerKey, 'card') || str_contains($lowerKey, 'credit') || str_contains($lowerKey, 'pan')) {
            return $this->maskCreditCard($value);
        }

        // IP addresses
        if (str_contains($lowerKey, 'ip') || str_contains($lowerKey, 'address')) {
            return $this->maskIpAddress($value);
        }

        // Medical data (symptoms, diagnoses, health scores)
        if (str_contains($lowerKey, 'symptom') || str_contains($lowerKey, 'diagnos') || str_contains($lowerKey, 'health') || str_contains($lowerKey, 'medical')) {
            return $this->maskMedicalData($value);
        }

        // Passport, SNILS, OMS
        if (str_contains($lowerKey, 'passport') || str_contains($lowerKey, 'snils') || str_contains($lowerKey, 'oms') || str_contains($lowerKey, 'inn')) {
            return $this->maskDocument($value);
        }

        // Name, surname, patronymic
        if (str_contains($lowerKey, 'name') || str_contains($lowerKey, 'surname') || str_contains($lowerKey, 'patronymic') || str_contains($lowerKey, 'first_name') || str_contains($lowerKey, 'last_name')) {
            return $this->maskName($value);
        }

        return $value;
    }

    /**
     * Mask string without key context (pattern-based)
     *
     * @param  string  $value
     * @return string
     */
    private function maskString(string $value): string
    {
        // Email pattern
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        // Credit card pattern (16 digits)
        if (preg_match('/^\d{16}$/', $value)) {
            return $this->maskCreditCard($value);
        }

        // IP address pattern
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $this->maskIpAddress($value);
        }

        // Russian phone pattern
        if (preg_match('/^(\+7|8)?\d{10}$/', preg_replace('/[^0-9+]/', '', $value))) {
            return $this->maskPhone($value);
        }

        return $value;
    }

    /**
     * Mask email address
     *
     * @param  string  $email
     * @return string
     */
    public function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::MASK_PATTERN;
        }

        $parts = explode('@', $email);
        $local = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        $maskedDomain = str_repeat('*', max(0, strlen($domain) - 4)) . substr($domain, -4);

        return $maskedLocal . '@' . $maskedDomain;
    }

    /**
     * Mask phone number
     *
     * @param  string  $phone
     * @return string
     */
    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);

        if (strlen($digits) < 10) {
            return self::MASK_PATTERN;
        }

        return self::PHONE_MASK_PATTERN;
    }

    /**
     * Mask token or API key
     *
     * @param  string  $token
     * @return string
     */
    public function maskToken(string $token): string
    {
        if (strlen($token) <= 8) {
            return self::MASK_PATTERN;
        }

        return substr($token, 0, 4) . '...' . substr($token, -4);
    }

    /**
     * Mask credit card number (PCI-DSS compliant)
     *
     * @param  string  $card
     * @return string
     */
    public function maskCreditCard(string $card): string
    {
        $digits = preg_replace('/[^0-9]/', '', $card);

        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return self::MASK_PATTERN;
        }

        // Show first 6 and last 4 digits (PCI-DSS requirement)
        return substr($digits, 0, 6) . str_repeat('*', strlen($digits) - 10) . substr($digits, -4);
    }

    /**
     * Mask IP address
     *
     * @param  string  $ip
     * @return string
     */
    public function maskIpAddress(string $ip): string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return self::MASK_PATTERN;
        }

        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.' . self::MASK_PATTERN . '.' . $parts[3];
        }

        $parts = explode(':', $ip);
        if (count($parts) === 8) {
            return $parts[0] . ':' . $parts[1] . ':' . self::MASK_PATTERN . ':' . $parts[7];
        }

        return self::MASK_PATTERN;
    }

    /**
     * Mask medical data (152-FZ, FZ-323 compliance)
     *
     * @param  string  $data
     * @return string
     */
    public function maskMedicalData(string $data): string
    {
        // Medical data must be completely anonymized for external logs
        return self::MASK_PATTERN;
    }

    /**
     * Mask document number (passport, SNILS, OMS, INN)
     *
     * @param  string  $document
     * @return string
     */
    public function maskDocument(string $document): string
    {
        $digits = preg_replace('/[^0-9]/', '', $document);

        if (strlen($digits) <= 4) {
            return self::MASK_PATTERN;
        }

        return substr($digits, 0, 2) . str_repeat('*', strlen($digits) - 4) . substr($digits, -2);
    }

    /**
     * Mask name (first name, last name, patronymic)
     *
     * @param  string  $name
     * @return string
     */
    public function maskName(string $name): string
    {
        if (strlen($name) <= 2) {
            return self::MASK_PATTERN;
        }

        return mb_substr($name, 0, 1) . str_repeat('*', mb_strlen($name) - 2) . mb_substr($name, -1);
    }
}
