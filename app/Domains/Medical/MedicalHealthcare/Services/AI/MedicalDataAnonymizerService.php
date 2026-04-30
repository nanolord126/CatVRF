<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

/**
 * MedicalDataAnonymizerService - Anonymizes medical data for external AI calls
 * 
 * Ensures compliance with 152-ФZ (personal data protection) and ФЗ-323 (healthcare data).
 * No raw PII or medical data should be sent to external LLMs.
 */
final readonly class MedicalDataAnonymizerService
{
    private const NAME_PATTERN = '/[A-ZА-Я][a-zа-я]+/u';
    private const PHONE_PATTERN = '/\+?\d{1,3}[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{2}[-.\s]?\d{2}/';
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    private const PASSPORT_PATTERN = '/\d{4}\s?\d{6}/';
    private const INN_PATTERN = '/\d{10}|\d{12}/';
    private const ADDRESS_PATTERN = '/(?:ул\.|улица|д\.|дом|кв\.|квартира|г\.|город)/i';

    /**
     * Anonymize medical text by removing PII and sensitive identifiers
     */
    public function anonymizeMedicalText(string $text): string
    {
        $text = $this->anonymizeNames($text);
        $text = $this->anonymizePhoneNumbers($text);
        $text = $this->anonymizeEmails($text);
        $text = $this->anonymizePassportNumbers($text);
        $text = $this->anonymizeINN($text);
        $text = $this->anonymizeAddresses($text);
        
        return $text;
    }

    /**
     * Anonymize user ID for analytics
     */
    public function anonymizeUserId(int $userId): string
    {
        return hash('sha256', (string)$userId . config('app.key'));
    }

    private function anonymizeNames(string $text): string
    {
        return preg_replace(self::NAME_PATTERN, '[ИМЯ]', $text);
    }

    private function anonymizePhoneNumbers(string $text): string
    {
        return preg_replace(self::PHONE_PATTERN, '[ТЕЛЕФОН]', $text);
    }

    private function anonymizeEmails(string $text): string
    {
        return preg_replace(self::EMAIL_PATTERN, '[EMAIL]', $text);
    }

    private function anonymizePassportNumbers(string $text): string
    {
        return preg_replace(self::PASSPORT_PATTERN, '[ПАСПОРТ]', $text);
    }

    private function anonymizeINN(string $text): string
    {
        return preg_replace(self::INN_PATTERN, '[ИНН]', $text);
    }

    private function anonymizeAddresses(string $text): string
    {
        return preg_replace(self::ADDRESS_PATTERN, '[АДРЕС]', $text);
    }
}
