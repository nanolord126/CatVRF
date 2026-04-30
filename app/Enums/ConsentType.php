<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Consent Types for 152-FZ Compliance
 * 
 * Defines different categories of personal data processing that require explicit consent.
 * Biometric data requires written form or UKEDS (enhanced qualified electronic signature).
 */
enum ConsentType: string
{
    // Basic personal data processing
    case REGISTRATION = 'registration';
    case ORDERS = 'orders';
    case COMMUNICATIONS = 'communications';

    // Special categories requiring enhanced consent
    case BIOMETRIC_FACE_ID = 'biometric_face_id';
    case BIOMETRIC_LIVENESS = 'biometric_liveness';
    case BIOMETRIC_BEHAVIORAL = 'biometric_behavioral';
    case BIOMETRIC_VOICE = 'biometric_voice';

    // Business verification
    case KYB_VERIFICATION = 'kyb_verification';
    case DOCUMENT_VERIFICATION = 'document_verification';

    // Data retention
    case LONG_TERM_STORAGE = 'long_term_storage';

    /**
     * Check if this consent type is biometric (requires written form/UKEDS)
     */
    public function isBiometric(): bool
    {
        return str_starts_with($this->value, 'biometric_');
    }

    /**
     * Check if this consent type requires enhanced form (written/UKEDS)
     */
    public function requiresEnhancedForm(): bool
    {
        return $this->isBiometric() || $this === self::KYB_VERIFICATION;
    }

    /**
     * Get human-readable label in Russian
     */
    public function label(): string
    {
        return match ($this) {
            self::REGISTRATION => 'Обработка персональных данных при регистрации',
            self::ORDERS => 'Обработка данных для оформления заказов',
            self::COMMUNICATIONS => 'Отправка уведомлений и коммуникаций',
            self::BIOMETRIC_FACE_ID => 'Использование биометрии Face ID',
            self::BIOMETRIC_LIVENESS => 'Проверка liveness (активность пользователя)',
            self::BIOMETRIC_BEHAVIORAL => 'Поведенческая биометрия (клавиатурный почерк, движения мыши)',
            self::BIOMETRIC_VOICE => 'Голосовая биометрия',
            self::KYB_VERIFICATION => 'Верификация бизнеса (KYB)',
            self::DOCUMENT_VERIFICATION => 'Верификация документов',
            self::LONG_TERM_STORAGE => 'Хранение данных более 30 дней',
        };
    }

    /**
     * Get data retention period in days for this consent type
     */
    public function retentionDays(): int
    {
        return match ($this) {
            self::REGISTRATION => 365, // 1 year for account data
            self::ORDERS => 1095, // 3 years for order data (tax requirements)
            self::COMMUNICATIONS => 180, // 6 months for notification logs
            self::BIOMETRIC_FACE_ID => 365, // 1 year for biometric templates
            self::BIOMETRIC_LIVENESS => 30, // 30 days for liveness data
            self::BIOMETRIC_BEHAVIORAL => 90, // 90 days for behavioral patterns
            self::BIOMETRIC_VOICE => 365, // 1 year for voice templates
            self::KYB_VERIFICATION => 1825, // 5 years for business verification
            self::DOCUMENT_VERIFICATION => 1825, // 5 years for document copies
            self::LONG_TERM_STORAGE => 2555, // 7 years for long-term storage
        };
    }

    /**
     * Get legal basis description for 152-FZ
     */
    public function legalBasis(): string
    {
        return match ($this) {
            self::REGISTRATION => 'Ст. 9 ФЗ-152: Согласие субъекта на обработку персональных данных',
            self::ORDERS => 'Ст. 9 ФЗ-152: Согласие на обработку для исполнения договора',
            self::COMMUNICATIONS => 'Ст. 9 ФЗ-152: Согласие на информационную рассылку',
            self::BIOMETRIC_FACE_ID, self::BIOMETRIC_LIVENESS, self::BIOMETRIC_BEHAVIORAL, self::BIOMETRIC_VOICE => 'Ст. 11 ФЗ-152: Письменное согласие на обработку биометрических персональных данных',
            self::KYB_VERIFICATION => 'Ст. 9 ФЗ-152: Согласие на обработку для проверки контрагента',
            self::DOCUMENT_VERIFICATION => 'Ст. 9 ФЗ-152: Согласие на обработку документов',
            self::LONG_TERM_STORAGE => 'Ст. 9 ФЗ-152: Согласие на длительное хранение',
        };
    }
}
