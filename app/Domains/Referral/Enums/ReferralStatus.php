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
 * Исключительно строгий и фундаментальный перечислитель (Enum) для статусов реферальной связи.
 *
 * Категорически гарантирует консистентность конечного автомата (State Machine) этапов
 * жизненного цикла реферала — от первоначального намерения до финальной стадии вознаграждения
 * или истечения срока действия приглашения.
 */
enum ReferralStatus: string
{
    /** Безусловное состояние ожидания принятия приглашения клиентом или бизнесом. */
    case PENDING = 'pending';

    /** Статус успешной регистрации реферала на платформе по уникальной ссылке кода. */
    case REGISTERED = 'registered';

    /** Исключительно важный статус подтверждения выполнения условия по минимальному обороту. */
    case QUALIFIED = 'qualified';

    /** Финальный статус, означающий, что бонусное вознаграждение было успешно зачислено на Wallet. */
    case REWARDED = 'rewarded';

    /** Категорический статус истекшего срока действия реферальной ссылки или невыполнения условий в срок. */
    case EXPIRED = 'expired';
}
