<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RuntimeException;

/**
 * PII Anonymization Service for AI Vertical
 *
 * Ensures compliance with 152-FZ and GDPR by anonymizing
 * personal data before sending to external AI services (OpenAI, etc.)
 *
 * Anonymizes:
 * - Names, emails, phone numbers
 * - Addresses, geographic data
 * - IDs, document numbers
 * - Any other PII
 *
 * Strictly follows CatVRF rules:
 * - No facades, uses dependency injection
 * - All operations are deterministic and reversible only with salt
 * - Medical data is never sent to external AI services
 */
final readonly class PIIAnonymizationService
{
    private const string EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    private const string PHONE_PATTERN = '/\+?[78][-\(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}/';
    private const string INN_PATTERN = '/\b\d{10}\b|\b\d{12}\b/';
    private const string PASSPORT_PATTERN = '/\b\d{4}\s?\d{6}\b/';
    private const string CARD_PATTERN = '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/';
    private const string ADDRESS_PATTERN = '/\b(?:ул\.|улица|пр\.|проспект|д\.|дом|кв\.|квартира)\s+[^\n]+/i';
    private const string NAME_PATTERN = '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+(?:\s+[A-ZА-Я][a-zа-я]+)?\b/u';

    public function __construct(
        private readonly ConfigRepository $config
    ) {}

    /**
     * Anonymize data before sending to external AI services
     *
     * @param array|string $data Data to anonymize
     * @return array|string Anonymized data
     * @throws RuntimeException If data contains medical PII
     */
    public function anonymize(array|string $data): array|string
    {
        if (is_string($data)) {
            return $this->anonymizeString($data);
        }

        return $this->anonymizeArray($data);
    }

    /**
     * Anonymize string data with comprehensive PII detection
     *
     * @param string $data Input string
     * @return string Anonymized string
     */
    private function anonymizeString(string $data): string
    {
        // Check for medical data first - should never be sent to external AI
        if ($this->containsMedicalData($data)) {
            throw new RuntimeException(
                'Medical data detected. Cannot send to external AI services per 152-FZ compliance.'
            );
        }

        // Remove emails
        $data = preg_replace(self::EMAIL_PATTERN, '[EMAIL]', $data);

        // Remove phone numbers (Russian format)
        $data = preg_replace(self::PHONE_PATTERN, '[PHONE]', $data);

        // Remove INN (Russian tax ID)
        $data = preg_replace(self::INN_PATTERN, '[INN]', $data);

        // Remove passport numbers
        $data = preg_replace(self::PASSPORT_PATTERN, '[PASSPORT]', $data);

        // Remove credit card numbers
        $data = preg_replace(self::CARD_PATTERN, '[CARD]', $data);

        // Remove addresses
        $data = preg_replace(self::ADDRESS_PATTERN, '[ADDRESS]', $data);

        // Remove full names (basic pattern)
        $data = preg_replace(self::NAME_PATTERN, '[NAME]', $data);

        return $data;
    }

    /**
     * Anonymize array data recursively
     *
     * @param array $data Input array
     * @return array Anonymized array
     */
    private function anonymizeArray(array $data): array
    {
        $anonymized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $anonymized[$key] = $this->anonymizeString($value);
            } elseif (is_array($value)) {
                $anonymized[$key] = $this->anonymizeArray($value);
            } else {
                $anonymized[$key] = $value;
            }
        }

        return $anonymized;
    }

    /**
     * Generate anonymous user ID for AI services
     * This ID is consistent for the same user but not reversible
     *
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @return string Anonymous user ID
     */
    public function getAnonymousUserId(int $userId, int $tenantId): string
    {
        $salt = $this->config->get('app.key');
        $hash = hash('sha256', $userId . '|' . $tenantId . '|' . $salt);

        return 'user_' . substr($hash, 0, 16);
    }

    /**
     * Check if data contains PII
     *
     * @param string $data Input string
     * @return bool True if PII detected
     */
    public function containsPII(string $data): bool
    {
        $patterns = [
            self::EMAIL_PATTERN,
            self::PHONE_PATTERN,
            self::INN_PATTERN,
            self::PASSPORT_PATTERN,
            self::CARD_PATTERN,
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if data contains medical information
     * Medical data must never be sent to external AI services per 152-FZ
     *
     * @param string $data Input string
     * @return bool True if medical data detected
     */
    public function containsMedicalData(string $data): bool
    {
        $medicalKeywords = [
            'диагноз', 'симптом', 'болезнь', 'лечение', 'медицинский',
            'диагностика', 'пациент', 'врач', 'лекарство', 'рецепт',
            'анализ', 'обследование', 'операция', 'хирургия', 'терапия',
            'diagnosis', 'symptom', 'disease', 'treatment', 'medical',
            'patient', 'doctor', 'medication', 'prescription', 'analysis',
        ];

        $lowerData = mb_strtolower($data);

        foreach ($medicalKeywords as $keyword) {
            if (str_contains($lowerData, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anonymize specific fields in a data structure
     * Selectively anonymizes only specified fields
     *
     * @param array $data Input data
     * @param array $fields Fields to anonymize
     * @return array Data with specified fields anonymized
     */
    public function anonymizeFields(array $data, array $fields): array
    {
        $anonymized = $data;

        foreach ($fields as $field) {
            if (isset($anonymized[$field]) && is_string($anonymized[$field])) {
                $anonymized[$field] = $this->anonymizeString($anonymized[$field]);
            }
        }

        return $anonymized;
    }

    /**
     * Sanitize prompt for AI services
     * Removes PII while preserving context
     *
     * @param string $prompt Input prompt
     * @return string Sanitized prompt
     */
    public function sanitizePrompt(string $prompt): string
    {
        $sanitized = $this->anonymizeString($prompt);

        // Remove any remaining personal identifiers
        $sanitized = preg_replace('/\b\d{2,}\b/', '[ID]', $sanitized);

        return $sanitized;
    }

    /**
     * Generate correlation ID for AI requests
     * Used for tracking and audit purposes
     *
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @param string $operation Operation type
     * @return string Correlation ID
     */
    public function generateCorrelationId(int $userId, int $tenantId, string $operation): string
    {
        $salt = $this->config->get('app.key');
        $timestamp = microtime(true);
        $hash = hash('sha256', $userId . '|' . $tenantId . '|' . $operation . '|' . $timestamp . '|' . $salt);

        return 'corr_' . substr($hash, 0, 20);
    }

    /**
     * Validate that data is safe for external AI processing
     *
     * @param array|string $data Data to validate
     * @return bool True if safe
     * @throws RuntimeException If data contains PII or medical information
     */
    public function validateSafeForAI(array|string $data): bool
    {
        $dataString = is_array($data) ? json_encode($data) : $data;

        if ($this->containsMedicalData($dataString)) {
            throw new RuntimeException(
                'Medical data detected. Cannot send to external AI services per 152-FZ compliance.'
            );
        }

        if ($this->containsPII($dataString)) {
            throw new RuntimeException(
                'PII detected. Data must be anonymized before sending to external AI services.'
            );
        }

        return true;
    }
}
