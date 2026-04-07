<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Referral\Enums;

/**
 * Абсолютно строгий перечислитель (Enum) для бескомпромиссной типизации видов реферальных вознаграждений.
 *
 * Категорически предотвращает ошибки начисления бонусов за счет жесткой фиксации
 * допустимых причин получения денежных или бонусных преференций.
 */
enum ReferralRewardType: string
{
    /** Категорическое вознаграждение за стандартное приглашение покупателя или бизнеса (base referral). */
    case REFERRAL_BONUS = 'referral_bonus';

    /** Исключительно дополнительный бонус за достижение приглашенным пользователем или бизнесом строгого порога оборота (turnover). */
    case TURNOVER_BONUS = 'turnover_bonus';

    /** Безусловное вознаграждение владельцу бизнеса за подтвержденную миграцию с другой платформы (Yandex, Dikidi и т.д.). */
    case MIGRATION_BONUS = 'migration_bonus';
}
