<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cooldown Action Types
 * 
 * Defines the types of actions that can trigger a cooldown period.
 * Each action type has a default cooldown duration in hours.
 */
enum CooldownActionType: string
{
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case CHANGE_BANK = 'change_bank';
    case NEW_DEVICE = 'new_device';
    case PASSWORD_CHANGE = 'password_change';
    case TWO_FA_CHANGE = '2fa_change';
    case PASSKEY_CHANGE = 'passkey_change';
    case EMAIL_CHANGE = 'email_change';
    case PHONE_CHANGE = 'phone_change';
    case ROLE_CHANGE = 'role_change';
    case STAFF_INVITE = 'staff_invite';
    case HIGH_FRAUD_SCORE = 'high_fraud_score';
    case DATA_EXPORT = 'data_export';
    case HUNTING_DETECTED = 'hunting_detected';
    case BEHAVIORAL_ANOMALY = 'behavioral_anomaly';
    case VPN_LOGIN = 'vpn_login';
    case FINANCIAL_OPERATIONS = 'financial_operations';
    case CRITICAL_CHANGES = 'critical_changes';

    public function getDefaultDurationHours(): int
    {
        return match ($this) {
            self::WITHDRAWAL => 24,
            self::TRANSFER => 24,
            self::CHANGE_BANK => 72,
            self::NEW_DEVICE => 24,
            self::PASSWORD_CHANGE => 48,
            self::TWO_FA_CHANGE => 48,
            self::PASSKEY_CHANGE => 48,
            self::EMAIL_CHANGE => 72,
            self::PHONE_CHANGE => 72,
            self::ROLE_CHANGE => 24,
            self::DATA_EXPORT => 72, // 3 days
            self::HUNTING_DETECTED => 168, // 7 days
            self::STAFF_INVITE => 24,
            self::HIGH_FRAUD_SCORE => 168, // 7 days
            self::VPN_LOGIN => 12, // 12 hours (dynamic based on risk level)
            self::FINANCIAL_OPERATIONS => 12, // 12 hours (dynamic based on VPN risk level)
            self::CRITICAL_CHANGES => 24, // 24 hours (dynamic based on VPN risk level)
        };
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WITHDRAWAL => 'Вывод средств',
            self::TRANSFER => 'Перевод средств',
            self::CHANGE_BANK => 'Смена банковских реквизитов',
            self::NEW_DEVICE => 'Вход с нового устройства',
            self::PASSWORD_CHANGE => 'Смена пароля',
            self::TWO_FA_CHANGE => 'Смена двухфакторной аутентификации',
            self::PASSKEY_CHANGE => 'Смена Passkey',
            self::EMAIL_CHANGE => 'Смена email',
            self::DATA_EXPORT => 'Экспорт данных',
            self::HUNTING_DETECTED => 'Обнаружен hunting pattern',
            self::PHONE_CHANGE => 'Смена телефона',
            self::ROLE_CHANGE => 'Смена роли',
            self::STAFF_INVITE => 'Приглашение сотрудника',
            self::VPN_LOGIN => 'Вход через VPN',
            self::FINANCIAL_OPERATIONS => 'Финансовые операции',
            self::CRITICAL_CHANGES => 'Критические изменения',
        };
    }

    /**
     * Check if this action type affects financial operations
     */
    public function affectsFinancialOperations(): bool
    {
        return in_array($this, [
            self::WITHDRAWAL,
            self::TRANSFER,
            self::CHANGE_BANK,
            self::NEW_DEVICE,
            self::PASSWORD_CHANGE,
            self::TWO_FA_CHANGE,
            self::DATA_EXPORT,
            self::HUNTING_DETECTED,
            self::PASSKEY_CHANGE,
            self::EMAIL_CHANGE,
            self::PHONE_CHANGE,
            self::ROLE_CHANGE,
            self::STAFF_INVITE,
            self::HIGH_FRAUD_SCORE,
            self::BEHAVIORAL_ANOMALY,
            self::VPN_LOGIN,
            self::FINANCIAL_OPERATIONS,
            self::CRITICAL_CHANGES,
        ], true);
    }
}
